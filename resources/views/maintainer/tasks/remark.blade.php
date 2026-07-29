<x-dynamic-component component="layouts.maintainer-pwa">
    <header class="app-topbar">
        <a href="{{ route('maintainer.tasks.show', $task) }}" class="grid h-10 w-10 place-items-center rounded-xl" aria-label="Back"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
        <h1 class="font-black text-[#071a3b]">Add Remark</h1>
        <span class="h-10 w-10"></span>
    </header>

    <main class="app-scroll px-5 pt-5">
        @if($errors->any())<div class="mb-3 rounded-2xl bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
        {{-- data-mobile-photo-upload enables progress reporting for the shared mobile image component. --}}
        <form method="POST" action="{{ route('maintainer.tasks.remarks.store', $task) }}" enctype="multipart/form-data" data-mobile-photo-upload class="space-y-5">
            @csrf
            <label class="block text-sm font-semibold text-slate-700">Remark <span class="text-red-500">*</span>
                <textarea name="remark" required rows="5" class="app-input mt-2 p-3" placeholder="Checked the AC filter. It was very dirty. Cleaned it.">{{ old('remark') }}</textarea>
            </label>
            <label class="block text-sm font-semibold text-slate-700">Status Update
                <select name="status_update" class="app-input mt-2 h-12 px-3">
                    <option value="">No status change</option>
                    <option value="accepted">Accepted</option>
                    <option value="in_progress">In Progress</option>
                    <option value="waiting_approval">Waiting Approval</option>
                    <option value="completed">Completed</option>
                </select>
            </label>
            {{-- Reusable camera/library photo field with previews and upload progress. --}}
            @include('maintainer.partials.mobile-image-upload', ['field' => 'pictures', 'label' => 'Photos'])
            <button class="app-button bg-[#6d3be8] text-white shadow-lg shadow-purple-600/20">Add Remark</button>
        </form>
    </main>
</x-dynamic-component>
