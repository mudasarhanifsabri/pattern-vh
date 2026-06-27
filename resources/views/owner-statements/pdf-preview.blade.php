<x-app-layout>
@php
    $ownerOnly = auth()->user()?->can('portal.owner')
        && ! auth()->user()?->can('accounting.view')
        && ! auth()->user()?->can('accounting.manage')
        && ! auth()->user()?->can('users.manage');
@endphp

<x-slot name="header">
    <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Owner statement</p>
        <h1 class="text-2xl font-bold text-[#071a3b]">Statement PDF</h1>
    </div>
</x-slot>

<div class="{{ $ownerOnly ? 'tenant-app-screen' : '' }} min-h-[calc(100dvh-9rem)] space-y-4">
    <div class="sticky top-0 z-20 -mx-4 border-b border-slate-200 bg-[#f7f9fe]/95 px-4 py-3 backdrop-blur lg:static lg:mx-0 lg:rounded-2xl lg:border lg:bg-white">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ $backUrl }}" class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-black text-[#071a3b]">
                Back
            </a>
            <div class="min-w-0 flex-1 text-center">
                <p class="truncate text-sm font-black text-[#071a3b]">{{ $owner->full_name }}</p>
                <p class="text-xs font-semibold text-slate-500">{{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p>
            </div>
            <a href="{{ $pdfUrl }}" download class="inline-flex h-11 items-center justify-center rounded-2xl bg-blue-600 px-4 text-sm font-black text-white">
                Save
            </a>
        </div>
    </div>

    <section class="overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
        <iframe src="{{ $pdfUrl }}#toolbar=0&navpanes=0" title="Owner statement PDF" class="h-[calc(100dvh-11rem)] w-full bg-white lg:min-h-[620px]"></iframe>
    </section>
</div>
</x-app-layout>
