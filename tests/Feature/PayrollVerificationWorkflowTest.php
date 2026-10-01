<?php

namespace Tests\Feature;

use App\Models\ListRole;
use App\Models\User;
use App\Services\Payroll\PayrollStatusService;
use App\Support\SystemPermissions;
use Tests\TestCase;

class PayrollVerificationWorkflowTest extends TestCase
{
    public function test_scholarship_staff_can_verify_only_the_submitted_stage(): void
    {
        $permissions = app(SystemPermissions::class);
        $user = $this->userWithRole('Scholarship Staff');

        $this->assertTrue($permissions->canVerifyPayroll($user, 'submitted_payroll'));
        $this->assertFalse($permissions->canVerifyPayroll($user, 'verified_payroll'));
        $this->assertFalse($permissions->canApprovePayroll($user, 'verified_payroll'));
        $this->assertTrue($permissions->canReviewPayroll($user, 'submitted_payroll'));
        $this->assertFalse($permissions->canReviewPayroll($user, 'verified_payroll'));
    }

    public function test_scholarship_coordinator_can_approve_only_the_verified_stage(): void
    {
        $permissions = app(SystemPermissions::class);
        $user = $this->userWithRole('Scholarship Coordinator');

        $this->assertFalse($permissions->canApprovePayroll($user, 'submitted_payroll'));
        $this->assertTrue($permissions->canApprovePayroll($user, 'verified_payroll'));
        $this->assertFalse($permissions->canVerifyPayroll($user, 'submitted_payroll'));
        $this->assertFalse($permissions->canReviewPayroll($user, 'submitted_payroll'));
        $this->assertTrue($permissions->canReviewPayroll($user, 'verified_payroll'));
    }

    public function test_verified_payroll_keeps_submitted_downstream_statuses(): void
    {
        $statuses = app(PayrollStatusService::class);

        $this->assertSame('SUBMITTED', $statuses->processStatus('verified_payroll'));
        $this->assertSame('submitted', $statuses->itemStatus('verified_payroll'));
        $this->assertSame('APPROVED', $statuses->processStatus('approved_payroll'));
        $this->assertSame('approved', $statuses->itemStatus('approved_payroll'));
    }

    private function userWithRole(string $roleName): User
    {
        $user = new User;
        $user->setAttribute('id', strtolower(str_replace(' ', '-', $roleName)));
        $user->setRelation('role', new ListRole(['name' => $roleName]));

        return $user;
    }
}
