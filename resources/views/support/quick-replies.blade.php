<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Support automation</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Quick replies</h1>
                <p class="mt-2 text-sm text-slate-500">Reusable WhatsApp-ready answers for staff inside the chat composer.</p>
            </div>
            <a href="{{ route('support.reports') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700">Back to reports</a>
        </div>
    </x-slot>

    <div class="grid gap-5 xl:grid-cols-[420px_1fr]">
        <form method="POST" action="{{ route('support.quick-replies.store') }}" class="erp-card p-5">
            @csrf
            @if(session('status'))<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif
            <h2 class="text-lg font-black text-[#071a3b]">Add quick reply</h2>
            <div class="mt-4 space-y-3">
                <select name="support_category_id" class="erp-focus h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                <input name="title" class="erp-focus h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm" placeholder="Reply title" required>
                <textarea name="body" rows="7" class="erp-focus w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="Reply text / WhatsApp-ready template" required></textarea>
                <button class="w-full rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white">Save quick reply</button>
            </div>
        </form>

        <section class="erp-card overflow-hidden">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-lg font-black text-[#071a3b]">Saved replies</h2>
            </div>
            <div class="grid gap-3 p-5 md:grid-cols-2">
                @forelse($quickReplies as $reply)
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex justify-between gap-3">
                            <p class="text-sm font-black text-[#071a3b]">{{ $reply->title }}</p>
                            <span class="shrink-0 rounded-full bg-blue-100 px-3 py-1 text-[10px] font-black text-blue-700">{{ $reply->category?->name ?: 'All' }}</span>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-xs leading-5 text-slate-500">{{ $reply->body }}</p>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 md:col-span-2">No quick replies saved yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
