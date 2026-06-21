<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\CheckInInspectionItem;
use App\Models\OperationsTeamMember;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskManagementController extends Controller
{
    public function index(Request $request)
    {
        $tasks = BookingTask::query()
            ->with(['booking.tenant', 'unit.building', 'assignee', 'events.user'])
            ->when($request->status, fn ($query, string $status) => $query->where('status', $status))
            ->when($request->task_type, fn ($query, string $type) => $query->where('task_type', $type))
            ->orderByRaw("case when status = 'open' then 0 when status = 'in_progress' then 1 when status = 'blocked' then 2 else 3 end")
            ->orderBy('due_at')
            ->get();

        return view('tasks.index', [
            'tasks' => $tasks,
            'teamMembers' => OperationsTeamMember::orderBy('full_name')->get(),
            'statuses' => BookingTask::STATUSES,
            'types' => BookingTask::query()->select('task_type')->distinct()->pluck('task_type'),
        ]);
    }

    public function bookingInspection(Request $request, Booking $booking)
    {
        $tenant = $this->tenantFor($request);
        $canManage = $request->user()?->can('bookings.manage') || $request->user()?->can('booking-tasks.manage');

        abort_unless($canManage || ($tenant && (int) $booking->tenant_id === (int) $tenant->id), 403);

        $booking->load(['unit.building', 'tenant', 'tasks.assignee', 'tasks.events.user', 'checkInInspectionItems']);

        $unitType = strtolower((string) $booking->unit->unit_type);
        $groups = collect(config('inspection-groups', []))
            ->first(fn ($items, string $key): bool => $key !== 'default' && str_contains($unitType, $key))
            ?: config('inspection-groups.default', []);

        return view('bookings.inspection', [
            'booking' => $booking,
            'groups' => $groups,
            'tenantPortal' => $tenant && ! $canManage,
            'conditionOptions' => ['good', 'damaged', 'missing', 'needs_attention'],
            'existingItems' => $booking->checkInInspectionItems->keyBy(fn (CheckInInspectionItem $item): string => $item->area.'|'.$item->item),
        ]);
    }

    public function update(Request $request, BookingTask $bookingTask)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(BookingTask::STATUSES)],
            'assigned_to_id' => ['nullable', 'exists:operations_team_members,id'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'due_at' => ['nullable', 'date'],
            'completion_notes' => ['nullable', 'string', 'max:3000'],
            'timeline_note' => ['nullable', 'string', 'max:2000'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'string', 'max:191'],
        ]);

        $oldStatus = $bookingTask->status;

        $bookingTask->fill($validated);
        $bookingTask->checklist = collect($validated['checklist'] ?? [])
            ->filter()
            ->map(fn (string $item): array => ['label' => $item, 'done' => $validated['status'] === 'completed'])
            ->values()
            ->all();

        if ($validated['status'] === 'in_progress' && ! $bookingTask->started_at) {
            $bookingTask->started_at = now();
        }

        if ($validated['status'] === 'completed' && ! $bookingTask->completed_at) {
            $bookingTask->completed_at = now();
        }

        $bookingTask->save();

        $bookingTask->events()->create([
            'user_id' => $request->user()->id,
            'event_type' => $oldStatus === $validated['status'] ? 'updated' : 'status_changed',
            'description' => $validated['timeline_note'] ?: "Task moved from {$oldStatus} to {$validated['status']}.",
            'payload' => ['old_status' => $oldStatus, 'new_status' => $validated['status']],
        ]);

        ActivityLogger::log('booking_tasks.updated', "Updated task {$bookingTask->title}.", $bookingTask);

        return back()->with('status', 'Task updated.');
    }

    public function submitTenantCheckIn(Request $request, Booking $booking)
    {
        $tenant = $this->tenantFor($request);
        abort_unless($tenant && (int) $booking->tenant_id === (int) $tenant->id, 403);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.area' => ['required', 'string', 'max:100'],
            'items.*.item' => ['required', 'string', 'max:191'],
            'items.*.condition_status' => ['required', Rule::in(['good', 'damaged', 'missing', 'needs_attention'])],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated['items'] as $item) {
            $booking->checkInInspectionItems()->updateOrCreate(
                ['area' => $item['area'], 'item' => $item['item']],
                [
                    'condition_status' => $item['condition_status'],
                    'notes' => $item['notes'] ?? null,
                ],
            );
        }

        $booking->tasks()->firstOrCreate(
            ['task_type' => 'tenant_checkin_review'],
            [
                'unit_id' => $booking->unit_id,
                'title' => "Review tenant check-in report for Unit {$booking->unit->unit_no}",
                'due_at' => now()->addHours(4),
                'status' => 'open',
                'priority' => 'normal',
                'notes' => 'Tenant submitted apartment condition report from mobile portal.',
            ],
        )->events()->create([
            'user_id' => $request->user()->id,
            'event_type' => 'tenant_checkin_submitted',
            'description' => 'Tenant submitted check-in condition report.',
        ]);

        return back()->with('status', 'Check-in condition report submitted.');
    }

    public function submitBookingInspection(Request $request, Booking $booking)
    {
        abort_unless($request->user()?->can('bookings.manage') || $request->user()?->can('booking-tasks.manage'), 403);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.area' => ['required', 'string', 'max:100'],
            'items.*.item' => ['required', 'string', 'max:191'],
            'items.*.condition_status' => ['required', Rule::in(['good', 'damaged', 'missing', 'needs_attention'])],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'completion_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        foreach ($validated['items'] as $item) {
            $booking->checkInInspectionItems()->updateOrCreate(
                ['area' => $item['area'], 'item' => $item['item']],
                [
                    'condition_status' => $item['condition_status'],
                    'notes' => $item['notes'] ?? null,
                ],
            );
        }

        $task = $booking->tasks()->firstOrCreate(
            ['task_type' => 'checkout_inspection'],
            [
                'unit_id' => $booking->unit_id,
                'title' => "Full apartment inspection for Unit {$booking->unit->unit_no}",
                'due_at' => now(),
                'status' => 'open',
                'priority' => 'normal',
                'notes' => 'Full apartment inspection checklist created from booking page.',
            ],
        );

        $task->events()->create([
            'user_id' => $request->user()->id,
            'event_type' => 'inspection_saved',
            'description' => $validated['completion_notes'] ?: 'Full apartment inspection checklist saved.',
        ]);

        ActivityLogger::log('booking_tasks.inspection_saved', "Saved full apartment inspection for {$booking->booking_no}.", $booking);

        return back()->with('status', 'Full apartment inspection saved.');
    }

    public function submitCheckoutInspection(Request $request, BookingTask $bookingTask)
    {
        abort_unless($bookingTask->task_type === 'checkout_inspection', 404);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.area' => ['required', 'string', 'max:100'],
            'items.*.item' => ['required', 'string', 'max:191'],
            'items.*.condition_status' => ['required', Rule::in(['good', 'damaged', 'missing', 'needs_attention'])],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'completion_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $booking = $bookingTask->booking;

        foreach ($validated['items'] as $item) {
            $booking->checkInInspectionItems()->updateOrCreate(
                ['area' => $item['area'], 'item' => $item['item']],
                [
                    'condition_status' => $item['condition_status'],
                    'notes' => $item['notes'] ?? null,
                ],
            );
        }

        $bookingTask->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_notes' => $validated['completion_notes'] ?? 'Checkout inspection completed.',
        ]);

        $bookingTask->events()->create([
            'user_id' => $request->user()->id,
            'event_type' => 'checkout_inspection_completed',
            'description' => 'Checkout inspection checklist completed.',
        ]);

        return back()->with('status', 'Checkout inspection checklist completed.');
    }

    private function tenantFor(Request $request): ?Tenant
    {
        return Tenant::query()
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();
    }
}
