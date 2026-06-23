<?php

namespace App\Support;

use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\OwnerPayoutTransfer;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BankStatementMatcher
{
    public function suggest(BankTransaction $transaction): void
    {
        if ($transaction->status === 'matched') {
            return;
        }

        $suggestions = $transaction->type === 'credit'
            ? $this->paymentSuggestions($transaction)
            : $this->debitSuggestions($transaction);

        foreach ($suggestions->sortByDesc('confidence')->take(5) as $suggestion) {
            $transaction->matches()->updateOrCreate(
                [
                    'matchable_type' => $suggestion['model'],
                    'matchable_id' => $suggestion['id'],
                ],
                [
                    'confidence' => $suggestion['confidence'],
                    'status' => 'suggested',
                    'reason' => $suggestion['reason'],
                ],
            );
        }

        if ($transaction->matches()->where('status', 'suggested')->exists()) {
            $transaction->update(['status' => 'suggested']);
        }
    }

    public function confirm(BankTransaction $transaction, Model $model, int $userId): void
    {
        $transaction->update([
            'status' => 'matched',
            'matched_type' => $model::class,
            'matched_id' => $model->getKey(),
            'matched_at' => now(),
            'matched_by' => $userId,
        ]);

        $transaction->matches()
            ->where('matchable_type', $model::class)
            ->where('matchable_id', $model->getKey())
            ->update([
                'status' => 'confirmed',
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

        $transaction->matches()
            ->where(fn ($query) => $query
                ->where('matchable_type', '!=', $model::class)
                ->orWhere('matchable_id', '!=', $model->getKey()))
            ->update(['status' => 'rejected']);
    }

    private function paymentSuggestions(BankTransaction $transaction): Collection
    {
        return Payment::query()
            ->with(['invoice.tenant'])
            ->whereIn('status', ['pending', 'approved'])
            ->whereBetween('amount', [$transaction->amount - 1, $transaction->amount + 1])
            ->whereBetween('paid_at', [
                $transaction->transaction_date->copy()->subDays(7)->startOfDay(),
                $transaction->transaction_date->copy()->addDays(7)->endOfDay(),
            ])
            ->limit(20)
            ->get()
            ->map(fn (Payment $payment): array => [
                'model' => Payment::class,
                'id' => $payment->id,
                'confidence' => $this->score($transaction, $payment->amount, $payment->paid_at, [
                    $payment->payment_no,
                    $payment->reference_no,
                    $payment->invoice?->invoice_no,
                    $payment->invoice?->tenant?->full_name,
                ]),
                'reason' => "Payment {$payment->payment_no} / {$payment->invoice?->invoice_no}",
            ]);
    }

    private function debitSuggestions(BankTransaction $transaction): Collection
    {
        $expenses = Expense::query()
            ->whereBetween('amount', [$transaction->amount - 1, $transaction->amount + 1])
            ->whereBetween('incurred_on', [
                $transaction->transaction_date->copy()->subDays(14)->toDateString(),
                $transaction->transaction_date->copy()->addDays(14)->toDateString(),
            ])
            ->limit(20)
            ->get()
            ->map(fn (Expense $expense): array => [
                'model' => Expense::class,
                'id' => $expense->id,
                'confidence' => $this->score($transaction, $expense->amount, $expense->incurred_on, [$expense->expense_no, $expense->name, $expense->type]),
                'reason' => "Expense {$expense->expense_no} / {$expense->name}",
            ]);

        $payouts = OwnerPayoutTransfer::query()
            ->with('owner')
            ->whereBetween('net_payout', [$transaction->amount - 1, $transaction->amount + 1])
            ->whereBetween('transferred_at', [
                $transaction->transaction_date->copy()->subDays(14)->startOfDay(),
                $transaction->transaction_date->copy()->addDays(14)->endOfDay(),
            ])
            ->limit(20)
            ->get()
            ->map(fn (OwnerPayoutTransfer $transfer): array => [
                'model' => OwnerPayoutTransfer::class,
                'id' => $transfer->id,
                'confidence' => $this->score($transaction, $transfer->net_payout, $transfer->transferred_at, [$transfer->reference_no, $transfer->owner?->full_name]),
                'reason' => "Owner payout / {$transfer->owner?->full_name}",
            ]);

        return $expenses->merge($payouts);
    }

    private function score(BankTransaction $transaction, mixed $amount, mixed $date, array $needles): int
    {
        $score = abs((float) $transaction->amount - (float) $amount) < 0.01 ? 60 : 45;
        $days = abs($transaction->transaction_date->diffInDays(Carbon::parse($date), false));
        $score += max(0, 20 - min(20, $days * 3));
        $description = Str::lower((string) $transaction->description.' '.(string) $transaction->reference_no);

        foreach (array_filter($needles) as $needle) {
            $needle = Str::lower((string) $needle);
            if ($needle && Str::contains($description, $needle)) {
                $score += 20;
                break;
            }
        }

        return min(100, $score);
    }
}
