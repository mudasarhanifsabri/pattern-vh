<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UtilityManagementController extends Controller
{
    public function index()
    {
        $accounts = UtilityAccount::query()
            ->with(['unit.building', 'bills' => fn ($query) => $query->latest('due_date')->limit(3)])
            ->when(request('provider_type'), fn ($query, string $type) => $query->where('provider_type', $type))
            ->latest()
            ->get();

        $bills = UtilityBill::query()
            ->with('utilityAccount.unit.building')
            ->orderBy('due_date')
            ->get();

        return view('utilities.index', [
            'accounts' => $accounts,
            'bills' => $bills,
            'units' => Unit::with('building')->orderBy('unit_no')->get(),
            'providerTypes' => UtilityAccount::PROVIDER_TYPES,
            'statuses' => UtilityBill::STATUSES,
        ]);
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'provider_type' => ['required', Rule::in(UtilityAccount::PROVIDER_TYPES)],
            'provider_name' => ['required', 'string', 'max:191'],
            'account_no' => ['nullable', 'string', 'max:191'],
            'username' => ['nullable', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'max:191'],
            'paid_by_company' => ['nullable', 'boolean'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'next_due_date' => ['nullable', 'date'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'paused', 'closed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['paid_by_company'] = $request->boolean('paid_by_company');
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $account = UtilityAccount::create($validated);
        ActivityLogger::log('utilities.account_created', "Created {$account->provider_name} utility account.", $account);

        return redirect()->route('utilities.index')->with('status', 'Utility account added.');
    }

    public function storeBill(Request $request)
    {
        $validated = $request->validate([
            'utility_account_id' => ['required', 'exists:utility_accounts,id'],
            'bill_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['required', Rule::in(UtilityBill::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($request->hasFile('receipt')) {
            $validated = array_merge($validated, $this->storeReceipt($request->file('receipt'), $validated));
        }

        unset($validated['receipt']);
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $bill = UtilityBill::create($validated);
        ActivityLogger::log('utilities.bill_created', "Created utility bill due {$bill->due_date->format('M d, Y')}.", $bill);

        return redirect()->route('utilities.index')->with('status', 'Utility bill added to due calendar.');
    }

    public function markBillPaid(Request $request, UtilityBill $utilityBill)
    {
        $utilityBill->update([
            'status' => 'paid',
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Utility bill marked paid.');
    }

    private function storeReceipt(UploadedFile $file, array $data): array
    {
        $account = UtilityAccount::with('unit.building')->findOrFail($data['utility_account_id']);
        $disk = config('filesystems.default');
        $name = 'Utility Receipt - '.$account->provider_name.' - Unit '.$account->unit->unit_no.'.'.$file->getClientOriginalExtension();
        $path = ErpStoragePath::documentPath('Utilities', $account->unit->unit_no, 'receipts', $file, $name);

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return [
            'receipt_disk' => $disk,
            'receipt_path' => $path,
            'receipt_original_name' => $name,
        ];
    }
}
