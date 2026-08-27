<?php

namespace App\Services\Payroll;

use App\Models\BatchRecipients;
use App\Models\Batches;
use App\Models\PayrollBatchActivityLog;
use App\Support\SystemPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PayrollActivityService
{
    public function log(
        Batches $batch,
        string $action,
        ?BatchRecipients $recipient = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $remarks = null,
        array $metadata = []
    ): void {
        PayrollBatchActivityLog::create([
            'batch_id' => $batch->id,
            'batch_recipient_id' => $recipient?->id,
            'scholar_id' => $recipient?->scholar_id,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'remarks' => $remarks,
            'metadata' => $metadata ?: null,
            'created_by' => Auth::user()?->profile?->fullname,
        ]);
    }

    public function visibleLogs(Batches $batch)
    {
        $user = Auth::user();
        $permissions = app(SystemPermissions::class);

        $query = $batch->activityLogs()
            ->with('scholar.profile:scholar_id,fname,mname,lname,suffix')
            ->orderBy('created_at', 'desc');

        if ($permissions->isAdministrator($user)) {
            return $query->limit(25)->get();
        }

        if ($permissions->isRegionalRole($user)) {
            $query->whereIn('action', [
                'batch_created',
                'scholars_added',
                'scholar_moved_from_returned_payroll',
                'payroll_saved',
                'payroll_submitted',
                'payroll_returned',
                'payroll_month_credited',
                'scholar_removed',
            ]);
        } elseif ($permissions->isScholarshipReviewer($user)) {
            $query->whereIn('action', [
                'payroll_submitted',
                'payroll_returned',
                'payroll_approved',
                'scholar_marked_for_removal',
                'scholar_moved_from_returned_payroll',
                'scholar_removal_cancelled',
            ]);
        } elseif ($permissions->isCashier($user)) {
            $query->whereIn('action', [
                'payroll_approved',
                'payroll_month_credited',
            ]);
        }

        return $query->limit(25)->get();
    }

    public function label(string $action): string
    {
        return match ($action) {
            'batch_created' => 'Batch created',
            'scholars_added' => 'Scholars added',
            'payroll_saved' => 'Payroll saved',
            'payroll_submitted' => 'Payroll submitted',
            'payroll_approved' => 'Payroll approved',
            'payroll_imported' => 'Historical payroll imported',
            'payroll_returned' => 'Payroll returned',
            'payroll_month_credited' => 'Payroll month credited',
            'scholar_marked_for_removal' => 'Scholar marked for removal',
            'scholar_moved_from_returned_payroll' => 'Scholar moved from returned payroll',
            'scholar_removal_cancelled' => 'Scholar removal cancelled',
            'scholar_removed' => 'Scholar removed from draft payroll',
            default => Str::of($action)->replace('_', ' ')->headline()->toString(),
        };
    }
}
