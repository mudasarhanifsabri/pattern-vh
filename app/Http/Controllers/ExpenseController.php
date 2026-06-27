<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Expense;
use App\Models\OperationsTeamMember;
use App\Models\Owner;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use App\Support\ReferenceNumber;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::query()
            ->with(['owner', 'unit.building'])
            ->when(request('search'), fn ($query, string $search) => $query->where('expense_no', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
            ->when(request('type'), fn ($query, string $type) => $query->where('type', $type))
            ->when(request('expense_to_role'), fn ($query, string $role) => $query->where('expense_to_role', $role))
            ->latest('incurred_on')
            ->paginate(12)
            ->withQueryString();

        return view('expenses.index', compact('expenses'));
    }

    public function create()
    {
        return view('expenses.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        if ($request->hasFile('receipt')) {
            $validated = array_merge($validated, $this->storeReceipt($request->file('receipt'), $validated));
        }

        unset($validated['receipt']);

        $expense = Expense::create(array_merge($validated, [
            'expense_no' => $this->nextExpenseNo(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        ActivityLogger::log('expenses.created', "Created expense {$expense->expense_no}.", $expense);

        return redirect()->route('expenses.show', $expense)->with('status', 'Expense recorded successfully.');
    }

    public function show(Expense $expense)
    {
        return view('expenses.show', ['expense' => $expense->load(['owner', 'unit.building'])]);
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', array_merge($this->formData(), compact('expense')));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $this->validated($request);

        if ($request->hasFile('receipt')) {
            $validated = array_merge($validated, $this->storeReceipt($request->file('receipt'), $validated));
        }

        unset($validated['receipt']);
        $expense->update(array_merge($validated, ['updated_by' => $request->user()->id]));

        ActivityLogger::log('expenses.updated', "Updated expense {$expense->expense_no}.", $expense);

        return redirect()->route('expenses.show', $expense)->with('status', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        ActivityLogger::log('expenses.deleted', "Deleted expense {$expense->expense_no}.", $expense);
        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Expense deleted successfully.');
    }

    public function receipt(Expense $expense)
    {
        abort_if(! $expense->receipt_path, 404);

        $disk = Storage::disk($expense->receipt_disk ?? config('filesystems.default'));

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return redirect()->away($disk->temporaryUrl($expense->receipt_path, now()->addMinutes(10)));
            } catch (\Throwable) {
                //
            }
        }

        try {
            return response()->streamDownload(fn () => print $disk->get($expense->receipt_path), $expense->receipt_original_name ?: basename($expense->receipt_path));
        } catch (\Throwable) {
            abort(404);
        }
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'type' => ['required', Rule::in(Expense::TYPES)],
            'expense_to_role' => ['required', Rule::in(Expense::TARGET_ROLES)],
            'expense_to_id' => ['nullable', 'integer'],
            'owner_id' => ['nullable', Rule::requiredIf($request->input('expense_to_role') === 'owner'), 'exists:owners,id'],
            'unit_id' => ['nullable', Rule::requiredIf($request->input('expense_to_role') === 'owner'), 'exists:units,id'],
            'association' => ['required', Rule::in(Expense::ASSOCIATIONS)],
            'incurred_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($validated['expense_to_role'] === 'owner') {
            $belongsToOwner = Unit::query()
                ->whereKey($validated['unit_id'])
                ->whereHas('owners', fn ($query) => $query->whereKey($validated['owner_id']))
                ->exists();

            if (! $belongsToOwner) {
                throw ValidationException::withMessages([
                    'unit_id' => 'Select an apartment assigned to the selected owner.',
                ]);
            }

            $validated['expense_to_id'] = $validated['owner_id'];
            $validated['association'] = in_array($validated['association'], ['owner_account', 'unit'], true)
                ? $validated['association']
                : 'owner_account';
        } elseif ($validated['expense_to_role'] === 'company') {
            $validated['expense_to_id'] = null;
            $validated['owner_id'] = null;
            $validated['unit_id'] = null;
            $validated['association'] = 'company';
        } else {
            if (blank($validated['expense_to_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'expense_to_id' => 'Select the person or team member for this expense.',
                ]);
            }

            $targetModel = match ($validated['expense_to_role']) {
                'tenant' => Tenant::class,
                'agent' => Agent::class,
                'operations_team' => OperationsTeamMember::class,
            };

            if (! $targetModel::whereKey($validated['expense_to_id'])->exists()) {
                throw ValidationException::withMessages([
                    'expense_to_id' => 'Select a valid '.str($validated['expense_to_role'])->replace('_', ' ')->headline()->lower().' record.',
                ]);
            }

            $validated['owner_id'] = null;
            $validated['unit_id'] = null;
        }

        return $validated;
    }

    private function formData(): array
    {
        return [
            'types' => Expense::TYPES,
            'targetRoles' => Expense::TARGET_ROLES,
            'associations' => Expense::ASSOCIATIONS,
            'owners' => Owner::orderBy('full_name')->get(),
            'tenants' => Tenant::orderBy('full_name')->get(),
            'agents' => Agent::orderBy('full_name')->get(),
            'teamMembers' => OperationsTeamMember::orderBy('full_name')->get(),
            'units' => Unit::with('building', 'owners')->orderBy('unit_no')->get(),
        ];
    }

    private function storeReceipt(UploadedFile $file, array $data): array
    {
        $disk = config('filesystems.default');
        $name = 'Expense Receipt - '.str($data['name'])->slug('-').'.'.$file->getClientOriginalExtension();
        $path = ErpStoragePath::documentPath('Expenses', $data['expense_to_role'], 'receipts', $file, $name);

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return [
            'receipt_disk' => $disk,
            'receipt_path' => $path,
            'receipt_original_name' => $name,
        ];
    }

    private function nextExpenseNo(): string
    {
        return ReferenceNumber::next(Expense::class, 'expense_no', 'EXP');
    }
}
