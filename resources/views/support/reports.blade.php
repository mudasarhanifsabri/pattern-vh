<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Support analytics</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Support reports</h1>
                <p class="mt-2 text-sm text-slate-500">Ticket health, response performance, staff workload, and category-wise issues.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('support.manage')
                    <a href="{{ route('support.quick-replies.index') }}" class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-black text-blue-700">Quick replies</a>
                    <a href="{{ route('support.auto-reply-rules.index') }}" class="rounded-xl border border-violet-200 px-4 py-2.5 text-sm font-black text-violet-700">Auto replies</a>
                @endcan
                <a href="{{ route('support.index') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white">Inbox</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        <div class="grid gap-4 md:grid-cols-4">
            @foreach([['Total tickets',$stats['total']],['Open / unresolved',$stats['open']],['Resolved',$stats['resolved']],['Avg response',$stats['average_response_minutes'].' min']] as [$label,$value])
                <div class="erp-card p-5">
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-black text-[#071a3b]">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="erp-card p-5">
                <h2 class="text-lg font-black text-[#071a3b]">Category-wise issues</h2>
                <div class="mt-4 space-y-3">
                    @forelse($categoryRows as $category)
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                            <span class="text-sm font-bold">{{ $category->name }}</span>
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700">{{ $category->tickets_count }}</span>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">No support categories yet.</p>
                    @endforelse
                </div>
            </section>
            <section class="erp-card p-5">
                <h2 class="text-lg font-black text-[#071a3b]">Staff performance</h2>
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs text-slate-500"><tr><th class="px-4 py-3">Staff</th><th class="px-4 py-3">Assigned</th><th class="px-4 py-3">Replies</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($staffRows as $person)
                                <tr><td class="px-4 py-3 font-bold">{{ $person->name }}</td><td class="px-4 py-3">{{ $person->assigned_count }}</td><td class="px-4 py-3">{{ $person->reply_count }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">No support staff found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
