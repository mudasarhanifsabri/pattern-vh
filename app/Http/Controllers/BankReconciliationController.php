<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankTransaction;
use App\Models\BankTransactionMatch;
use App\Models\Expense;
use App\Models\OwnerPayoutTransfer;
use App\Models\Payment;
use App\Support\BankStatementMatcher;
use App\Support\InvoicePaymentWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $transactions = BankTransaction::query()
            ->with(['bankAccount', 'matches.matchable', 'matched'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('bank_account_id'), fn ($query) => $query->where('bank_account_id', $request->integer('bank_account_id')))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->where('description', 'like', '%'.$request->search.'%')
                ->orWhere('reference_no', 'like', '%'.$request->search.'%')))
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('bank-reconciliation.index', [
            'accounts' => BankAccount::latest()->get(),
            'imports' => BankStatementImport::with('bankAccount')->latest()->limit(8)->get(),
            'transactions' => $transactions,
            'stats' => [
                'unmatched' => BankTransaction::where('status', 'unmatched')->count(),
                'suggested' => BankTransaction::where('status', 'suggested')->count(),
                'matched' => BankTransaction::where('status', 'matched')->count(),
                'credits' => BankTransaction::where('type', 'credit')->sum('amount'),
                'debits' => BankTransaction::where('type', 'debit')->sum('amount'),
            ],
        ]);
    }

    public function storeAccount(Request $request)
    {
        BankAccount::create($request->validate([
            'name' => ['required', 'string', 'max:191'],
            'bank_name' => ['nullable', 'string', 'max:191'],
            'account_no' => ['nullable', 'string', 'max:191'],
            'iban' => ['nullable', 'string', 'max:191'],
            'currency' => ['required', 'string', 'max:10'],
        ]));

        return back()->with('status', 'Bank account added.');
    }

    public function import(Request $request, BankStatementMatcher $matcher)
    {
        $validated = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'statement' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'statement_from' => ['nullable', 'date'],
            'statement_to' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $import = BankStatementImport::create([
            'bank_account_id' => $validated['bank_account_id'],
            'original_name' => $request->file('statement')->getClientOriginalName(),
            'statement_from' => $validated['statement_from'] ?? null,
            'statement_to' => $validated['statement_to'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        [$imported, $duplicates, $total] = $this->parseCsv($request->file('statement')->getRealPath(), $import, $matcher);

        $import->update([
            'rows_total' => $total,
            'rows_imported' => $imported,
            'rows_duplicate' => $duplicates,
        ]);

        return back()->with('status', "Statement imported: {$imported} new transactions, {$duplicates} duplicates.");
    }

    public function confirm(Request $request, BankTransaction $bankTransaction, BankStatementMatcher $matcher, InvoicePaymentWorkflow $workflow)
    {
        $data = $request->validate([
            'match_id' => ['required', 'exists:bank_transaction_matches,id'],
        ]);

        $match = BankTransactionMatch::with('matchable')->where('bank_transaction_id', $bankTransaction->id)->findOrFail($data['match_id']);
        $matcher->confirm($bankTransaction, $match->matchable, $request->user()->id);

        if ($match->matchable instanceof Payment && $match->matchable->status === 'pending') {
            $match->matchable->forceFill([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'verification_notes' => trim(($match->matchable->verification_notes ? $match->matchable->verification_notes."\n" : '').'Approved by bank statement match '.$bankTransaction->reference_no),
            ])->save();

            $workflow->afterPayment($match->matchable);
        }

        return back()->with('status', 'Bank transaction matched successfully.');
    }

    public function manualMatch(Request $request, BankTransaction $bankTransaction, BankStatementMatcher $matcher, InvoicePaymentWorkflow $workflow)
    {
        $data = $request->validate([
            'match_type' => ['required', Rule::in(['payment', 'expense', 'owner_payout'])],
            'match_id' => ['required', 'integer'],
        ]);

        $model = match ($data['match_type']) {
            'payment' => Payment::findOrFail($data['match_id']),
            'expense' => Expense::findOrFail($data['match_id']),
            'owner_payout' => OwnerPayoutTransfer::findOrFail($data['match_id']),
        };

        $suggestion = $bankTransaction->matches()->updateOrCreate(
            ['matchable_type' => $model::class, 'matchable_id' => $model->getKey()],
            ['confidence' => 100, 'status' => 'suggested', 'reason' => 'Manual finance match'],
        );

        $request->merge(['match_id' => $suggestion->id]);

        return $this->confirm($request, $bankTransaction, $matcher, $workflow);
    }

    public function reject(BankTransactionMatch $match)
    {
        $match->update(['status' => 'rejected']);

        if (! $match->bankTransaction->matches()->where('status', 'suggested')->exists()) {
            $match->bankTransaction->update(['status' => 'unmatched']);
        }

        return back()->with('status', 'Match suggestion rejected.');
    }

    public function ignore(BankTransaction $bankTransaction)
    {
        $bankTransaction->update(['status' => 'ignored']);

        return back()->with('status', 'Bank transaction ignored.');
    }

    private function parseCsv(string $path, BankStatementImport $import, BankStatementMatcher $matcher): array
    {
        $handle = fopen($path, 'r');
        $headers = array_map(fn ($value) => str(ltrim((string) $value, "\xEF\xBB\xBF"))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString(), fgetcsv($handle) ?: []);

        if ($headers === []) {
            fclose($handle);

            return [0, 0, 0];
        }
        $imported = $duplicates = $total = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            $data = array_combine($headers, array_pad($row, count($headers), null)) ?: [];
            $date = $this->value($data, ['date', 'transaction_date', 'posting_date', 'value_date']);
            $description = $this->value($data, ['description', 'details', 'narration', 'transaction_details', 'particulars']);
            $reference = $this->value($data, ['reference', 'reference_no', 'ref', 'cheque_no', 'transaction_id']);
            $debit = $this->money($this->value($data, ['debit', 'withdrawal', 'paid_out']));
            $credit = $this->money($this->value($data, ['credit', 'deposit', 'paid_in']));
            $amount = $this->money($this->value($data, ['amount', 'transaction_amount']));
            $balance = $this->money($this->value($data, ['balance', 'running_balance', 'available_balance']));

            if (! $date || (! $amount && ! $debit && ! $credit)) {
                continue;
            }

            $type = $credit > 0 || $amount > 0 ? 'credit' : 'debit';
            $absoluteAmount = abs($credit ?: $debit ?: $amount);
            $transactionDate = Carbon::parse($date)->toDateString();
            $fingerprint = hash('sha256', implode('|', [$import->bank_account_id, $transactionDate, $type, number_format($absoluteAmount, 2, '.', ''), $reference, $description]));

            $transaction = BankTransaction::firstOrCreate(
                ['bank_account_id' => $import->bank_account_id, 'fingerprint' => $fingerprint],
                [
                    'bank_statement_import_id' => $import->id,
                    'transaction_date' => $transactionDate,
                    'type' => $type,
                    'amount' => $absoluteAmount,
                    'balance' => $balance,
                    'reference_no' => $reference,
                    'description' => $description,
                    'fingerprint' => $fingerprint,
                ],
            );

            $transaction->wasRecentlyCreated ? $imported++ : $duplicates++;

            if ($transaction->wasRecentlyCreated) {
                $matcher->suggest($transaction);
            }
        }

        fclose($handle);

        return [$imported, $duplicates, $total];
    }

    private function value(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (filled($data[$key] ?? null)) {
                return trim((string) $data[$key]);
            }
        }

        return null;
    }

    private function money(?string $value): float
    {
        $raw = strtoupper(trim((string) $value));
        if ($raw === '') {
            return 0.0;
        }

        $negative = str_contains($raw, '(') || str_contains($raw, 'DR');
        $clean = preg_replace('/[^0-9.\-]/', '', $raw);
        $amount = (float) $clean;

        return $negative ? -abs($amount) : $amount;
    }
}
