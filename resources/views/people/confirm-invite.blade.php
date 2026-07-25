<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">{{ $config['pluralTitle'] ?? 'Portal access' }}</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Send welcome email</h1>
        </div>
    </x-slot>

    <section class="mx-auto max-w-2xl rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">Please confirm before sending a portal welcome email.</p>
        <div class="mt-5 rounded-2xl bg-slate-50 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $config['singularTitle'] ?? 'Record' }}</p>
            <h2 class="mt-2 text-xl font-black text-[#071a3b]">{{ $record->full_name }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $record->email ?: 'No email added' }}</p>
        </div>

        @if (! $record->email)
            <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">Add an email before sending the welcome link.</div>
        @endif

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <a href="{{ route(($config['route'] ?? 'dashboard').'.show', $record) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50">Cancel</a>
            @if ($record->email)
                <form method="POST" action="{{ route($config['route'].'.send-invite', $record) }}">
                    @csrf
                    <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">Send welcome email</button>
                </form>
            @endif
        </div>
    </section>
</x-app-layout>
