<?php

namespace App\Services\Scholar\Management;

use App\Models\Scholars;
use App\Models\StudentDocument;
use App\Support\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class ScholarManagementDetailsService
{
    public function find(string $hashId, SystemPermissions $permissions, $user): ?array
    {
        $id = Hashids::decode($hashId)[0] ?? 0;
        $scholar = $this->query($id, $permissions, $user);

        if (! $scholar) {
            return null;
        }

        $schoolInfo = $scholar->schoolInfo?->first();
        $payrolls = $scholar->payrolls;
        $allowances = $payrolls->flatMap->allowances;

        return [
            'id' => Hashids::encode($scholar->id),
            'spas_no' => $scholar?->spas_no,
            'type' => [
                'id' => $scholar?->type?->id,
                'name' => $scholar?->type?->name,
            ],
            'program' => [
                'id' => $scholar?->program?->id,
                'name' => $scholar?->program?->name,
            ],
            'email' => $scholar?->profile?->email,
            'contact_no' => $scholar?->profile?->contact_no,
            'fname' => $scholar?->profile?->fname,
            'mname' => $scholar?->profile?->mname,
            'lname' => $scholar?->profile?->lname,
            'suffix' => $scholar?->profile?->suffix,
            'birthplace' => $scholar?->profile?->birthplace,
            'birthdate' => Carbon::parse($scholar?->profile?->birthdate)->format('Y-m-d'),
            'religion' => $scholar?->profile?->religion,
            'civil_status' => $scholar?->profile?->civil_status,
            'fullname' => trim(collect([
                $scholar?->profile?->lname.',',
                $scholar?->profile?->fname,
                $scholar?->profile?->mname,
                $scholar?->profile?->suffix,
            ])->filter()->implode(' ')),
            'academic_status' => Str::upper($scholar?->academic_status ?? 'NEW'),
            'status' => [
                'id' => Str::upper($scholar?->academic_status ?? 'NEW'),
                'name' => Str::upper($scholar?->academic_status ?? 'NEW'),
            ],
            'address' => [
                'address' => $scholar?->address?->address,
                'province' => $scholar?->address?->province_array,
                'region' => $scholar?->address?->region_array,
                'municipality' => $scholar?->address?->municipality_array,
                'barangay' => $scholar?->address?->barangay_array,
            ],
            'fullAddress' => $scholar?->address?->full_address,
            'awardYear' => $scholar?->award_year,
            'schoolInfoId' => $schoolInfo?->id,
            'course' => $schoolInfo?->course?->course?->name,
            'curriculum' => $schoolInfo?->curriculum
                ? 'Curriculum '.$schoolInfo->curriculum->years
                : null,
            'school' => $schoolInfo?->campus?->generated_name,
            'schoolInput' => [
                'id' => $schoolInfo?->campus?->id,
                'name' => $schoolInfo?->campus?->generated_name,
            ],
            'landbank' => [
                'has_account_name' => filled($scholar?->landbank?->account_name),
                'has_account_number' => filled($scholar?->landbank?->account_number),
            ],
            'courseInput' => [
                'id' => $schoolInfo?->course?->id,
                'name' => $schoolInfo?->course?->course?->name,
                'campus' => $schoolInfo?->campus?->generated_name,
            ],
            'curriculumInput' => [
                'id' => $schoolInfo?->curriculum?->id,
                'name' => $schoolInfo?->curriculum
                    ? 'Curriculum '.$schoolInfo->curriculum->years
                    : null,
            ],
            'region' => $schoolInfo?->campus?->address?->region_array,
            'guardian' => [
                'name' => $scholar?->parent?->fname,
                'id_no' => $scholar?->parent?->id_no,
                'place_issue' => $scholar?->parent?->id_place,
                'date_issue' => $scholar?->parent?->id_date,
            ],
            'logs' => $this->logs($scholar, false),
            'academicLogs' => $this->logs($scholar, true),
            'termGrades' => $this->termGrades($scholar),
            'financialAid' => [
                'grandTotal' => number_format($payrolls->sum('grand_total'), 2),
                'approvedTotal' => number_format($payrolls->where('status', 'approved')->sum('grand_total'), 2),
                'totalWithheld' => number_format($payrolls->sum('total_withheld'), 2),
                'clothing' => number_format($allowances->filter(fn ($allowance) => $allowance->allowanceType?->code === 'clothing')->sum('amount'), 2),
                'connectivity' => number_format($allowances->filter(fn ($allowance) => $allowance->allowanceType?->code === 'connectivity')->sum('amount'), 2),
                'totalAllowances' => number_format($allowances->sum('amount'), 2),
                'monthly' => $this->monthlyPayrolls($payrolls),
            ],
        ];
    }

    public function revealLandbank(string $hashId, SystemPermissions $permissions, $user): ?array
    {
        $id = Hashids::decode($hashId)[0] ?? 0;
        $scholar = $this->query($id, $permissions, $user);

        if (! $scholar) {
            return null;
        }

        return [
            'account_name' => $scholar->landbank?->account_name,
            'account_number' => $scholar->landbank?->account_number,
            'activity_logs' => $scholar->logs
                ->where('request_type', 'landbank')
                ->values()
                ->mapWithKeys(fn ($log) => [
                    $log->id => [
                        'previous' => $log->previous_formatted,
                        'changes' => $log->changes_formatted,
                    ],
                ])
                ->all(),
        ];
    }

    private function query(int $id, SystemPermissions $permissions, $user): ?Scholars
    {
        return Scholars::select(
            'scholars.id',
            'scholars.spas_no',
            'scholars.status_id',
            'scholars.academic_status',
            'scholars.program_id',
            'scholars.type_id',
            'scholars.award_year'
        )
            ->join('scholar_profiles', 'scholar_profiles.scholar_id', '=', 'scholars.id')
            ->with([
                'parent',
                'status:id,name,icon,color_id',
                'status.color:id,background_color,text_color',
                'address:id,scholar_id,region_code,province_code,municipality_code,barangay_code,address',
                'program:id,name',
                'type:id,name',
                'profile:id,scholar_id,photo,sex,fname,lname,mname,suffix,email,contact_no,birthplace,birthdate,religion,civil_status',
                'schoolInfo' => fn ($q) => $q
                    ->select('id', 'scholar_id', 'campus_id', 'campus_course_id', 'curriculum_id')
                    ->with([
                        'campus:id,generated_name,agency_id',
                        'campus.agency:id,name,slug',
                        'campus.address:campus_id,region_code',
                        'curriculum:id,years',
                        'course' => fn ($q) => $q
                            ->select('id', 'course_id')
                            ->with([
                                'course:id,name',
                            ]),
                    ])
                    ->latest()
                    ->limit(1),
                'termRecords' => fn ($q) => $q
                    ->select('id', 'scholar_id', 'term_id', 'level_id', 'academic_year', 'scholar_school_id', 'term_type_id', 'verification_status')
                    ->where('verification_status', 'approved')
                    ->with([
                        'termType:id,name',
                        'term:id,name',
                        'level:id,name,others',
                        'schoolInfo.campus:id,generated_name',
                        'schoolInfo.course.course:id,name',
                        'subjects' => fn ($q) => $q
                            ->select('id', 'term_record_id', 'subject_id', 'grade_id', 'remarks')
                            ->where('is_deleted', false)
                            ->with([
                                'subject:id,name,year,subject_code,unit,subject_class,semester_id',
                                'grade:id,grade,is_failed,is_incomplete,is_drop,is_active',
                            ]),
                    ]),
                'logs',
                'landbank',
                'payrolls' => fn ($q) => $q
                    ->with([
                        'logs',
                        'stipends',
                        'allowances.allowanceType',
                    ])
                    ->orderBy('created_at', 'desc'),
            ])
            ->where('scholars.id', $id)
            ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                    $address->where('region_code', $permissions->regionCodeFor($user));
                });
            })
            ->first();
    }

    private function logs(Scholars $scholar, bool $academic)
    {
        return $scholar->logs
            ->when($academic, fn ($logs) => $logs->where('request_type', 'academic'), fn ($logs) => $logs->where('request_type', '!=', 'academic'))
            ->sortByDesc('created_at')
            ->take(50)
            ->values()
            ->map(fn ($log) => [
                'id' => $log->id,
                'created_by' => $log->created_by,
                'previous' => $this->maskSensitiveLogData($log->request_type, $log->previous_formatted),
                'changes' => $this->maskSensitiveLogData($log->request_type, $log->changes_formatted),
                'type' => $log->request_type,
                'has_sensitive_landbank' => $log->request_type === 'landbank' && $this->hasSensitiveLandbankLogData($log->previous_formatted, $log->changes_formatted),
                'date' => Carbon::parse($log->created_at)->format('M d, Y h:i A'),
            ]);
    }

    private function maskSensitiveLogData(?string $type, ?array $data): array
    {
        $data ??= [];

        if ($type !== 'landbank') {
            return $data;
        }

        foreach (['account_name', 'account_number'] as $field) {
            if (array_key_exists($field, $data) && filled($data[$field])) {
                $data[$field] = '**********************';
            }
        }

        return $data;
    }

    private function hasSensitiveLandbankLogData(?array $previous, ?array $changes): bool
    {
        foreach ([$previous ?? [], $changes ?? []] as $data) {
            foreach (['account_name', 'account_number'] as $field) {
                if (array_key_exists($field, $data) && filled($data[$field])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function termGrades(Scholars $scholar)
    {
        $termIds = $scholar->termRecords->pluck('id');
        $standings = DB::connection('scholars')
            ->table('scholar_processes')
            ->whereIn('term_record_id', $termIds)
            ->pluck('scholarship_status', 'term_record_id');
        $documents = StudentDocument::whereIn('term_record_id', $termIds)
            ->get()
            ->groupBy('term_record_id');

        return $scholar?->termRecords->sort(function ($left, $right) {
            $leftSort = [
                $this->academicYearSortValue($left?->academic_year),
                (int) ($left?->level?->others ?? 0),
                $this->termSortValue($left?->term?->name),
                (int) ($left?->id ?? 0),
            ];
            $rightSort = [
                $this->academicYearSortValue($right?->academic_year),
                (int) ($right?->level?->others ?? 0),
                $this->termSortValue($right?->term?->name),
                (int) ($right?->id ?? 0),
            ];

            return $rightSort <=> $leftSort;
        })->values()->map(function ($term) use ($standings, $documents) {
            $subjects = $term->subjects->map(function ($sub) {
                $grade = $sub->grade;
                $gradeValue = is_numeric($grade?->grade) ? (float) $grade->grade : null;
                $unit = is_numeric($sub->subject?->unit) ? (float) $sub->subject->unit : 0;
                $isAcademic = Str::lower($sub->subject?->subject_class ?? '') === 'academic';
                $isCounted = $isAcademic
                    && $gradeValue !== null
                    && ! ($grade?->is_drop || $grade?->is_incomplete);

                return [
                    'id' => $sub->id,
                    'subject' => [
                        'id' => $sub->subject?->id,
                        'name' => $sub->subject?->name,
                        'code' => $sub->subject?->subject_code,
                        'unit' => $sub->subject?->unit,
                        'class' => $sub->subject?->subject_class,
                    ],
                    'grade' => [
                        'id' => $sub->grade?->id,
                        'grade' => $sub->grade?->grade,
                        'is_failed' => $sub->grade?->is_failed,
                        'is_incomplete' => $sub->grade?->is_incomplete,
                        'is_drop' => $sub->grade?->is_drop,
                        'is_active' => $sub->grade?->is_active,
                    ],
                    'request' => [
                        'id' => null,
                        'grade' => null,
                        'is_failed' => null,
                        'is_incomplete' => null,
                        'is_drop' => null,
                        'is_active' => null,
                    ],
                    'total' => $isCounted ? round($gradeValue * $unit, 2) : null,
                    'remarks' => $sub->remarks,
                    'is_academic' => $isAcademic,
                    'is_counted' => $isCounted,
                ];
            });
            $countedSubjects = $subjects->where('is_counted', true);
            $totalUnits = $countedSubjects->sum(fn ($subject) => (float) ($subject['subject']['unit'] ?? 0));
            $totalGradePoints = $countedSubjects->sum(fn ($subject) => (float) ($subject['total'] ?? 0));

            return [
                'id' => $term->id,
                'term_id' => $term->term_id,
                'level_id' => $term->level_id,
                'schoolInfoId' => $term->scholar_school_id,
                'termType' => $term->term->name,
                'termInput' => [
                    'id' => $term->term?->id,
                    'name' => $term->term?->name,
                ],
                'levelInput' => [
                    'id' => $term->level?->id,
                    'name' => $term->level?->name,
                    'number' => $term->level?->others,
                ],
                'schoolInput' => [
                    'id' => $term->schoolInfo?->campus?->id,
                    'name' => $term->schoolInfo?->campus?->generated_name,
                ],
                'courseInput' => [
                    'id' => $term->schoolInfo?->course?->id,
                    'name' => $term->schoolInfo?->course?->course?->name,
                    'campus' => $term->schoolInfo?->campus?->generated_name,
                ],
                'files' => $documents->get($term->id, collect())->values(),
                'academic_year' => $term->academic_year,
                'gradeRequest' => false,
                'subjects' => $subjects,
                'summary' => [
                    'units' => $totalUnits,
                    'total' => round($totalGradePoints, 2),
                    'average' => $totalUnits > 0 ? number_format($totalGradePoints / $totalUnits, 2, '.', '') : null,
                ],
                'scholarshipStatus' => $standings->get($term->id),
            ];
        });
    }

    private function monthlyPayrolls($payrolls)
    {
        return $payrolls->map(function ($payroll) {
            return [
                'period' => $payroll->period,
                'status' => $payroll->status,
                'grandTotal' => number_format($payroll->grand_total, 2),
                'logs' => $payroll->logs->map(fn ($log) => [
                    'action' => $log->status,
                    'remarks' => $log->remarks,
                    'created_at' => Carbon::parse($log->created_at)->format('F d, Y h:i A'),
                    'created_by' => $log->action_by,
                ]),
                'stipends' => $payroll->stipends->map(fn ($stipend) => [
                    'month' => $stipend->month,
                    'amount' => number_format($stipend->amount, 2),
                ]),
                'financial' => $payroll->allowances->map(fn ($allowance) => [
                    'code' => $allowance->allowanceType?->code,
                    'name' => $allowance->allowanceType?->name,
                    'description' => $allowance->allowanceType?->description,
                    'amount' => number_format($allowance->amount, 2),
                ]),
                'totalStipends' => number_format($payroll->stipends->sum('amount'), 2),
            ];
        });
    }

    private function academicYearSortValue(?string $academicYear): int
    {
        if (preg_match('/\d{4}/', $academicYear ?? '', $matches)) {
            return (int) $matches[0];
        }

        return 0;
    }

    private function termSortValue(?string $term): int
    {
        $term = Str::lower($term ?? '');

        return match (true) {
            Str::contains($term, ['4th', 'fourth']) => 4,
            Str::contains($term, ['3rd', 'third']) => 3,
            Str::contains($term, ['2nd', 'second']) => 2,
            Str::contains($term, ['1st', 'first']) => 1,
            default => 0,
        };
    }
}
