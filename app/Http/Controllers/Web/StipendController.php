<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\BatchRecipients;
use App\Models\Batches;
use App\Models\ListAgencies;
use App\Models\ListReferences;
use App\Models\RecipientAllowance;
use App\Models\RecipientStipend;
use App\Models\RecipientWithheld;
use App\Models\Scholars;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Vinkla\Hashids\Facades\Hashids;

class StipendController extends Controller
{
    private function payrollEditableStatuses(): array
    {
        return ['draft', 'rejected_payroll'];
    }

    private function payrollTermStatus(string $batchStatus): string
    {
        return match ($batchStatus) {
            'rejected_payroll' => 'rejected_payroll',
            'submitted_payroll' => 'submitted_payroll',
            'approved_payroll' => 'approved_payroll',
            default => 'draft_payroll',
        };
    }

    private function updateScholarTermPayrollStatus(Batches $batch, int $scholarId, string $batchStatus): void
    {
        $query = DB::table('scholar_term_records')
            ->where('scholar_id', $scholarId)
            ->where('academic_year', $batch->school_year)
            ->whereIn('verification_status', [
                'submitted',
                'draft_payroll',
                'submitted_payroll',
                'rejected_payroll',
                'approved_payroll',
            ]);

        if ($batch->term_id) {
            $query->where('term_id', $batch->term_id);
        } else {
            $query->whereExists(function ($query) use ($batch) {
                $query->select(DB::raw(1))
                    ->from('list_references')
                    ->whereColumn('list_references.id', 'scholar_term_records.term_id')
                    ->whereRaw('LOWER(list_references.name) = ?', [Str::lower($batch->academic_term)]);
            });
        }

        if ($batch->level_id) {
            $query->where('level_id', $batch->level_id);
        }

        $query->update(['verification_status' => $this->payrollTermStatus($batchStatus)]);
    }

    private function resetScholarTermToSubmitted(Batches $batch, int $scholarId): void
    {
        $query = DB::table('scholar_term_records')
            ->where('scholar_id', $scholarId)
            ->where('academic_year', $batch->school_year)
            ->whereIn('verification_status', ['draft_payroll', 'rejected_payroll']);

        if ($batch->term_id) {
            $query->where('term_id', $batch->term_id);
        } else {
            $query->whereExists(function ($query) use ($batch) {
                $query->select(DB::raw(1))
                    ->from('list_references')
                    ->whereColumn('list_references.id', 'scholar_term_records.term_id')
                    ->whereRaw('LOWER(list_references.name) = ?', [Str::lower($batch->academic_term)]);
            });
        }

        if ($batch->level_id) {
            $query->where('level_id', $batch->level_id);
        }

        $query->update(['verification_status' => 'submitted']);
    }

    private function syncBatchRecipientTermStatuses(Batches $batch, string $batchStatus): void
    {
        $batch->loadMissing('recipients:id,batch_id,scholar_id');

        foreach ($batch->recipients as $recipient) {
            if ($recipient->scholar_id) {
                $this->updateScholarTermPayrollStatus($batch, $recipient->scholar_id, $batchStatus);
            }
        }
    }

    private function payrollItemStatus(string $batchStatus): string
    {
        return match ($batchStatus) {
            'submitted_payroll' => 'submitted',
            'approved_payroll' => 'approved',
            'rejected_payroll' => 'rejected',
            default => 'pending',
        };
    }

    private function syncBatchFinancialStatuses(Batches $batch, string $batchStatus): void
    {
        $status = $this->payrollItemStatus($batchStatus);

        $batch->loadMissing('recipients.stipends', 'recipients.withhelds', 'recipients.allowances');

        foreach ($batch->recipients as $recipient) {
            $recipient->update(['status' => $status]);
            $recipient->stipends()->update(['status' => $status]);
            $recipient->allowances()->where('amount', '>', 0)->update(['status' => $status]);
            $recipient->withhelds()->where('total_amount', '>', 0)->update(['status' => $status]);
        }
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Web/stipendPage', [
            'agencyOption' =>  ListAgencies::where('is_active', true)
                ->where('is_delete', false)
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
            'termOptions' => ListReferences::where('is_active', true)
                ->where('is_delete', false)
                ->whereRaw("TRIM(type) = 'Term'")
                ->orderBy('classification')
                ->orderBy('id')
                ->get()
                ->map(fn($term) => [
                    'id' => $term->id,
                    'name' => trim($term->classification . ' - ' . $term->name),
                    'term_name' => $term->name,
                ]),
            'batches' => Batches::whereNull('deleted_at')
                ->with([
                    'level:id,name',
                    'logs' => fn($q) => $q
                        ->select('id', 'batch_id', 'status', 'remarks', 'action_by', 'created_at')
                        ->orderBy('created_at', 'desc')
                ])
                ->paginate(10)
                ->through(fn($q) => [
                    'id'            => Hashids::encode($q->id),
                    'name'          => $q->name,
                    'region'        => $q->region,
                    'term'          => $q->academic_term,
                    'level'         => $q->level?->name,
                    'sy'            => $q->school_year,
                    'user'          => $q->logs->first()?->action_by,
                    'created_at'    => $q->logs->first()?->created_at
                        ? Carbon::parse($q->logs->first()->created_at)->format('M d, Y | h:i a')
                        : null,
                    'remarks'       => $q->logs->first()?->remarks,
                    'status'        => $q->logs->first()?->status ?? 'draft',
                ]),
            'details' => request('id')
                ? $this->batchDetails(request('id'))
                : null,
            'eligibleScholars' => request('id')
                ? $this->eligibleScholars(request('id'), $request)
                : null,
            'payrollRecipients' => request('id')
                ? $this->payrollRecipients(request('id'))
                : null,
        ]);
    }

    private function batchDetails(string $hashId): ?array
    {
        $batchId = Hashids::decode($hashId)[0] ?? 0;
        $batch = Batches::select('id', 'name', 'region', 'academic_term', 'term_id', 'level_id', 'school_year')
            ->with([
                'logs' => fn($q) => $q
                    ->select('id', 'batch_id', 'status', 'remarks', 'action_by', 'created_at')
                    ->orderBy('created_at', 'desc')
            ])
            ->whereId($batchId)
            ->first();

        if (!$batch) {
            return null;
        }

        return [
            'id' => Hashids::encode($batch->id),
            'name' => $batch->name,
            'region' => $batch->region,
            'term' => $batch->academic_term,
            'term_id' => $batch->term_id,
            'level_id' => $batch->level_id,
            'school_year' => $batch->school_year,
            'status' => $batch->logs->first()?->status ?? 'draft',
            'remarks' => $batch->logs->first()?->remarks,
            'remarks_by' => $batch->logs->first()?->action_by,
            'remarks_at' => $batch->logs->first()?->created_at
                ? Carbon::parse($batch->logs->first()->created_at)->format('M d, Y | h:i a')
                : null,
            'is_editable' => in_array($batch->logs->first()?->status ?? 'draft', $this->payrollEditableStatuses(), true),
        ];
    }

    private function eligibleScholars(string $hashId, Request $request)
    {
        $batchId = Hashids::decode($hashId)[0] ?? 0;
        $batch = Batches::find($batchId);
        $search = Str::lower($request->input('eligible_search'));
        $program = Str::lower($request->input('eligible_program'));
        $university = Str::lower($request->input('eligible_university'));
        $status = Str::lower($request->input('eligible_status'));

        if (!$batch) {
            return Scholars::whereRaw('1 = 0')->paginate(10, ['*'], 'eligible_page');
        }

        $sameTermRecipientScholarIds = BatchRecipients::query()
            ->whereHas('batch', function ($query) use ($batch) {
                $query->where('region', $batch->region)
                    ->where('school_year', $batch->school_year)
                    ->whereNull('deleted_at');

                if ($batch->term_id) {
                    $query->where('term_id', $batch->term_id);
                } else {
                    $query->where('academic_term', $batch->academic_term);
                }

                if ($batch->level_id) {
                    $query->where('level_id', $batch->level_id);
                }
            })
            ->pluck('scholar_id')
            ->filter();

        return Scholars::query()
            ->with([
                'profile:scholar_id,fname,mname,lname,suffix,email,birthdate',
                'program:id,name',
                'landbank:scholar_id,account_number',
                'schoolInfo' => fn($q) => $q
                    ->select('id', 'scholar_id', 'campus_id')
                    ->latest('id')
                    ->with('campus:id,name,generated_name,agency_id'),
            ])
            ->where('is_active', true)
            ->where('is_delete', false)
            ->whereHas('termRecords', function ($termQuery) use ($batch) {
                $termQuery->where('verification_status', 'approved')
                    ->where('academic_year', $batch->school_year);

                if ($batch->term_id) {
                    $termQuery->where('term_id', $batch->term_id);
                } else {
                    $termQuery->whereHas('term', function ($term) use ($batch) {
                        $term->whereRaw('LOWER(name) = ?', [Str::lower($batch->academic_term)]);
                    });
                }
            })
            ->when(!Str::contains(Str::lower($batch->region), 'science education institute'), function ($query) use ($batch) {
                $query->whereHas('schoolInfo.campus.agency', function ($agency) use ($batch) {
                    $agency->whereRaw('LOWER(name) = ?', [Str::lower($batch->region)]);
                });
            })
            ->whereNotIn('id', $sameTermRecipientScholarIds)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(spas_no) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('profile', function ($profile) use ($search) {
                            $profile->whereRaw("LOWER(CONCAT_WS(' ', fname, mname, lname, suffix)) LIKE ?", ["%{$search}%"]);
                        });
                });
            })
            ->when($program, function ($query) use ($program) {
                $query->whereHas('program', function ($programQuery) use ($program) {
                    $programQuery->whereRaw('LOWER(name) = ?', [$program]);
                });
            })
            ->when($university, function ($query) use ($university) {
                $query->whereHas('schoolInfo.campus', function ($campusQuery) use ($university) {
                    $campusQuery->whereRaw('LOWER(COALESCE(generated_name, name)) = ?', [$university]);
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->whereRaw('LOWER(academic_status) = ?', [$status]);
            })
            ->paginate(10, ['*'], 'eligible_page')
            ->through(fn($scholar) => [
                'id' => Hashids::encode($scholar->id),
                'spas_no' => $scholar->spas_no,
                'name' => trim(collect([
                    $scholar->profile?->lname,
                    $scholar->profile?->fname,
                    $scholar->profile?->mname,
                    $scholar->profile?->suffix,
                ])->filter()->join(' ')),
                'email' => $scholar->profile?->email,
                'birthday' => $scholar->profile?->birthdate,
                'account_no' => $scholar->landbank?->account_number,
                'program' => $scholar->program?->name,
                'university' => $scholar->schoolInfo->first()?->campus?->generated_name
                    ?? $scholar->schoolInfo->first()?->campus?->name,
                'status' => $scholar->academic_status ?? 'Ongoing',
            ]);
    }

    private function payrollRecipients(string $hashId)
    {
        $batchId = Hashids::decode($hashId)[0] ?? 0;

        return BatchRecipients::with([
            'scholar.profile:scholar_id,fname,mname,lname,suffix,email,birthdate',
            'scholar.program:id,name',
            'scholar.schoolInfo' => fn($q) => $q
                ->select('id', 'scholar_id', 'campus_id')
                ->latest('id')
                ->with('campus:id,name,generated_name,agency_id'),
            'stipends' => fn($q) => $q->orderBy('month_no'),
            'withhelds' => fn($q) => $q->orderBy('month_no'),
            'allowances',
        ])
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->get()
            ->map(function ($recipient) {
                $learningMaterials = $recipient->allowances
                    ->firstWhere('classification', 'connectivity')?->amount
                    ?? $recipient->learning_materials_amount;
                $clothing = $recipient->allowances
                    ->firstWhere('classification', 'clothing')?->amount
                    ?? $recipient->clothing_amount;

                return [
                    'id' => Hashids::encode($recipient->id),
                    'scholar_id' => Hashids::encode($recipient->scholar_id),
                    'spas_no' => $recipient->scholar?->spas_no,
                    'account_no' => $recipient->account_no,
                    'name' => trim(collect([
                        $recipient->scholar?->profile?->lname,
                        $recipient->scholar?->profile?->fname,
                        $recipient->scholar?->profile?->mname,
                        $recipient->scholar?->profile?->suffix,
                    ])->filter()->join(' ')),
                    'program' => $recipient->scholar?->program?->name,
                    'university' => $recipient->scholar?->schoolInfo->first()?->campus?->generated_name
                        ?? $recipient->scholar?->schoolInfo->first()?->campus?->name,
                    'scholarship_status' => $recipient->scholarship_status ?? $recipient->scholar?->academic_status ?? 'Ongoing',
                    'period' => $recipient->period,
                    'months' => collect(range(1, 5))->mapWithKeys(fn($month) => [
                        "month_{$month}" => (float) ($recipient->stipends->firstWhere('month_no', $month)?->amount ?? 0),
                    ]),
                    'total_withheld' => (float) $recipient->total_withheld,
                    'remarks' => $recipient->remarks,
                    'learning_materials_amount' => (float) $learningMaterials,
                    'clothing_amount' => (float) $clothing,
                    'grand_total' => (float) $recipient->grand_total,
                    'status' => $recipient->status,
                ];
            });
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $data = $request->validate([
                'region' => ['required', 'array'],
                'region.name' => ['required', 'string'],
                'term' => ['required', 'array'],
                'term.id' => ['required', 'integer', 'exists:list_references,id'],
                'term.term_name' => ['nullable', 'string'],
                'term.name' => ['required', 'string'],
                'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
                'batch' => ['required', 'string'],
            ]);


            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = Str::lower(Str::trim($value));
                }
            }

            $years = explode('-', $data['academic_year']);
            $result = substr($years[0], -2) . substr($years[1], -2);
            $termName = $data['term']['term_name'] ?? $data['term']['name'];
            $name =  Auth::user()->profile?->agency?->code . '_' . Str::of($termName)->replace(' ', '') . 'AY' .   $result . '_Batch' . $data['batch'];

            $parent = Batches::create([
                'region'        => $data['region']['name'],
                'academic_term' => $termName,
                'term_id'       => $data['term']['id'],
                'school_year'   => $data['academic_year'],
                'name'          => $name
            ]);

            $parent->logs()->create([
                'action_by' => Auth::user()->profile?->fullname
            ]);


            DB::commit();
            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title'  => 'Batch created',
                'message' => 'Batch successfully created.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title'  => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id, $type)
    {
        if ($type !== 'status') {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Invalid action',
                'message' => 'The requested stipend action is not supported.',
            ]);
        }

        $batchId = Hashids::decode($id)[0] ?? 0;
        $data = $request->validate([
            'status' => ['required', 'in:submitted_payroll,rejected_payroll,approved_payroll'],
            'remarks' => ['required_if:status,rejected_payroll', 'nullable', 'string'],
        ]);

        $batch = Batches::findOrFail($batchId);
        $latestStatus = $batch->logs()->latest('created_at')->value('status') ?? 'draft';

        if ($latestStatus === 'approved_payroll') {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Batch locked',
                'message' => 'Verified payroll batches can no longer be changed.',
            ]);
        }

        $batch->logs()->create([
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
            'action_by' => Auth::user()->profile?->fullname,
        ]);

        $this->syncBatchRecipientTermStatuses($batch, $data['status']);
        $this->syncBatchFinancialStatuses($batch, $data['status']);

        return redirect()->back()->with('flash', [
            'status' => 'success',
            'title' => 'Batch status updated',
            'message' => 'The payroll batch status was updated.',
        ]);
    }

    public function addRecipients(Request $request, string $id): RedirectResponse
    {
        $batchId = Hashids::decode($id)[0] ?? 0;
        $batch = Batches::findOrFail($batchId);
        $latestStatus = $batch->logs()->latest('created_at')->value('status') ?? 'draft';

        if (!in_array($latestStatus, $this->payrollEditableStatuses(), true)) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Batch locked',
                'message' => 'Submitted or verified payroll batches cannot be edited.',
            ]);
        }

        $data = $request->validate([
            'scholar_ids' => ['required', 'array', 'min:1'],
            'scholar_ids.*' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();
            $addedCount = 0;
            $attachedCount = 0;

            foreach ($data['scholar_ids'] as $hashScholarId) {
                $scholarId = Hashids::decode($hashScholarId)[0] ?? null;
                if (!$scholarId) {
                    continue;
                }

                $scholar = Scholars::with(['profile', 'landbank'])->find($scholarId);
                if (!$scholar) {
                    continue;
                }

                $alreadyInSameTermPayroll = BatchRecipients::where('scholar_id', $scholar->id)
                    ->where('batch_id', '!=', $batch->id)
                    ->whereHas('batch', function ($query) use ($batch) {
                        $query->where('region', $batch->region)
                            ->where('school_year', $batch->school_year)
                            ->whereNull('deleted_at');

                        if ($batch->term_id) {
                            $query->where('term_id', $batch->term_id);
                        } else {
                            $query->where('academic_term', $batch->academic_term);
                        }

                        if ($batch->level_id) {
                            $query->where('level_id', $batch->level_id);
                        }
                    })
                    ->exists();

                if ($alreadyInSameTermPayroll) {
                    continue;
                }

                $recipient = BatchRecipients::firstOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'scholar_id' => $scholar->id,
                    ],
                    [
                        'account_no' => $scholar->landbank?->account_number,
                        'birthday' => $scholar->profile?->birthdate,
                        'period' => trim($batch->academic_term . ' AY ' . $batch->school_year),
                        'scholarship_status' => $scholar->academic_status ?? 'Ongoing',
                        'status' => 'pending',
                    ]
                );

                if ($recipient->wasRecentlyCreated) {
                    $addedCount++;
                }
                $attachedCount++;

                foreach (range(1, 5) as $month) {
                    $recipient->stipends()->firstOrCreate(
                        ['month_no' => $month],
                        [
                            'month' => 'Month ' . $month,
                            'amount' => 0,
                            'status' => 'pending',
                        ]
                    );
                }

                $this->updateScholarTermPayrollStatus($batch, $scholar->id, $latestStatus);
            }

            if ($attachedCount === 0) {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'No scholars added',
                    'message' => 'The selected scholars may already be included in this payroll or another payroll for the same term and year.',
                ]);
            }

            DB::commit();

            $message = $addedCount > 0
                ? "{$addedCount} scholar(s) were added to the payroll."
                : 'The selected scholar(s) are already attached to this payroll.';

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Scholars added',
                'message' => $message,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Failed to add scholars to stipend payroll.', [
                'batch_id' => $batch->id ?? null,
                'scholar_ids' => $data['scholar_ids'] ?? [],
                'message' => $th->getMessage(),
            ]);

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function savePayroll(Request $request, string $id): RedirectResponse
    {
        $batchId = Hashids::decode($id)[0] ?? 0;
        $batch = Batches::findOrFail($batchId);
        $latestStatus = $batch->logs()->latest('created_at')->value('status') ?? 'draft';

        if (!in_array($latestStatus, $this->payrollEditableStatuses(), true)) {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Batch locked',
                'message' => 'Submitted or verified payroll batches cannot be edited.',
            ]);
        }

        $data = $request->validate([
            'recipients' => ['required', 'array'],
            'recipients.*.id' => ['required', 'string'],
            'recipients.*.account_no' => ['nullable', 'string'],
            'recipients.*.scholarship_status' => ['nullable', 'string'],
            'recipients.*.period' => ['nullable', 'string'],
            'recipients.*.month_1' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.month_2' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.month_3' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.month_4' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.month_5' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.total_withheld' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.remarks' => ['nullable', 'string'],
            'recipients.*.learning_materials_amount' => ['nullable', 'numeric', 'min:0'],
            'recipients.*.clothing_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            foreach ($data['recipients'] as $item) {
                $recipientId = Hashids::decode($item['id'])[0] ?? 0;
                $recipient = BatchRecipients::where('batch_id', $batchId)->findOrFail($recipientId);

                $totalStipend = 0;
                foreach (range(1, 5) as $month) {
                    $amount = (float) ($item["month_{$month}"] ?? 0);
                    $totalStipend += $amount;

                    RecipientStipend::updateOrCreate(
                        [
                            'recipient_id' => $recipient->id,
                            'month_no' => $month,
                        ],
                        [
                            'month' => 'Month ' . $month,
                            'amount' => $amount,
                            'status' => $amount > 0 ? 'pending' : 'withheld',
                        ]
                    );
                }

                $totalWithheld = (float) ($item['total_withheld'] ?? 0);
                $learningMaterials = (float) ($item['learning_materials_amount'] ?? 0);
                $clothing = (float) ($item['clothing_amount'] ?? 0);
                $grandTotal = $totalStipend + $totalWithheld + $learningMaterials + $clothing;

                $recipient->update([
                    'account_no' => $item['account_no'] ?? null,
                    'scholarship_status' => $item['scholarship_status'] ?? null,
                    'period' => $item['period'] ?? null,
                    'total_stipend' => $totalStipend,
                    'total_withheld' => $totalWithheld,
                    'learning_materials_amount' => $learningMaterials,
                    'clothing_amount' => $clothing,
                    'grand_total' => $grandTotal,
                    'remarks' => $item['remarks'] ?? null,
                ]);

                foreach ([
                    'connectivity' => $learningMaterials,
                    'clothing' => $clothing,
                ] as $classification => $amount) {
                    if ($amount <= 0) {
                        RecipientAllowance::where('recipient_id', $recipient->id)
                            ->where('classification', $classification)
                            ->delete();

                        continue;
                    }

                    RecipientAllowance::updateOrCreate(
                        [
                            'recipient_id' => $recipient->id,
                            'classification' => $classification,
                        ],
                        [
                            'amount' => $amount,
                            'remarks' => $item['remarks'] ?? null,
                            'status' => 'pending',
                        ]
                    );
                }

                if ($totalWithheld > 0) {
                    RecipientWithheld::updateOrCreate(
                        ['recipient_id' => $recipient->id, 'month_no' => null],
                        [
                            'total_amount' => $totalWithheld,
                            'remarks' => $item['remarks'] ?? null,
                            'status' => 'pending',
                        ]
                    );
                } else {
                    RecipientWithheld::where('recipient_id', $recipient->id)
                        ->whereNull('month_no')
                        ->delete();
                }
            }

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Payroll saved',
                'message' => 'Payroll amounts were updated.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function destroy($id, $type)
    {
        if ($type === 'batch') {
            $batchId = Hashids::decode($id)[0] ?? 0;

            try {
                DB::beginTransaction();

                $batch = Batches::with('recipients.stipends', 'recipients.withhelds', 'recipients.allowances')->findOrFail($batchId);
                $latestStatus = $batch->logs()->latest('created_at')->value('status') ?? 'draft';

                if (!in_array($latestStatus, $this->payrollEditableStatuses(), true)) {
                    DB::rollBack();

                    return redirect()->back()->with('flash', [
                        'status' => 'error',
                        'title' => 'Batch locked',
                        'message' => 'Submitted or verified payroll batches cannot be deleted.',
                    ]);
                }

                foreach ($batch->recipients as $recipient) {
                    if ($recipient->scholar_id) {
                        $this->resetScholarTermToSubmitted($batch, $recipient->scholar_id);
                    }

                    $recipient->stipends()->delete();
                    $recipient->withhelds()->delete();
                    $recipient->allowances()->delete();
                    $recipient->delete();
                }

                $batch->logs()->delete();
                $batch->delete();

                DB::commit();

                return redirect()->back()->with('flash', [
                    'status' => 'success',
                    'title' => 'Batch deleted',
                    'message' => 'The payroll batch was deleted.',
                ]);
            } catch (\Throwable $th) {
                DB::rollBack();

                return redirect()->back()->with('flash', [
                    'status' => 'error',
                    'title' => 'Something went wrong.',
                    'message' => $th->getMessage(),
                ]);
            }
        }

        if ($type !== 'recipient') {
            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Invalid action',
                'message' => 'The requested stipend action is not supported.',
            ]);
        }

            $recipientId = Hashids::decode($id)[0] ?? 0;

            try {
                DB::beginTransaction();

                $recipient = BatchRecipients::findOrFail($recipientId);
                $latestStatus = $recipient->batch?->logs()->latest('created_at')->value('status') ?? 'draft';

                if (!in_array($latestStatus, $this->payrollEditableStatuses(), true)) {
                    DB::rollBack();

                    return redirect()->back()->with('flash', [
                        'status' => 'error',
                        'title' => 'Batch locked',
                        'message' => 'Submitted or verified payroll batches cannot be edited.',
                    ]);
                }
            $batch = $recipient->batch;
            if ($batch && $recipient->scholar_id) {
                $this->resetScholarTermToSubmitted($batch, $recipient->scholar_id);
            }

            $recipient->stipends()->delete();
            $recipient->withhelds()->delete();
            $recipient->allowances()->delete();
            $recipient->delete();

            DB::commit();

            return redirect()->back()->with('flash', [
                'status' => 'success',
                'title' => 'Scholar removed',
                'message' => 'The scholar was removed from this payroll.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('flash', [
                'status' => 'error',
                'title' => 'Something went wrong.',
                'message' => $th->getMessage(),
            ]);
        }
    }
}
