<x-dynamic-component component="layouts.maintainer-pwa">
    <header class="app-topbar">
        <a href="{{ route('maintainer.tasks.show', $task) }}" class="grid h-10 w-10 place-items-center rounded-xl" aria-label="Back"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
        <h1 class="font-black text-[#071a3b]">Complete Task</h1>
        <span class="h-10 w-10"></span>
    </header>

    <main class="app-scroll px-5 pt-5">
        @if($errors->any())<div class="mb-3 rounded-2xl bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
        {{-- data-mobile-photo-upload enables progress reporting for the shared mobile image component. --}}
        <form method="POST" action="{{ route('maintainer.tasks.complete', $task) }}" enctype="multipart/form-data" data-mobile-photo-upload class="space-y-5">
            @csrf
            <label class="block text-sm font-semibold text-slate-700">Completion Notes <span class="text-red-500">*</span>
                <textarea name="completion_notes" required rows="4" class="app-input mt-2 p-3" placeholder="AC is working fine now. Gas refilled and tested.">{{ old('completion_notes') }}</textarea>
            </label>
            {{-- Reusable camera/library photo field with previews and upload progress. --}}
            @include('maintainer.partials.mobile-image-upload', ['field' => 'final_images', 'label' => 'Completion Photos'])
            <div class="space-y-3">
                <p class="text-sm font-semibold text-slate-700">Attachments</p>
                <label class="block rounded-xl bg-slate-50 p-3 text-sm font-semibold text-slate-700">Invoice attachment<input type="file" name="invoice_attachment" class="mt-2 block w-full text-xs"></label>
                <label class="block rounded-xl bg-slate-50 p-3 text-sm font-semibold text-slate-700">Receipt attachment<input type="file" name="receipt_attachment" class="mt-2 block w-full text-xs"></label>
            </div>
            <label class="block text-sm font-semibold text-slate-700">Final Status
                <select class="app-input mt-2 h-12 px-3" disabled><option>Completed</option></select>
            </label>
            <button class="app-button bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">Complete Task</button>
        </form>
    </main>
</x-dynamic-component>
