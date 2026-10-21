<?php

namespace Tests\Feature;

use App\Models\Batches;
use App\Models\BatchRecipients;
use App\Models\RecipientAllowance;
use App\Models\User;
use App\Services\Payroll\PayrollAllowanceCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Vinkla\Hashids\Facades\Hashids;

class PayrollAllowanceCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowances_can_be_deposited_individually_without_changing_payroll_status(): void
    {
        $user = User::factory()->create();
        $batch = Batches::create([
            'name' => 'Allowance deposit test batch',
            'status' => 'approved_payroll',
        ]);
        $recipient = BatchRecipients::create([
            'batch_id' => $batch->id,
            'account_no' => '123456789',
            'status' => 'approved_payroll',
        ]);
        $tuition = RecipientAllowance::create([
            'recipient_id' => $recipient->id,
            'classification' => 'tuition',
            'amount' => 12000,
            'status' => 'approved_payroll',
        ]);
        $transportation = RecipientAllowance::create([
            'recipient_id' => $recipient->id,
            'classification' => 'transportation',
            'amount' => 3500,
            'status' => 'approved_payroll',
        ]);

        $result = app(PayrollAllowanceCreditService::class)->creditSelected(
            $batch,
            $user->id,
            [
                [
                    'id' => Hashids::encode($tuition->id),
                    'selected' => true,
                    'remarks' => null,
                ],
                [
                    'id' => Hashids::encode($transportation->id),
                    'selected' => false,
                    'remarks' => 'Awaiting payment documents',
                ],
            ]
        );

        $tuition->refresh();
        $transportation->refresh();

        $this->assertSame('credited', $tuition->deposit_status);
        $this->assertSame($user->id, $tuition->deposited_by);
        $this->assertNotNull($tuition->deposited_at);
        $this->assertSame('approved_payroll', $tuition->status);
        $this->assertSame('pending', $transportation->deposit_status);
        $this->assertSame('Awaiting payment documents', $transportation->deposit_remarks);
        $this->assertSame('approved_payroll', $transportation->status);
        $this->assertSame(1, $result['recipient_count']);
        $this->assertSame(12000.0, $result['amount']);
        $this->assertSame('partial', $result['summary']['status']);
    }
}
