<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\BookingTaskCostItem;
use App\Models\BookingTaskRemark;
use App\Models\CheckInInspectionItem;
use App\Models\OperationsTeamMember;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\ActivityLogger;
use App\Support\PushEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskManagementController extends Controller
{
    public function index(Request $request)
    {
        $tasks = $this->taskQuery($request)->paginate($request->integer('per_page', 20))->withQueryString();
        $teamMembers = OperationsTeamMember::orderBy('full_name')->get();
        $units = Unit::with('building')->orderBy('unit_no')->get();
        $bookings = Booking::with(['unit.building', 'tenant'])->latest()->limit(100)->get();
        $stats = [
            'total' => BookingTask::count(),
            'pending' => BookingTask::whereIn('status', ['open', 'assigned'])->count(),
            'accepted' => BookingTask::where('status', 'accepted')->count(),
            'in_progress' => BookingTask::where('status', 'in_progress')->count(),
            'completed' => BookingTask::whereIn('status', ['completed', 'closed'])->count(),
            'overdue' => BookingTask::whereNotIn('status', ['completed', 'closed', 'cancelled'])->where('due_at', '<', now())->count(),
        ];

        return view('tasks.index', compact('tasks', 'teamMembers', 'units', 'bookings', 'stats') + [
            'statuses' => BookingTask::STATUSES,
            'types' => BookingTask::TYPES,
            'priorities' => BookingTask::PRIORITIES,
        ]);
    }

    public function store(Request $request, PushEventLogger $push)
    {
        $validated = $request->validate([
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'unit_id' => ['required_without:booking_id', 'nullable', 'exists:units,id'],
            'assigned_to_id' => ['nullable', 'exists:operations_team_members,id'],
            'task_type' => ['required', Rule::in(array_keys(BookingTask::TYPES))],
            'priority' => ['required', Rule::in(array_keys(BookingTask::PRIORITIES))],
            'due_at' => ['nullable', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'pictures.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $booking = ! empty($validated['booking_id']) ? Booking::find($validated['booking_id']) : null;
        $status = ! empty($validated['assigned_to_id']) ? 'assigned' : 'open';

        $task = BookingTask::create([
            'task_number' => $this->nextTaskNumber(),
            'booking_id' => $booking?->id,
            'unit_id' => $booking?->unit_id ?: $validated['unit_id'],
            'assigned_to_id' => $validated['assigned_to_id'] ?? null,
            'task_type' => $validated['task_type'],
            'category' => $validated['task_type'],
            'priority' => $validated['priority'],
            'due_at' => $validated['due_at'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'notes' => $validated['description'] ?? null,
            'pictures' => $this->uploadOptimizedFiles($request, 'pictures', 'booking_task_attachments'),
            'status' => $status,
            'progress' => $status === 'assigned' ? 10 : 0,
        ]);

        $this->event($task, 'created', 'Task created by admin.');
        if ($task->assigned_to_id) {
            $this->event($task, 'assigned', 'Task assigned while creating.');
            $task->load('assignee.user', 'booking');
            $this->notifyAssignee($task, $push);
        }
        $this->updateUnitStatusForTask($task);

        return redirect()->route('tasks.show', $task)->with('status', 'Task created successfully.');
    }

    public function show(BookingTask $bookingTask)
    {
        // The task detail page is the operations record for maintainer status, photos, remarks, and management replies.
        $bookingTask->load([
            'booking.tenant',
            'unit.building',
            'assignee.user',
            'events.user',
            'remarks' => fn ($query) => $query->with(['user', 'replies.user'])->latest(),
            'costItems',
        ]);
        $teamMembers = OperationsTeamMember::orderBy('full_name')->get();

        return view('tasks.show', ['task' => $bookingTask, 'teamMembers' => $teamMembers]);
    }

    public function update(Request $request, BookingTask $bookingTask, PushEventLogger $push)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(BookingTask::STATUSES)],
            'assigned_to_id' => ['nullable', 'exists:operations_team_members,id'],
            'priority' => ['required', Rule::in(array_keys(BookingTask::PRIORITIES))],
            'due_at' => ['nullable', 'date'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'completion_notes' => ['nullable', 'string', 'max:3000'],
            'timeline_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldStatus = $bookingTask->status;
        $oldAssignee = $bookingTask->assigned_to_id;

        $bookingTask->fill($validated);
        if ($validated['status'] === 'accepted' && ! $bookingTask->accepted_at) {
            $bookingTask->accepted_at = now();
        }
        if ($validated['status'] === 'in_progress' && ! $bookingTask->started_at) {
            $bookingTask->started_at = now();
        }
        if (in_array($validated['status'], ['completed', 'closed'], true) && ! $bookingTask->completed_at) {
            $bookingTask->completed_at = now();
        }
        $bookingTask->progress = $validated['progress'] ?? $this->progressForStatus($validated['status']);
        $bookingTask->save();

        $this->event($bookingTask, $oldStatus === $bookingTask->status ? 'updated' : 'status_changed', $validated['timeline_note'] ?: "Task moved from {$oldStatus} to {$bookingTask->status}.");

        if ($bookingTask->assigned_to_id && (int) $oldAssignee !== (int) $bookingTask->assigned_to_id) {
            $bookingTask->load('assignee.user', 'booking');
            $this->notifyAssignee($bookingTask, $push);
        }

        return back()->with('status', 'Task updated.');
    }

    public function addRemark(Request $request, BookingTask $bookingTask)
    {
        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
            'status_update' => ['nullable', Rule::in(['accepted', 'in_progress', 'waiting_approval', 'completed'])],
            'pictures.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $bookingTask->remarks()->create([
            'user_id' => Auth::id(),
            'remark' => $validated['remark'],
            'status_update' => $validated['status_update'] ?? null,
            'pictures' => $this->uploadOptimizedFiles($request, 'pictures', 'booking_task_remark_pictures'),
        ]);

        if (! empty($validated['status_update'])) {
            $bookingTask->update([
                'status' => $validated['status_update'],
                'progress' => $this->progressForStatus($validated['status_update']),
            ]);
        }

        $this->event($bookingTask, 'remark_added', $validated['remark']);

        return back()->with('status', 'Remark added.');
    }

    public function addRemarkReply(Request $request, BookingTask $bookingTask, BookingTaskRemark $bookingTaskRemark)
    {
        abort_unless((int) $bookingTaskRemark->booking_task_id === (int) $bookingTask->id, 404);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:2000'],
        ]);

        // Keep manager responses in the original remark thread instead of creating unrelated timeline items.
        $bookingTaskRemark->replies()->create([
            'user_id' => Auth::id(),
            'reply' => $validated['reply'],
        ]);
        $this->event($bookingTask, 'remark_replied', $validated['reply']);

        return back()->with('status', 'Reply added to maintainer remark.');
    }

    public function addCost(Request $request, BookingTask $bookingTask)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['labor', 'material', 'other'])],
            'label' => ['required', 'string', 'max:255'],
            'worker' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $amount = match ($validated['type']) {
            'labor' => (float) ($validated['hours'] ?? 0) * (float) ($validated['rate'] ?? 0),
            'material' => (float) ($validated['quantity'] ?? 0) * (float) ($validated['unit_price'] ?? 0),
            default => (float) ($validated['amount'] ?? 0),
        };

        $bookingTask->costItems()->create([...$validated, 'amount' => $amount]);
        $bookingTask->recalculateCosts();
        $this->event($bookingTask, 'cost_added', ucfirst($validated['type']) . ' added: AED ' . number_format($amount, 2));

        return back()->with('status', 'Cost added.');
    }

    public function complete(Request $request, BookingTask $bookingTask)
    {
        $validated = $request->validate([
            'completion_notes' => ['required', 'string', 'max:3000'],
            'final_images.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'invoice_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'receipt_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'warranty_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $payload = [
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
            'completion_notes' => $validated['completion_notes'],
            'final_images' => $this->uploadOptimizedFiles($request, 'final_images', 'booking_task_final_pictures'),
        ];

        foreach (['invoice_attachment', 'receipt_attachment', 'warranty_attachment'] as $field) {
            if ($request->hasFile($field)) {
                $payload[$field] = $this->uploadOptimizedFile($request->file($field), 'booking_task_attachments');
            }
        }

        $bookingTask->update($payload);
        $this->event($bookingTask, 'completed', $validated['completion_notes']);
        $this->updateUnitStatusForTask($bookingTask->fresh('unit'));

        return back()->with('status', 'Task completed.');
    }

    public function bookingInspection(Request $request, Booking $booking)
    {
        $tenant = $this->tenantFor($request);
        $canManage = $request->user()?->can('bookings.manage') || $request->user()?->can('booking-tasks.manage');
        abort_unless($canManage || ($tenant && (int) $booking->tenant_id === (int) $tenant->id), 403);

        $booking->load(['unit.building', 'tenant', 'tasks.assignee', 'tasks.events.user', 'checkInInspectionItems']);
        $unitType = strtolower((string) $booking->unit->unit_type);
        $groups = collect(config('inspection-groups', []))->first(fn ($items, string $key): bool => $key !== 'default' && str_contains($unitType, $key)) ?: config('inspection-groups.default', []);

        return view('bookings.inspection', [
            'booking' => $booking,
            'groups' => $groups,
            'tenantPortal' => $tenant && ! $canManage,
            'conditionOptions' => ['good', 'damaged', 'missing', 'needs_attention'],
            'existingItems' => $booking->checkInInspectionItems->keyBy(fn (CheckInInspectionItem $item): string => $item->area.'|'.$item->item),
        ]);
    }

    public function submitTenantCheckIn(Request $request, Booking $booking, PushEventLogger $push)
    {
        $tenant = $this->tenantFor($request);
        abort_unless($tenant && (int) $booking->tenant_id === (int) $tenant->id, 403);

        $this->saveInspectionItems($request, $booking);
        $task = $booking->tasks()->firstOrCreate(
            ['task_type' => 'tenant_checkin_review'],
            ['unit_id' => $booking->unit_id, 'title' => "Review tenant check-in report for Unit {$booking->unit->unit_no}", 'due_at' => now()->addHours(4), 'status' => 'open', 'priority' => 'normal', 'notes' => 'Tenant submitted apartment condition report from mobile portal.']
        );
        $this->event($task, 'tenant_checkin_submitted', 'Tenant submitted check-in condition report.');

        $push->toUserIds(
            \App\Models\User::permission('booking-tasks.manage')->pluck('id'),
            'Tenant check-in report submitted',
            "{$tenant->full_name} submitted the apartment condition report for {$booking->booking_no}.",
            ['type' => 'tenant_checkin_report', 'booking_id' => $booking->id, 'url' => route('bookings.inspection', $booking)],
            $booking
        );

        return back()->with('status', 'Check-in condition report submitted.');
    }

    public function submitBookingInspection(Request $request, Booking $booking)
    {
        abort_unless($request->user()?->can('bookings.manage') || $request->user()?->can('booking-tasks.manage'), 403);
        $validated = $this->saveInspectionItems($request, $booking);
        $task = $booking->tasks()->firstOrCreate(
            ['task_type' => 'checkout_inspection'],
            ['unit_id' => $booking->unit_id, 'title' => "Full apartment inspection for Unit {$booking->unit->unit_no}", 'due_at' => now(), 'status' => 'open', 'priority' => 'normal', 'notes' => 'Full apartment inspection checklist created from booking page.']
        );
        $this->event($task, 'inspection_saved', $validated['completion_notes'] ?? 'Full apartment inspection checklist saved.');
        ActivityLogger::log('booking_tasks.inspection_saved', "Saved full apartment inspection for {$booking->booking_no}.", $booking);

        return back()->with('status', 'Full apartment inspection saved.');
    }

    public function submitCheckoutInspection(Request $request, BookingTask $bookingTask)
    {
        abort_unless($bookingTask->task_type === 'checkout_inspection', 404);
        $validated = $this->saveInspectionItems($request, $bookingTask->booking);
        $bookingTask->update(['status' => 'completed', 'progress' => 100, 'completed_at' => now(), 'completion_notes' => $validated['completion_notes'] ?? 'Checkout inspection completed.']);
        $this->event($bookingTask, 'checkout_inspection_completed', 'Checkout inspection checklist completed.');

        return back()->with('status', 'Checkout inspection checklist completed.');
    }

    private function taskQuery(Request $request)
    {
        return BookingTask::with(['booking.tenant', 'unit.building', 'assignee', 'events.user', 'remarks.user'])
            ->when($request->filled('status'), fn ($query) => match ($request->input('status')) {
                'pending' => $query->whereIn('status', ['open', 'assigned']),
                'overdue' => $query->whereNotIn('status', ['completed', 'closed', 'cancelled'])->where('due_at', '<', now()),
                default => $query->where('status', $request->input('status')),
            })
            ->when($request->filled('task_type'), fn ($query) => $query->where('task_type', $request->input('task_type')))
            ->when($request->filled('assigned_to_id'), fn ($query) => $query->where('assigned_to_id', $request->input('assigned_to_id')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->input('q');
                $query->where(fn ($inner) => $inner->where('title', 'like', "%{$search}%")->orWhere('task_number', 'like', "%{$search}%"));
            })
            ->orderByRaw("case when status in ('open','assigned') then 0 when status = 'accepted' then 1 when status = 'in_progress' then 2 when status = 'waiting_approval' then 3 when status in ('completed','closed') then 4 else 5 end")
            ->orderByRaw('due_at is null, due_at asc')
            ->latest();
    }

    private function saveInspectionItems(Request $request, Booking $booking): array
    {
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
                ['condition_status' => $item['condition_status'], 'notes' => $item['notes'] ?? null]
            );
        }

        return $validated;
    }

    private function notifyAssignee(BookingTask $task, PushEventLogger $push): void
    {
        if (! $task->assignee?->user_id) {
            return;
        }

        $push->toUserIds(
            collect([$task->assignee->user_id]),
            'New task assigned',
            "{$task->title} is assigned to you.",
            ['type' => 'task', 'task_id' => $task->id, 'url' => route('maintainer.tasks.show', $task)],
            $task->booking
        );
    }

    private function progressForStatus(string $status): int
    {
        return match ($status) {
            'assigned' => 10,
            'accepted' => 25,
            'in_progress' => 55,
            'waiting_approval' => 80,
            'completed', 'closed' => 100,
            default => 0,
        };
    }

    private function event(BookingTask $task, string $type, ?string $description): void
    {
        $task->events()->create(['user_id' => Auth::id(), 'event_type' => $type, 'description' => $description]);
    }

    private function nextTaskNumber(): string
    {
        do {
            $number = 'TSK-' . now()->format('ymd') . '-' . strtoupper(substr((string) str()->uuid(), 0, 4));
        } while (BookingTask::where('task_number', $number)->exists());

        return $number;
    }

    private function updateUnitStatusForTask(BookingTask $task): void
    {
        if (! $task->unit_id || ! in_array($task->task_type, ['checkout_cleaning', 'cleaning', 'maintenance'], true)) {
            return;
        }

        if ($task->status === 'completed') {
            $openTasks = BookingTask::where('unit_id', $task->unit_id)->whereNotIn('status', ['completed', 'closed', 'cancelled'])->exists();
            Unit::whereKey($task->unit_id)->update(['availability_status' => $openTasks ? 'maintenance' : 'available']);
            return;
        }

        Unit::whereKey($task->unit_id)->update(['availability_status' => $task->task_type === 'checkout_cleaning' || $task->task_type === 'cleaning' ? 'blocked' : 'maintenance']);
    }

    private function tenantFor(Request $request): ?Tenant
    {
        return Tenant::query()->where('user_id', $request->user()->id)->orWhere('email', $request->user()->email)->first();
    }

    private function uploadOptimizedFiles(Request $request, string $field, string $folder): array
    {
        if (! $request->hasFile($field)) {
            return [];
        }

        return collect((array) $request->file($field))->map(fn ($file) => $this->uploadOptimizedFile($file, $folder))->all();
    }

    private function uploadOptimizedFile($file, string $folder): string
    {
        $destination = public_path($folder);
        if (! file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) && function_exists('imagewebp')) {
            $image = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                'png' => @imagecreatefrompng($file->getRealPath()),
                'webp' => @imagecreatefromwebp($file->getRealPath()),
                default => null,
            };

            if ($image) {
                $width = imagesx($image);
                $height = imagesy($image);
                if ($width > 1280) {
                    $newWidth = 1280;
                    $newHeight = (int) round($height * (1280 / $width));
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resized;
                }

                $filename = uniqid() . '.webp';
                imagewebp($image, $destination . DIRECTORY_SEPARATOR . $filename, 78);
                imagedestroy($image);

                return $folder . '/' . $filename;
            }
        }

        $filename = uniqid() . '.' . $extension;
        $file->move($destination, $filename);

        return $folder . '/' . $filename;
    }
}
