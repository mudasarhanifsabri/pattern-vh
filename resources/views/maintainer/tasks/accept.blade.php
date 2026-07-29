<x-dynamic-component component="layouts.maintainer-pwa">
    <header class="app-topbar">
        <a href="{{ route('maintainer.tasks.show', $task) }}" class="grid h-10 w-10 place-items-center rounded-xl" aria-label="Back"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
        <h1 class="font-black text-[#071a3b]">Accept Task</h1>
        <span class="h-10 w-10"></span>
    </header>

    <main class="app-scroll px-5 pt-5">
        @if($errors->any())<div class="mb-3 rounded-2xl bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
        {{-- data-mobile-photo-upload enables progress reporting for the shared mobile image component. --}}
        <form method="POST" action="{{ route('maintainer.tasks.accept', $task) }}" enctype="multipart/form-data" data-mobile-photo-upload class="space-y-5">
            @csrf
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                You are about to accept this task. Update the expected completion date and add initial remarks.
            </div>
            <label class="block text-sm font-semibold text-slate-700">Expected Completion Date <span class="text-red-500">*</span>
                <input type="date" name="expected_completion_date" value="{{ old('expected_completion_date', now()->toDateString()) }}" required class="app-input mt-2 h-12 px-3">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Initial Remark
                <textarea name="initial_remark" rows="5" class="app-input mt-2 p-3" placeholder="I will check and update.">{{ old('initial_remark') }}</textarea>
            </label>
            {{-- Reusable camera/library photo field with previews and upload progress. --}}
            @include('maintainer.partials.mobile-image-upload', ['field' => 'pictures', 'label' => 'Evidence Photos'])
            <button class="app-button bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">Accept Task</button>
        </form>
    </main>
</x-dynamic-component>
