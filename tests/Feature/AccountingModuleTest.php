<?php

namespace Tests\Feature;

use App\Mail\OwnerExpenseRecordedMail;
use App\Models\Expense;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_accounting_pages_open_and_expense_can_be_recorded(): void
    {
        $this->seed();
        Storage::fake(config('filesystems.default'));
        Mail::fake();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::where('email', 'mariam.owner@example.com')->firstOrFail();
        $unit = Unit::where('unit_no', '1402')->firstOrFail();

        $this->actingAs($admin)->get(route('accounting.index'))->assertOk()->assertSee('Accounting command center');
        $this->actingAs($admin)->get(route('expenses.index'))->assertOk()->assertSee('Expense registry');
        $this->actingAs($admin)->get(route('expenses.create'))->assertOk()->assertSee('Expense name');

        $this->actingAs($admin)
            ->post(route('expenses.store'), [
                'name' => 'Owner AC service',
                'type' => 'maintenance',
                'expense_to_role' => 'owner',
                'expense_to_id' => $owner->id,
                'owner_id' => $owner->id,
                'unit_id' => $unit->id,
                'association' => 'owner_account',
                'incurred_on' => now()->toDateString(),
                'amount' => 450,
                'notes' => 'AC service for owner statement.',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect();

        $expense = Expense::where('name', 'Owner AC service')->firstOrFail();

        $this->assertSame('owner_account', $expense->association);
        $this->assertNotNull($expense->receipt_path);
        Mail::assertQueued(OwnerExpenseRecordedMail::class, fn (OwnerExpenseRecordedMail $mail): bool => $mail->expense->is($expense));

        $this->actingAs($admin)->get(route('expenses.show', $expense))->assertOk()->assertSee('Owner AC service');
        $payoutPayment = Payment::query()
            ->with('invoice')
            ->where('status', 'approved')
            ->whereHas('invoice', fn ($query) => $query->where('status', 'paid')->where('balance_amount', '<=', 0))
            ->firstOrFail();
        $payoutPeriodStart = $payoutPayment->invoice->period_start->format('M d, Y');
        $payoutPeriodEnd = $payoutPayment->invoice->period_end->format('M d, Y');
        $statementDates = [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->addMonth()->endOfMonth()->toDateString(),
        ];

        $this->actingAs($admin)->get(route('owner-statements.index', ['owner_id' => $owner->id, ...$statementDates]))
            ->assertOk()
            ->assertSee('Owner Account Statement')
            ->assertSee('Rent collected')
            ->assertSee('AED 8,500.00')
            ->assertSee('Check-in:')
            ->assertSee('Check-out:')
            ->assertSee('PDF');
        $this->actingAs($admin)->get(route('owner-statements.pdf', ['owner_id' => $owner->id, ...$statementDates]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('owner-payouts.index', ['owner_id' => $owner->id]))
            ->assertOk()
            ->assertSee('Owner Account Manager')
            ->assertSee('Payout and transfer schedule')
            ->assertSee('Payout dates are fixed from each invoice period')
            ->assertSee('AED 770.00')
            ->assertSee('AED 4,109.00')
            ->assertSee($payoutPeriodStart)
            ->assertSee($payoutPeriodEnd)
            ->assertDontSee('Save date');
        $this->actingAs($admin)->get(route('owner-payouts.pdf', ['owner_id' => $owner->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('owner-payouts.excel', ['owner_id' => $owner->id]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertSeeText('Reports & Profit/Loss');
        $this->actingAs($admin)->get(route('reports.export', ['type' => 'expenses']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_expense_number_does_not_reuse_a_soft_deleted_reference(): void
    {
        $this->seed();
        Mail::fake();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $deletedReference = 'EXP-'.now()->format('Ymd').'-0001';

        $deletedExpense = Expense::create([
            'expense_no' => $deletedReference,
            'name' => 'Deleted historical expense',
            'type' => 'other',
            'expense_to_role' => 'company',
            'association' => 'company',
            'incurred_on' => now()->toDateString(),
            'amount' => 10,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $deletedExpense->delete();

        $this->actingAs($admin)
            ->post(route('expenses.store'), [
                'name' => 'New expense after deletion',
                'type' => 'internet',
                'expense_to_role' => 'company',
                'association' => 'company',
                'incurred_on' => now()->toDateString(),
                'amount' => 100,
            ])
            ->assertRedirect();

        $newExpense = Expense::where('name', 'New expense after deletion')->firstOrFail();

        $this->assertNotSame($deletedReference, $newExpense->expense_no);
        $this->assertStringStartsWith('EXP-'.now()->format('Ymd').'-', $newExpense->expense_no);
    }

    public function test_expense_list_can_be_filtered_by_unit(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $units = Unit::query()->take(2)->get();
        $this->assertCount(2, $units);

        Expense::create([
            'expense_no' => 'EXP-UNIT-FILTER-A',
            'name' => 'Selected unit expense',
            'type' => 'maintenance',
            'expense_to_role' => 'company',
            'unit_id' => $units[0]->id,
            'association' => 'unit',
            'incurred_on' => now(),
            'amount' => 100,
        ]);
        Expense::create([
            'expense_no' => 'EXP-UNIT-FILTER-B',
            'name' => 'Other unit expense',
            'type' => 'maintenance',
            'expense_to_role' => 'company',
            'unit_id' => $units[1]->id,
            'association' => 'unit',
            'incurred_on' => now(),
            'amount' => 200,
        ]);

        $this->actingAs($admin)
            ->get(route('expenses.index', ['unit_id' => $units[0]->id]))
            ->assertOk()
            ->assertSee('All units')
            ->assertSee('Selected unit expense')
            ->assertDontSee('Other unit expense');
    }

    public function test_accounting_manager_can_delete_an_expense(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $expense = Expense::create([
            'expense_no' => 'EXP-DELETE-TEST',
            'name' => 'Expense to delete',
            'type' => 'other',
            'expense_to_role' => 'company',
            'association' => 'company',
            'incurred_on' => now()->toDateString(),
            'amount' => 99,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertSee('Delete');

        $this->actingAs($admin)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_unit_zoho_balance_carries_into_later_owner_statement(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::create(['full_name' => 'Zoho Balance Owner', 'mobile_no' => '+971500009999']);
        $unit = Unit::create([
            'building_id' => Unit::firstOrFail()->building_id,
            'unit_no' => 'ZOHO-01',
            'unit_type' => 'Studio',
            'availability_status' => 'available',
        ]);
        $owner->units()->attach($unit->id, ['share_percent' => 100]);

        $this->actingAs($admin)->post(route('owner-statements.opening-balance.store'), [
            'owner_id' => $owner->id,
            'unit_id' => $unit->id,
            'entry_date' => '2026-06-15',
            'direction' => 'credit',
            'amount' => 2500,
            'description' => 'Zoho migrated balance',
            'statement_from' => '2026-06-16',
            'statement_to' => '2026-08-04',
        ])->assertRedirect();

        $this->assertDatabaseHas('owner_account_entries', [
            'owner_id' => $owner->id,
            'unit_id' => $unit->id,
            'type' => 'opening_balance',
            'direction' => 'credit',
            'amount' => 2500,
        ]);

        $query = ['owner_id' => $owner->id, 'unit_id' => $unit->id, 'from' => '2026-06-16', 'to' => '2026-08-04'];
        $this->actingAs($admin)->get(route('owner-statements.index', $query))
            ->assertOk()
            ->assertSee('Opening balance')
            ->assertSee('AED 2,500.00')
            ->assertSee('Closing balance');

        $this->actingAs($admin)->get(route('owner-statements.pdf', $query))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_accounting_finance_sheet_exports_invoice_owner_periods(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $dates = ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->addMonth()->endOfMonth()->toDateString()];

        $this->actingAs($admin)->get(route('finance-sheet.index', $dates))
            ->assertOk()
            ->assertSee('Finance Sheet')
            ->assertSee('Owner Payment')
            ->assertSee('Amount to Owner');
        $this->actingAs($admin)->get(route('finance-sheet.pdf', $dates))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $excel = $this->actingAs($admin)->get(route('finance-sheet.excel', $dates));
        $excel->assertOk()->assertDownload('finance-sheet-'.now()->startOfMonth()->format('Ymd').'-'.now()->addMonth()->endOfMonth()->format('Ymd').'.csv');
        $this->assertStringContainsString('Owner Payment', $excel->streamedContent());
        $this->assertStringContainsString('Amount to Owner', $excel->streamedContent());
    }

    public function test_expense_target_flow_requires_matching_records(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $owner = Owner::where('email', 'mariam.owner@example.com')->firstOrFail();
        $wrongUnit = Unit::whereDoesntHave('owners', fn ($query) => $query->whereKey($owner->id))->firstOrFail();
        $tenant = Tenant::where('email', 'nora.tenant@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('Select the target first')
            ->assertSee('Select owner first')
            ->assertDontSee('Selected person ID');

        $this->actingAs($admin)
            ->post(route('expenses.store'), [
                'name' => 'Wrong unit expense',
                'type' => 'maintenance',
                'expense_to_role' => 'owner',
                'owner_id' => $owner->id,
                'unit_id' => $wrongUnit->id,
                'association' => 'owner_account',
                'incurred_on' => now()->toDateString(),
                'amount' => 125,
            ])
            ->assertSessionHasErrors('unit_id');

        $this->actingAs($admin)
            ->post(route('expenses.store'), [
                'name' => 'Tenant key delivery',
                'type' => 'other',
                'expense_to_role' => 'tenant',
                'expense_to_id' => $tenant->id,
                'association' => 'booking',
                'incurred_on' => now()->toDateString(),
                'amount' => 75,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(Expense::class, [
            'name' => 'Tenant key delivery',
            'expense_to_role' => 'tenant',
            'expense_to_id' => $tenant->id,
            'owner_id' => null,
            'unit_id' => null,
        ]);
    }

    public function test_owner_statement_uses_booking_checkout_date_for_collected_rent_and_period_expenses(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $invoice = Invoice::with('booking.unit.owners')->where('invoice_no', 'INV-DEMO-0001')->firstOrFail();
        $booking = $invoice->booking;
        $owner = $booking->unit->owners->firstOrFail();
        $checkoutDate = $booking->check_out_date->toDateString();

        Expense::create([
            'expense_no' => 'EXP-CHECKOUT-0001',
            'name' => 'Checkout period owner expense',
            'type' => 'maintenance',
            'expense_to_role' => 'owner',
            'owner_id' => $owner->id,
            'unit_id' => $booking->unit_id,
            'association' => 'owner_account',
            'incurred_on' => $checkoutDate,
            'amount' => 321,
        ]);

        // The booking begins outside this one-day filter, but its checkout invoice must still appear.
        $query = ['owner_id' => $owner->id, 'from' => $checkoutDate, 'to' => $checkoutDate];
        $this->actingAs($admin)->get(route('owner-statements.index', $query))
            ->assertOk()
            ->assertSee($invoice->invoice_no)
            ->assertSee('Rent collected')
            ->assertSee('Check-in:')
            ->assertSee('Check-out:')
            ->assertSee('Checkout period owner expense');

        $export = $this->actingAs($admin)->get(route('owner-statements.index', [...$query, 'export' => 1]));
        $export->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Rent Collected', $export->streamedContent());
        $this->assertStringContainsString($booking->check_in_date->toDateString(), $export->streamedContent());
        $this->assertStringContainsString($checkoutDate, $export->streamedContent());
        $this->assertStringContainsString('Checkout period owner expense', $export->streamedContent());
    }

    public function test_owner_payout_date_stays_with_the_paid_invoice_period_after_extension(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $payment = Payment::query()
            ->with(['invoice.booking.unit.owners'])
            ->where('status', 'approved')
            ->whereHas('invoice', fn ($query) => $query
                ->where('status', 'paid')
                ->where('balance_amount', '<=', 0)
                ->whereNotNull('payout_due_date'))
            ->firstOrFail();
        $invoice = $payment->invoice;
        $booking = $invoice->booking;
        $owner = $booking->unit->owners->firstOrFail();
        $originalPayoutDate = $invoice->payout_due_date->format('M d, Y');
        $extendedCheckout = $invoice->payout_due_date->copy()->addDays(14);

        $booking->update(['check_out_date' => $extendedCheckout]);

        $this->actingAs($admin)
            ->get(route('owner-payouts.index', ['owner_id' => $owner->id]))
            ->assertOk()
            ->assertSee($originalPayoutDate)
            ->assertDontSee($extendedCheckout->format('M d, Y'));
    }

    public function test_accounting_manager_can_open_reports_without_report_specific_permission(): void
    {
        $this->seed();

        Permission::findOrCreate('accounting.manage', 'web');
        $role = Role::findOrCreate('Accounting Manager', 'web');
        $role->syncPermissions(['accounting.manage']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeText('Reports & Profit/Loss');
    }

    public function test_bank_statement_import_suggests_and_confirms_payment_match(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $invoice = Invoice::where('invoice_no', 'INV-DEMO-0001')->firstOrFail();
        $invoice->update([
            'status' => 'sent',
            'paid_amount' => 0,
            'balance_amount' => $invoice->total_amount,
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'booking_id' => $invoice->booking_id,
            'payment_no' => 'PAY-TEST-BANK',
            'method' => 'bank_transfer',
            'status' => 'pending',
            'amount' => 11175,
            'paid_at' => now(),
            'reference_no' => 'BANK-REF-001',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('bank-reconciliation.accounts.store'), [
                'name' => 'Main Collections',
                'bank_name' => 'Test Bank',
                'currency' => 'AED',
            ])
            ->assertRedirect();

        $account = BankAccount::firstOrFail();
        $csv = "Date,Description,Reference,Credit,Debit,Balance\n".
            now()->format('Y-m-d').",Tenant payment {$invoice->invoice_no} BANK-REF-001,BANK-REF-001,11175.00,,50000.00\n";
        $path = tempnam(sys_get_temp_dir(), 'bank').'.csv';
        file_put_contents($path, $csv);

        $this->actingAs($admin)
            ->post(route('bank-reconciliation.import'), [
                'bank_account_id' => $account->id,
                'statement' => new UploadedFile($path, 'statement.csv', 'text/csv', null, true),
            ])
            ->assertRedirect();

        $transaction = BankTransaction::with('matches')->firstOrFail();
        $this->assertSame('suggested', $transaction->status);
        $this->assertTrue($transaction->matches->contains('matchable_id', $payment->id));

        $match = $transaction->matches->firstWhere('matchable_id', $payment->id);

        $this->actingAs($admin)
            ->post(route('bank-reconciliation.confirm', $transaction), ['match_id' => $match->id])
            ->assertRedirect();

        $this->assertSame('matched', $transaction->fresh()->status);
        $this->assertSame('approved', $payment->fresh()->status);
    }
}
