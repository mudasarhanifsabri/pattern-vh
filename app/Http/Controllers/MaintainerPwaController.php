<?php

namespace App\Http\Controllers;

use App\Models\BookingTask;
use App\Models\OperationsTeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MaintainerPwaController extends Controller
{
    public function dashboard()
    {
        return redirect()->route('maintainer.tasks.index');
    }

    public function index(Request $request)
    {
        $member = $this->member();
        if (! $member) {
            return $this->setupRequired();
        }

        $tasks = $this->assignedTaskQuery($member, $request)->paginate(20)->withQueryString();
        $stats = [
            'total' => $this->baseQuery($member)->count(),
            'in_progress' => $this->baseQuery($member)->where('status', 'in_progress')->count(),
            'completed' => $this->baseQuery($member)->whereIn('status', ['completed', 'closed'])->count(),
            'overdue' => $this->baseQuery($member)->whereNotIn('status', ['completed', 'closed', 'cancelled'])->where('due_at', '<', now())->count(),
        ];

        return view('maintainer.tasks.index', compact('member', 'tasks', 'stats'));
    }

    public function show(BookingTask $task)
    {
        $this->authorizeTask($task);
        $task->load(['booking.tenant', 'unit.building', 'assignee', 'events.user', 'remarks.user', 'costItems']);

        return view('maintainer.tasks.show', compact('task'));
    }

    public function acceptForm(BookingTask $task)
    {
        $this->authorizeTask($task);

        return view('maintainer.tasks.accept', ['task' => $this->taskForScreen($task)]);
    }

    public function remarkForm(BookingTask $task)
    {
        $this->authorizeTask($task);

        return view('maintainer.tasks.remark', ['task' => $this->taskForScreen($task)]);
    }

    public function timeline(BookingTask $task)
    {
        $this->authorizeTask($task);

        return view('maintainer.tasks.timeline', ['task' => $this->taskForScreen($task)]);
    }

    public function costForm(BookingTask $task)
    {
        $this->authorizeTask($task);

        return view('maintainer.tasks.cost', ['task' => $this->taskForScreen($task)]);
    }

    public function completeForm(BookingTask $task)
    {
        $this->authorizeTask($task);

        return view('maintainer.tasks.complete', ['task' => $this->taskForScreen($task)]);
    }

    public function profile()
    {
        $member = $this->member();
        if (! $member) {
            return $this->setupRequired();
        }

        return view('maintainer.profile', ['member' => $member, 'user' => Auth::user()]);
    }

    public function notifications()
    {
        $member = $this->member();
        if (! $member) {
            return $this->setupRequired();
        }
        $tasks = $this->baseQuery($member)->whereIn('status', ['open', 'assigned'])->latest()->get();

        return view('maintainer.notifications', compact('member', 'tasks'));
    }

    public function liveTasks()
    {
        $member = $this->member();
        if (! $member) {
            return response()->json(['tasks' => [], 'message' => 'No operations team member is linked to this login.']);
        }
        $tasks = $this->baseQuery($member)
            ->whereIn('status', ['open', 'assigned'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (BookingTask $task) => [
                'id' => $task->id,
                'number' => $task->task_display_number,
                'title' => $task->title,
                'unit' => trim(($task->unit?->building?->name ? $task->unit->building->name . ' - ' : '') . ($task->unit?->unit_no ?? 'Unit')),
                'priority' => $task->priority_label,
                'status' => $task->status_label,
                'url' => route('maintainer.tasks.show', $task),
                'updated_at' => $task->updated_at?->timestamp,
            ]);

        return response()->json(['tasks' => $tasks]);
    }

    public function accept(Request $request, BookingTask $task)
    {
        $this->authorizeTask($task);
        $validated = $request->validate([
            'expected_completion_date' => ['required', 'date', 'after_or_equal:today'],
            'initial_remark' => ['nullable', 'string', 'max:2000'],
            'pictures.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $task->update(['status' => 'accepted', 'progress' => 25, 'accepted_at' => now(), 'expected_completion_date' => $validated['expected_completion_date']]);
        if (filled($validated['initial_remark'] ?? null) || $request->hasFile('pictures')) {
            $task->remarks()->create(['user_id' => Auth::id(), 'remark' => $validated['initial_remark'] ?: 'Task accepted.', 'status_update' => 'accepted', 'pictures' => $this->uploadOptimizedFiles($request, 'pictures', 'booking_task_remark_pictures')]);
        }
        $this->event($task, 'accepted', $validated['initial_remark'] ?? 'Task accepted.');

        return redirect()->route('maintainer.tasks.show', $task)->with('status', 'Task accepted.');
    }

    public function start(Request $request, BookingTask $task)
    {
        $this->authorizeTask($task);
        $task->update(['status' => 'in_progress', 'progress' => 55, 'started_at' => now()]);
        $this->event($task, 'started', $request->input('comment'));

        return back()->with('status', 'Task started.');
    }

    public function remark(Request $request, BookingTask $task)
    {
        $this->authorizeTask($task);
        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
            'status_update' => ['nullable', Rule::in(['accepted', 'in_progress', 'waiting_approval', 'completed'])],
            'pictures.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $task->remarks()->create(['user_id' => Auth::id(), 'remark' => $validated['remark'], 'status_update' => $validated['status_update'] ?? null, 'pictures' => $this->uploadOptimizedFiles($request, 'pictures', 'booking_task_remark_pictures')]);
        if (! empty($validated['status_update'])) {
            $task->update(['status' => $validated['status_update'], 'progress' => $this->progressForStatus($validated['status_update'])]);
        }
        $this->event($task, 'remark_added', $validated['remark']);

        return redirect()->route('maintainer.tasks.show', $task)->with('status', 'Remark added.');
    }

    public function cost(Request $request, BookingTask $task)
    {
        $this->authorizeTask($task);
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
        $task->costItems()->create([...$validated, 'amount' => $amount]);
        $task->recalculateCosts();
        $this->event($task, 'cost_added', 'Cost added: AED ' . number_format($amount, 2));

        return redirect()->route('maintainer.tasks.show', $task)->with('status', 'Cost added.');
    }

    public function complete(Request $request, BookingTask $task)
    {
        $this->authorizeTask($task);
        $validated = $request->validate([
            'completion_notes' => ['required', 'string', 'max:3000'],
            'final_images.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'invoice_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'receipt_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $payload = ['status' => 'completed', 'progress' => 100, 'completed_at' => now(), 'completion_notes' => $validated['completion_notes'], 'final_images' => $this->uploadOptimizedFiles($request, 'final_images', 'booking_task_final_pictures')];
        foreach (['invoice_attachment', 'receipt_attachment'] as $field) {
            if ($request->hasFile($field)) {
                $payload[$field] = $this->uploadOptimizedFile($request->file($field), 'booking_task_attachments');
            }
        }
        $task->update($payload);
        $this->event($task, 'completed', $validated['completion_notes']);

        return redirect()->route('maintainer.tasks.index')->with('status', 'Task completed.');
    }

    private function assignedTaskQuery(OperationsTeamMember $member, Request $request)
    {
        return $this->baseQuery($member)
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($inner) => $inner->where('title', 'like', '%' . $request->q . '%')->orWhere('task_number', 'like', '%' . $request->q . '%')))
            ->when($request->filled('status'), fn ($query) => match ($request->status) {
                'assigned' => $query->whereIn('status', ['open', 'assigned']),
                'completed' => $query->whereIn('status', ['completed', 'closed']),
                default => $query->where('status', $request->status),
            });
    }

    private function baseQuery(OperationsTeamMember $member)
    {
        return BookingTask::with(['booking.tenant', 'unit.building', 'remarks.user'])
            ->where('assigned_to_id', $member->id)
            ->orderByRaw("case when status in ('open','assigned') then 0 when status = 'accepted' then 1 when status = 'in_progress' then 2 when status = 'completed' then 4 else 3 end")
            ->orderByRaw('due_at is null, due_at asc')
            ->latest();
    }

    private function member(): ?OperationsTeamMember
    {
        return OperationsTeamMember::where('user_id', Auth::id())->orWhere('email', Auth::user()?->email)->first();
    }

    private function authorizeTask(BookingTask $task): void
    {
        abort_unless($this->member()?->id === $task->assigned_to_id, 403);
    }

    private function taskForScreen(BookingTask $task): BookingTask
    {
        return $task->load(['booking.tenant', 'unit.building', 'assignee', 'events.user', 'remarks.user', 'costItems']);
    }

    private function setupRequired()
    {
        return response()->view('maintainer.setup-required', ['user' => Auth::user()], 403);
    }

    private function progressForStatus(string $status): int
    {
        return match ($status) {
            'accepted' => 25,
            'in_progress' => 55,
            'waiting_approval' => 80,
            'completed' => 100,
            default => 10,
        };
    }

    private function event(BookingTask $task, string $type, ?string $description): void
    {
        $task->events()->create(['user_id' => Auth::id(), 'event_type' => $type, 'description' => $description]);
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
                    $resized = imagecreatetruecolor(1280, (int) round($height * (1280 / $width)));
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, 1280, imagesy($resized), $width, $height);
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
