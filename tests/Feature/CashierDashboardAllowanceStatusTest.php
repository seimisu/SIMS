<?php

namespace Tests\Feature;

use App\Models\Batches;
use App\Models\BatchRecipients;
use App\Models\RecipientAllowance;
use App\Models\User;
use App\Services\Dashboard\CashierDashboardService;
use App\Support\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CashierDashboardAllowanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summarizes_individual_allowance_deposits_separately(): void
    {
        $batch = Batches::create([
            'name' => 'Cashier dashboard allowance batch',
            'region' => 'Region 1',
            'academic_term' => 'First Semester',
            'school_year' => '2026-2027',
            'status' => 'approved_payroll',
        ]);
        $recipient = BatchRecipients::create([
            'batch_id' => $batch->id,
            'status' => 'approved_payroll',
        ]);

        RecipientAllowance::create([
            'recipient_id' => $recipient->id,
            'classification' => 'tuition',
            'amount' => 12000,
            'status' => 'approved_payroll',
            'deposit_status' => 'credited',
        ]);
        RecipientAllowance::create([
            'recipient_id' => $recipient->id,
            'classification' => 'transportation',
            'amount' => 3500,
            'status' => 'approved_payroll',
            'deposit_status' => 'pending',
        ]);

        $permissions = $this->createMock(SystemPermissions::class);
        $permissions->method('dashboardType')->willReturn('cashier');

        $request = Request::create('/dashboard');
        $request->headers->set('X-Inertia', 'true');

        $response = app(CashierDashboardService::class)
            ->render($request, new User, $permissions)
            ->toResponse($request);
        $dashboard = $response->getData(true)['props']['cashierDashboard'];

        $this->assertSame(2, $dashboard['summary']['financial_allowances']);
        $this->assertSame(1, $dashboard['summary']['credited_financial_allowances']);
        $this->assertSame(1, $dashboard['summary']['pending_financial_allowances']);
        $this->assertEquals(15500, $dashboard['summary']['financial_allowance_amount']);
        $this->assertEquals(12000, $dashboard['summary']['credited_financial_allowance_amount']);
        $this->assertEquals(3500, $dashboard['summary']['pending_financial_allowance_amount']);
        $this->assertSame('partial', $dashboard['summary']['financial_allowance_status']);
        $this->assertSame('partial', $dashboard['recentBatches'][0]['financial_allowance']['status']);
        $this->assertSame(1, $dashboard['recentBatches'][0]['financial_allowance']['credited']);
        $this->assertSame(1, $dashboard['recentBatches'][0]['financial_allowance']['pending']);
    }
}
