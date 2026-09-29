<?php

namespace App\Services\Scholar\Management;

use App\Models\Scholars;
use App\Models\ScholarTerm;
use App\Support\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;

class ScholarManagementListService
{
    public function paginate(Request $request, SystemPermissions $permissions, $user, array $monitoring, $academicStatusOptions)
    {
        $monitoringAcademicYear = $monitoring['academicYear'];
        $monitoringTermId = $monitoring['termId'];
        $monitoringSubmissionStatus = $monitoring['submissionStatus'];

        $scholars = Scholars::select(
            'scholars.id',
            'scholars.spas_no',
            'scholars.status_id',
            'scholars.academic_status',
            'scholars.program_id',
            'scholars.category_id',
            'scholars.type_id',
            'scholars.award_year',
            'scholars.activated_at',
            'scholars.activation_token'
        )
            ->join('scholar_profiles', 'scholar_profiles.scholar_id', '=', 'scholars.id')
            ->with([
                'status:id,name,icon,color_id',
                'status.color:id,background_color,text_color',
                'program:id,name',
                'mainProgram:id,name',
                'type:id,name',
                'profile:id,scholar_id,photo,sex,fname,lname,mname,suffix,email,contact_no',
                'schoolInfo' => fn ($q) => $q
                    ->select('id', 'scholar_id', 'campus_id', 'campus_course_id')
                    ->with([
                        'campus:id,generated_name,agency_id',
                        'campus.agency:id,name,slug',
                        'campus.address:campus_id,region_code',
                        'course' => fn ($q) => $q
                            ->select('id', 'course_id')
                            ->with([
                                'course:id,name',
                            ]),
                    ])
                    ->latest()
                    ->limit(1),

            ])
            ->when($request->input('search'), fn ($q) => $q->whereHas(
                'profile',
                fn ($q) => $q->whereRaw("CONCAT(lname, ' ', fname, ' ', COALESCE(mname, '')) ILIKE ?", ['%'.$request->input('search').'%'])
                    ->orWhere('spas_no', 'ILIKE', '%'.$request->input('search').'%')
                    ->orWhere('lname', 'ILIKE', '%'.$request->input('search').'%')
                    ->orWhere('fname', 'ILIKE', '%'.$request->input('search').'%')
            ))
            ->when($request->input('schools'), function ($q, $schools) {
                $q->whereHas('schoolInfo', fn ($w) => $w->whereHas('campus', fn ($r) => $r->whereIn('generated_name', $schools)));
            })
            ->when($permissions->isSchoolCoordinator($user), function ($q) use ($user) {
                $q->whereHas('schoolInfo', fn ($w) => $w->whereHas('campus', fn ($r) => $r->where('id', $user->school_id)));
            })
            ->when($permissions->shouldScopeToRegion($user), function ($q) use ($permissions, $user) {
                $q->whereHas('schoolInfo.campus.address', function ($address) use ($permissions, $user) {
                    $address->where('region_code', $permissions->regionCodeFor($user));
                });
            })
            ->when($request->input('programs'), function ($q, $programs) {
                $q->whereHas('program', fn ($w) => $w->whereIn('name', $programs));
            })
            ->when($request->input('sub'), function ($q, $sub) {
                $q->whereHas('type', fn ($w) => $w->whereIn('name', $sub));
            })
            ->when($request->input('status'), function ($q, $status) {
                $statuses = collect($status)
                    ->map(fn ($item) => Str::upper(is_array($item) ? ($item['id'] ?? $item['name'] ?? '') : $item))
                    ->filter()
                    ->values()
                    ->all();

                $q->whereIn(DB::raw('UPPER(academic_status)'), $statuses);
            })
            ->when($monitoringAcademicYear && $monitoringTermId && $monitoringSubmissionStatus !== 'all', function ($q) use ($monitoringAcademicYear, $monitoringTermId, $monitoringSubmissionStatus) {
                if ($monitoringSubmissionStatus === 'No Submission') {
                    $q->whereDoesntHave('termRecords', function ($term) use ($monitoringAcademicYear, $monitoringTermId) {
                        $term->where('academic_year', $monitoringAcademicYear)
                            ->where('term_id', $monitoringTermId);
                    });

                    return;
                }

                $q->whereHas('termRecords', function ($term) use ($monitoringAcademicYear, $monitoringTermId, $monitoringSubmissionStatus) {
                    $term->where('academic_year', $monitoringAcademicYear)
                        ->where('term_id', $monitoringTermId)
                        ->where('verification_status', $monitoringSubmissionStatus);
                });
            })
            ->orderBy('scholar_profiles.lname', 'ASC')
            ->paginate(10);

        $scholarIds = $scholars->getCollection()->pluck('id')->all();
        $latestTerms = $this->latestTermsByScholar($scholarIds);
        $monitoringTerms = $this->monitoringTermsByScholar($scholarIds, $monitoringAcademicYear, $monitoringTermId);
        $processStatuses = $this->processStatusesByTerm($monitoringTerms->pluck('id')->all());

        return $scholars->through(function ($q) use ($monitoringTerms, $latestTerms, $processStatuses, $academicStatusOptions) {
                $monitoringTermRecord = $monitoringTerms->get($q->id);
                $latestTermRecord = $latestTerms->get($q->id);
                $schoolInfo = $q->schoolInfo?->first();
                $progressStatus = Str::upper($q->academic_status ?: 'NEW');
                $progressStatusOption = $academicStatusOptions->firstWhere('name', $progressStatus);

                return [
                    'id' => Hashids::encode($q->id),
                    'spas_no' => $q->spas_no,
                    'photo' => $q->profile?->photo,
                    'email' => $q->profile?->email,
                    'contact_no' => $q->profile?->contact_no,
                    'sex' => $q->profile?->sex,
                    'activated_at' => $q->activated_at,
                    'activationRequested' => ! empty($q->activation_token),
                    'fullname' => trim(collect([
                        $q->profile?->lname.',',
                        $q->profile?->fname,
                        $q->profile?->mname,
                        $q->profile?->suffix,
                    ])->filter()->implode(' ')),
                    'type' => $q->type?->name,
                    'subProgram' => $q->program?->name,
                    'mainProgram' => $q->mainProgram?->name,
                    'status' => $this->academicStatusMeta($progressStatus, $progressStatusOption),
                    'submissionStatus' => $this->submissionStatusMeta($monitoringTermRecord?->verification_status),
                    'scholarshipStatus' => $monitoringTermRecord ? $processStatuses->get($monitoringTermRecord->id) : null,
                    'term' => $latestTermRecord?->toArray(),
                    'course' => $schoolInfo?->course?->course?->name,
                    'school' => $schoolInfo?->campus?->generated_name,
                    'agency' => $schoolInfo?->campus?->agency?->slug,
                    'region' => $schoolInfo?->campus?->address?->region_array,
                ];
            });
    }

    private function latestTermsByScholar(array $scholarIds)
    {
        if (empty($scholarIds)) {
            return collect();
        }

        return ScholarTerm::whereIn('scholar_id', $scholarIds)
            ->orderBy('scholar_id')
            ->orderByDesc('id')
            ->get()
            ->unique('scholar_id')
            ->keyBy('scholar_id');
    }

    private function monitoringTermsByScholar(array $scholarIds, ?string $academicYear, int|string|null $termId)
    {
        if (empty($scholarIds) || ! $academicYear || ! $termId) {
            return collect();
        }

        return ScholarTerm::whereIn('scholar_id', $scholarIds)
            ->where('academic_year', $academicYear)
            ->where('term_id', $termId)
            ->orderBy('scholar_id')
            ->orderByDesc('id')
            ->get()
            ->unique('scholar_id')
            ->keyBy('scholar_id');
    }

    private function processStatusesByTerm(array $termRecordIds)
    {
        if (empty($termRecordIds)) {
            return collect();
        }

        return DB::connection('scholars')
            ->table('scholar_processes')
            ->whereIn('term_record_id', $termRecordIds)
            ->pluck('scholarship_status', 'term_record_id');
    }

    private function academicStatusMeta(?string $status, ?array $statusOption = null): array
    {
        $status = Str::upper($status ?: 'NEW');

        return [
            'id' => $status,
            'name' => $status,
            'bcolor' => $statusOption['bcolor'] ?? 'bg-slate-100',
            'tcolor' => $statusOption['tcolor'] ?? 'text-slate-600',
            'icon' => $statusOption['icon'] ?? 'IconProgressCheck',
        ];
    }

    private function submissionStatusMeta(?string $status): array
    {
        $status = $status ?: 'No Submission';

        return [
            'id' => $status,
            'name' => Str::headline($status),
            'bcolor' => match ($status) {
                'submitted' => 'bg-blue-100',
                'approved' => 'bg-green-100',
                'rejected' => 'bg-red-100',
                'draft' => 'bg-slate-100',
                default => 'bg-gray-100',
            },
            'tcolor' => match ($status) {
                'submitted' => 'text-blue-600',
                'approved' => 'text-green-600',
                'rejected' => 'text-red-600',
                'draft' => 'text-slate-600',
                default => 'text-gray-500',
            },
            'icon' => match ($status) {
                'submitted' => 'IconUpload',
                'approved' => 'IconCircleCheck',
                'rejected' => 'IconCircleX',
                'draft' => 'IconEdit',
                default => 'IconCircleDashed',
            },
        ];
    }
}
