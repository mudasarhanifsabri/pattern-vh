<x-app-layout>
    <x-slot name="header">
        <div class="hidden items-center justify-between gap-3 lg:flex">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Communication workspace</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Support Center</h1>
                <p class="mt-2 text-sm text-slate-500">Live chat, tickets, quick replies, and customer context in one inbox.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-enable-support-alerts class="rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm font-black text-[#071a3b] shadow-sm">Enable alerts</button>
                <a href="{{ route('support.create') }}" class="rounded-2xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-200">New request</a>
                <a href="{{ route('support.public.create') }}" target="_blank" class="rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm font-black text-blue-700">Public link</a>
                @if($manage && auth()->user()->can('support.reports'))
                    <a href="{{ route('support.reports') }}" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-black text-white">Reports</a>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $statusTone = [
            'open' => 'bg-blue-50 text-blue-700',
            'waiting_for_customer' => 'bg-amber-50 text-amber-700',
            'in_progress' => 'bg-violet-50 text-violet-700',
            'resolved' => 'bg-emerald-50 text-emerald-700',
            'closed' => 'bg-slate-100 text-slate-600',
        ];
        $priorityTone = [
            'low' => 'text-slate-500',
            'medium' => 'text-blue-600',
            'high' => 'text-amber-600',
            'urgent' => 'text-rose-600',
        ];
        $tenantChat = auth()->user()?->can('portal.tenant') && ! auth()->user()?->can('bookings.manage');
        $ownerChat = auth()->user()?->can('portal.owner')
            && ! auth()->user()?->can('accounting.view')
            && ! auth()->user()?->can('accounting.manage')
            && ! auth()->user()?->can('users.manage');
        $portalChat = $tenantChat || $ownerChat;
        $portalSelectedChat = $portalChat && $selected;
        $supportTopbar = $portalSelectedChat ? '0px' : ($portalChat ? '5.5rem' : '5rem');
        $selectedOnline = $selected?->requester?->onlineStatus?->is_online
            && $selected->requester->onlineStatus->last_seen_at?->greaterThan(now()->subMinutes(3));
        $selectedName = $selected?->requester_name ?: 'Support customer';
        $assigneeOnline = $selected?->assignee?->onlineStatus?->is_online
            && $selected->assignee->onlineStatus->last_seen_at?->greaterThan(now()->subMinutes(3));
        $chatPartnerName = $tenantChat ? ($selected?->assignee?->name ?: 'Pattern Support') : $selectedName;
        $chatPartnerOnline = $tenantChat ? $assigneeOnline : $selectedOnline;
    @endphp

    <div class="space-y-4" style="--support-topbar-height: {{ $supportTopbar }}">
        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>
        @endif

        <div data-support-shell class="support-mobile-shell support-desktop-shell grid min-h-[calc(100dvh-190px)] overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/70 lg:relative lg:z-auto lg:min-h-0 lg:grid-cols-[390px_minmax(460px,1fr)_360px]">
            <aside class="support-mobile-pane {{ $selected ? 'hidden lg:flex' : 'flex' }} min-h-0 flex-col border-b border-slate-100 bg-white lg:border-b-0 lg:border-r">
                <div class="shrink-0 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-600 lg:hidden">Support Center</p>
                            <h2 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Chats</h2>
                        </div>
                        <a href="{{ route('support.create') }}" class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-2xl font-light text-[#071a3b]">+</a>
                    </div>

                    <form method="GET" class="mt-5">
                        <div class="flex items-center gap-3 rounded-full border border-slate-100 bg-white px-4 py-3 shadow-sm shadow-slate-200/70">
                            <svg class="h-5 w-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input name="search" value="{{ request('search') }}" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:ring-0" placeholder="Search Messenger">
                        </div>
                    </form>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ route('support.index') }}" class="rounded-full bg-blue-100 px-4 py-2 text-sm font-black text-blue-700">All</a>
                        <a href="{{ route('support.index', ['status' => 'open']) }}" class="rounded-full px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-50">Unread</a>
                        <span class="rounded-full px-4 py-2 text-sm font-bold text-slate-500">Groups</span>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-3 pb-4">
                    @forelse($tickets as $ticket)
                        @php
                            $last = $ticket->messages->first();
                            $requesterOnline = $ticket->requester?->onlineStatus?->is_online
                                && $ticket->requester->onlineStatus->last_seen_at?->greaterThan(now()->subMinutes(3));
                            $active = $selected?->id === $ticket->id;
                        @endphp
                        <a href="{{ route('support.index', ['ticket' => $ticket->id]) }}" class="mb-2 flex items-center gap-4 rounded-[1.4rem] p-3 transition {{ $active ? 'bg-blue-50' : 'hover:bg-slate-50' }}">
                            <span class="relative grid h-16 w-16 shrink-0 place-items-center rounded-full bg-gradient-to-br from-blue-100 to-violet-100 text-sm font-black text-blue-700">
                                {{ str($ticket->requester_name)->substr(0, 2)->upper() }}
                                <span class="absolute bottom-1 right-1 h-4 w-4 rounded-full border-2 border-white {{ $requesterOnline ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-3">
                                    <span class="truncate text-lg font-black leading-tight text-[#071a3b]">{{ $ticket->requester_name }}</span>
                                    <span class="shrink-0 text-xs text-slate-400">{{ $ticket->updated_at->diffForHumans(null, true) }}</span>
                                </span>
                                <span class="mt-1 flex min-w-0 items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-slate-500">{{ $last?->sender_type === 'staff' ? 'You: ' : '' }}{{ $last?->body ?: $ticket->subject }}</span>
                                    <span class="shrink-0 text-xs font-black {{ $priorityTone[$ticket->priority] ?? 'text-slate-500' }}">{{ str($ticket->priority)->headline() }}</span>
                                </span>
                                <span class="mt-2 inline-flex rounded-full bg-white px-2 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">{{ $ticket->requester_role ?: 'Guest' }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-200 p-8 text-center">
                            <p class="text-sm font-bold text-slate-500">No conversations found.</p>
                            <a href="{{ route('support.create') }}" class="mt-4 inline-flex rounded-2xl bg-blue-600 px-4 py-2 text-sm font-black text-white">Create support request</a>
                        </div>
                    @endforelse
                </div>
            </aside>

            <main class="support-mobile-pane {{ $selected ? 'flex' : 'hidden lg:flex' }} min-h-0 min-w-0 flex-col overflow-hidden bg-white">
                @if($selected)
                    <header class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3 {{ $tenantChat ? 'pt-[calc(env(safe-area-inset-top)+0.75rem)]' : '' }}">
                        <div class="flex min-w-0 items-center gap-3">
                            <a href="{{ route('support.index') }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border border-slate-100 text-xl font-black text-[#071a3b] lg:hidden">&lsaquo;</a>
                            <span class="relative grid h-12 w-12 shrink-0 place-items-center rounded-full bg-gradient-to-br from-blue-100 to-violet-100 text-sm font-black text-blue-700">
                                {{ str($chatPartnerName)->substr(0, 2)->upper() }}
                                <span class="absolute bottom-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-white {{ $chatPartnerOnline ? 'bg-emerald-500' : 'bg-emerald-400' }}"></span>
                            </span>
                            <span class="min-w-0">
                                <h2 class="truncate text-lg font-black text-[#071a3b]">{{ $tenantChat ? 'Support Team' : $selectedName }}</h2>
                                <p class="text-sm font-bold text-emerald-600" data-chat-presence>{{ $tenantChat ? ($selected->assigned_to ? $chatPartnerName.' is connected' : 'Connecting you with an agent') : ($selectedOnline ? 'Active now' : 'Offline') }}</p>
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-blue-600">
                            <button type="button" class="grid h-10 w-10 place-items-center rounded-full hover:bg-blue-50" aria-label="Call">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.7.6 2.5a2 2 0 0 1-.5 2.1L8 9.5a16 16 0 0 0 6.5 6.5l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.6.5 2.5.6A2 2 0 0 1 22 16.9Z"/></svg>
                            </button>
                            <button type="button" class="grid h-10 w-10 place-items-center rounded-full hover:bg-blue-50" aria-label="Video">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10.5V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-4.5l7 4v-11l-7 4Z"/></svg>
                            </button>
                        </div>
                    </header>

                    <div class="support-message-scroll min-h-0 min-w-0 flex-1 overflow-y-auto overflow-x-hidden bg-white px-4 py-4" data-support-messages data-message-count="{{ $selected->messages->count() }}">
                        @if($tenantChat)
                            <div class="mb-4 rounded-[1.4rem] bg-gradient-to-br from-blue-50 to-slate-50 p-3 text-center">
                                <div class="mx-auto grid h-10 w-10 place-items-center rounded-full bg-white text-blue-600 shadow-sm">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 10a6 6 0 0 0-12 0v4a3 3 0 0 0 3 3h1" /><path d="M18 14v2a2 2 0 0 1-2 2h-2" /></svg>
                                </div>
                                <h3 class="mt-2 text-sm font-black text-[#071a3b]">Welcome to Pattern live chat</h3>
                                <p class="mx-auto mt-1 max-w-[300px] text-xs font-semibold leading-5 text-slate-500">Ask about booking, payment, check-in, checkout, deposit, Wi-Fi, or maintenance.</p>
                                <div class="mt-2 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-black text-emerald-700">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Live chat active
                                </div>
                            </div>
                        @endif
                        @php
                            $lastDate = null;
                        @endphp
                        @foreach($selected->messages as $message)
                            @php
                                $mine = $manage ? $message->sender_type === 'staff' : $message->sender_type === 'customer';
                                $messageDate = $message->created_at->format('M d, Y');
                            @endphp
                            @if($lastDate !== $messageDate)
                                <div class="my-4 text-center text-xs font-semibold text-slate-400">{{ $messageDate }}</div>
                                @php
                                    $lastDate = $messageDate;
                                @endphp
                            @endif
                            <div class="mb-3 flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div class="min-w-0 max-w-[78vw] overflow-hidden rounded-[1.25rem] px-4 py-3 shadow-sm sm:max-w-[76%] lg:max-w-[70%] {{ $message->is_internal_note ? 'border border-amber-200 bg-amber-50 text-slate-700' : ($mine ? 'bg-blue-600 text-white' : ($message->sender_type === 'bot' ? 'border border-violet-100 bg-violet-50 text-slate-700' : 'border border-slate-100 bg-white text-slate-700')) }}">
                                    <div class="mb-1 flex min-w-0 flex-wrap items-center gap-2 text-[10px] font-black uppercase tracking-[0.12em] {{ $mine && ! $message->is_internal_note ? 'text-blue-100' : 'text-slate-400' }}">
                                        <span class="min-w-0 break-words">{{ $message->is_internal_note ? 'Private internal note' : ($message->sender_name ?: str($message->sender_type)->headline()) }}</span>
                                        @if($message->is_auto_reply)<span>Help Bot</span>@endif
                                    </div>
                                    <p class="whitespace-pre-wrap break-words text-[15px] leading-7">{{ $message->body }}</p>
                                    @foreach($message->attachments as $attachment)
                                        <a href="{{ route('support.attachments', $attachment) }}" class="mt-3 block rounded-xl bg-white/80 px-3 py-2 text-xs font-bold text-blue-700 break-words">Attachment: {{ $attachment->original_name }}</a>
                                    @endforeach
                                    <p class="mt-2 flex items-center justify-end gap-1 text-[10px] opacity-60">
                                        <span>{{ $message->created_at->format('H:i') }}</span>
                                        @if($mine && ! $message->is_internal_note)
                                            <span class="inline-flex items-center gap-0.5 {{ $message->read_at ? 'text-emerald-200' : '' }}" title="{{ $message->read_at ? 'Read' : 'Sent' }}">
                                                <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 8 3 3 7-7"/></svg>
                                                @if($message->read_at)
                                                    <svg class="-ml-1 h-3 w-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 8 3 3 7-7"/></svg>
                                                @endif
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                        <div data-agent-typing class="mb-3 hidden justify-start">
                            <div class="rounded-[1.25rem] border border-slate-100 bg-white px-4 py-3 text-sm font-bold text-slate-500 shadow-sm">
                                <span data-typing-copy>{{ $selected->assigned_to ? $chatPartnerName.' is typing' : 'Connecting agent' }}</span>
                                <span class="ml-1 inline-flex gap-1 align-middle">
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-blue-500"></span>
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-blue-500 [animation-delay:120ms]"></span>
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-blue-500 [animation-delay:240ms]"></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="support-composer shrink-0 border-t border-slate-100 bg-white px-4 py-3">
                        @unless($tenantChat)
                            <div class="mb-3 flex gap-2 overflow-x-auto pb-1">
                                @foreach($quickReplies->take(8) as $reply)
                                    <button type="button" data-quick-reply="{{ $reply->body }}" class="shrink-0 rounded-full border border-blue-100 bg-blue-50 px-4 py-2 text-xs font-black text-blue-700">{{ $reply->title }}</button>
                                @endforeach
                            </div>
                        @endunless
                        <form method="POST" action="{{ route('support.reply', $selected) }}" enctype="multipart/form-data" class="min-w-0">
                            @csrf
                            <div class="flex min-w-0 items-end gap-3">
                                <label class="grid h-12 w-12 shrink-0 cursor-pointer place-items-center rounded-full border border-slate-100 bg-white text-2xl font-light text-blue-600 shadow-sm">+<input type="file" name="attachment" class="sr-only"></label>
                                <div class="min-w-0 flex-1 rounded-[1.4rem] border border-slate-100 bg-white px-4 py-3 shadow-sm shadow-slate-200/70">
                                    <textarea name="body" rows="1" data-message-input class="max-h-32 min-w-0 w-full resize-none border-0 bg-transparent p-0 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:ring-0" placeholder="Type a message..." required></textarea>
                                </div>
                                <button class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-blue-600 text-white shadow-lg shadow-blue-200" aria-label="Send message">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg>
                                </button>
                            </div>
                            @if($manage)
                                <label class="mt-3 ml-16 inline-flex items-center gap-2 text-[11px] font-bold text-amber-700">
                                    <input type="checkbox" name="is_internal_note" value="1" class="rounded border-slate-300">
                                    Private internal note
                                </label>
                            @endif
                        </form>
                    </div>
                @else
                    <div class="grid flex-1 place-items-center p-8 text-center">
                        <div>
                            <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-blue-100 text-2xl font-black text-blue-700">SC</div>
                            <h2 class="mt-4 text-xl font-black text-[#071a3b]">Select a conversation</h2>
                            <p class="mt-2 text-sm text-slate-500">Choose a chat from the inbox or start a new support request.</p>
                        </div>
                    </div>
                @endif
            </main>

            <aside class="hidden min-h-0 overflow-y-auto border-l border-slate-100 bg-white p-6 lg:block">
                @if($selected)
                    <div class="text-center">
                        <div class="relative mx-auto grid h-24 w-24 place-items-center rounded-full bg-gradient-to-br from-blue-100 to-violet-100 text-2xl font-black text-blue-700">
                            {{ str($selectedName)->substr(0, 2)->upper() }}
                            <span class="absolute bottom-2 right-2 h-4 w-4 rounded-full border-2 border-white {{ $selectedOnline ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-[#071a3b]">{{ $selectedName }}</h3>
                        <p class="mt-1 text-sm font-bold {{ $selectedOnline ? 'text-emerald-600' : 'text-slate-400' }}">{{ $selectedOnline ? 'Active now' : 'Offline' }}</p>
                        <p class="mt-5 flex items-center justify-center gap-2 text-sm text-slate-500">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                            Conversation logged securely
                        </p>
                    </div>

                    <div class="mt-7 grid grid-cols-3 gap-3 text-center">
                        <button type="button" class="rounded-2xl bg-slate-50 p-3 text-xs font-bold text-slate-500"><span class="mx-auto mb-2 grid h-9 w-9 place-items-center rounded-full bg-white text-[#071a3b]">ID</span>Profile</button>
                        <button type="button" class="rounded-2xl bg-slate-50 p-3 text-xs font-bold text-slate-500"><span class="mx-auto mb-2 grid h-9 w-9 place-items-center rounded-full bg-white text-[#071a3b]">--</span>Mute</button>
                        <button type="button" class="rounded-2xl bg-slate-50 p-3 text-xs font-bold text-slate-500"><span class="mx-auto mb-2 grid h-9 w-9 place-items-center rounded-full bg-white text-[#071a3b]">?</span>Search</button>
                    </div>

                    <div class="mt-7 space-y-2">
                        <div class="flex items-center justify-between rounded-2xl px-2 py-3 text-sm font-bold text-[#071a3b]"><span>Chat info</span><span class="text-slate-300">&rsaquo;</span></div>
                        <div class="flex items-center justify-between rounded-2xl px-2 py-3 text-sm font-bold text-[#071a3b]"><span>Customize chat</span><span class="text-slate-300">&rsaquo;</span></div>
                        <div class="flex items-center justify-between rounded-2xl px-2 py-3 text-sm font-bold text-[#071a3b]"><span>Media & files</span><span class="text-slate-300">&rsaquo;</span></div>
                        <div class="flex items-center justify-between rounded-2xl px-2 py-3 text-sm font-bold text-[#071a3b]"><span>Privacy & support</span><span class="text-slate-300">&rsaquo;</span></div>
                    </div>

                    <div class="mt-7 rounded-3xl bg-slate-50 p-4">
                        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-blue-600">Ticket</p>
                        <h4 class="mt-2 font-black text-[#071a3b]">{{ $selected->ticket_no }}</h4>
                        <p class="mt-1 text-sm text-slate-500">{{ $selected->subject }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $statusTone[$selected->status] ?? 'bg-slate-100 text-slate-500' }}">{{ str($selected->status)->replace('_', ' ')->headline() }}</span>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black {{ $priorityTone[$selected->priority] ?? 'text-slate-500' }}">{{ str($selected->priority)->headline() }}</span>
                        </div>
                    </div>

                    @if($manage)
                        <details class="mt-4 rounded-3xl border border-slate-100 bg-white p-4" open>
                            <summary class="cursor-pointer text-sm font-black text-[#071a3b]">Ticket controls</summary>
                            <form method="POST" action="{{ route('support.update', $selected) }}" class="mt-4 space-y-3">
                                @csrf
                                @method('PATCH')
                                <input name="subject" value="{{ $selected->subject }}" class="erp-focus h-11 w-full rounded-2xl border border-slate-200 px-3 text-xs font-bold" placeholder="Ticket subject">
                                <select name="assigned_to" class="erp-focus h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs">
                                    <option value="">Unassigned</option>
                                    @foreach($staff as $person)
                                        <option value="{{ $person->id }}" @selected($selected->assigned_to === $person->id)>{{ $person->name }}</option>
                                    @endforeach
                                </select>
                                <div class="grid grid-cols-2 gap-2">
                                    <select name="priority" class="erp-focus h-11 rounded-2xl border border-slate-200 bg-white px-2 text-xs">
                                        @foreach(\App\Models\SupportTicket::PRIORITIES as $priority)
                                            <option value="{{ $priority }}" @selected($selected->priority === $priority)>{{ str($priority)->headline() }}</option>
                                        @endforeach
                                    </select>
                                    <select name="status" class="erp-focus h-11 rounded-2xl border border-slate-200 bg-white px-2 text-xs">
                                        @foreach(\App\Models\SupportTicket::STATUSES as $status)
                                            <option value="{{ $status }}" @selected($selected->status === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <select name="support_category_id" class="erp-focus h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs">
                                    <option value="">Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected($selected->support_category_id === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <select name="booking_id" class="erp-focus h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs">
                                    <option value="">Link booking</option>
                                    @foreach($bookings as $booking)
                                        <option value="{{ $booking->id }}" @selected($selected->booking_id === $booking->id)>{{ $booking->booking_no }}</option>
                                    @endforeach
                                </select>
                                <select name="unit_id" class="erp-focus h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 text-xs">
                                    <option value="">Link property</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" @selected($selected->unit_id === $unit->id)>{{ $unit->building?->name }} / {{ $unit->unit_no }}</option>
                                    @endforeach
                                </select>
                                <details class="rounded-2xl border border-slate-100 p-3">
                                    <summary class="cursor-pointer text-xs font-black text-slate-500">More linked records</summary>
                                    <div class="mt-3 space-y-2">
                                        <select name="tenant_id" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected($selected->tenant_id === $tenant->id)>{{ $tenant->full_name }}</option>@endforeach</select>
                                        <select name="owner_id" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link owner</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected($selected->owner_id === $owner->id)>{{ $owner->full_name }}</option>@endforeach</select>
                                        <select name="agent_id" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link agent</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($selected->agent_id === $agent->id)>{{ $agent->full_name }}</option>@endforeach</select>
                                        <select name="operations_team_member_id" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link maintainer</option>@foreach($maintainers as $maintainer)<option value="{{ $maintainer->id }}" @selected($selected->operations_team_member_id === $maintainer->id)>{{ $maintainer->full_name }}</option>@endforeach</select>
                                        <select name="payment_id" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link payment</option>@foreach($payments as $payment)<option value="{{ $payment->id }}" @selected($selected->payment_id === $payment->id)>{{ $payment->payment_no }} / AED {{ number_format((float) $payment->amount, 2) }}</option>@endforeach</select>
                                    </div>
                                </details>
                                <button class="w-full rounded-2xl bg-blue-600 px-4 py-3 text-xs font-black text-white">Save ticket</button>
                            </form>
                            <div class="mt-3 space-y-2">
                                @if($selected->mode === 'chat')
                                    <form method="POST" action="{{ route('support.convert', $selected) }}">
                                        @csrf
                                        <button class="w-full rounded-2xl border border-blue-100 px-4 py-3 text-xs font-black text-blue-700">Convert chat to ticket</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('support.destroy', $selected) }}" onsubmit="return confirm('Delete this support conversation?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="w-full rounded-2xl border border-rose-100 px-4 py-3 text-xs font-black text-rose-600">Delete conversation</button>
                                </form>
                            </div>
                        </details>
                    @endif

                    <div class="mt-4 rounded-3xl bg-slate-50 p-4 text-xs text-slate-500">
                        <p><b>Created:</b> {{ $selected->created_at->format('M d, Y H:i') }}</p>
                        <p class="mt-2"><b>First response:</b> {{ $selected->first_response_at ? $selected->created_at->diffInMinutes($selected->first_response_at).' min' : 'Waiting' }}</p>
                        @if($selected->booking)<p class="mt-2"><b>Booking:</b> {{ $selected->booking->booking_no }}</p>@endif
                        @if($selected->unit)<p class="mt-2"><b>Property:</b> {{ $selected->unit->building?->name }} / {{ $selected->unit->unit_no }}</p>@endif
                        @if($selected->payment)<p class="mt-2"><b>Payment:</b> {{ $selected->payment->payment_no }}</p>@endif
                    </div>
                @else
                    <div class="grid h-full place-items-center text-center">
                        <p class="text-sm font-bold text-slate-400">Customer profile appears here.</p>
                    </div>
                @endif
            </aside>
        </div>

        <div data-support-toast class="fixed left-3 right-3 top-24 z-[80] hidden rounded-2xl border border-blue-100 bg-white p-4 shadow-2xl shadow-slate-950/20 sm:left-auto sm:w-[390px]">
            <p data-support-toast-title class="text-sm font-black text-[#071a3b]">Support alert</p>
            <p data-support-toast-body class="mt-1 text-xs leading-5 text-slate-500">New support update received.</p>
        </div>
    </div>

    <script>
        document.body.classList.add('support-page-active');
        if (window.matchMedia('(max-width: 1023px)').matches) document.body.classList.add('support-mobile-active');
        @if($portalSelectedChat)
            document.body.classList.add('support-chat-fullscreen');
        @endif
        window.addEventListener('beforeunload', () => {
            document.body.classList.remove('support-page-active');
            document.body.classList.remove('support-mobile-active');
            document.body.classList.remove('support-chat-fullscreen');
            document.body.classList.remove('support-keyboard-open');
        });

        const supportMessageBox = document.querySelector('[data-support-messages]');
        if (supportMessageBox) supportMessageBox.scrollTop = supportMessageBox.scrollHeight;

        const updateKeyboardOffset = () => {
            if (!window.visualViewport || !document.body.classList.contains('support-chat-fullscreen')) return;
            const offset = Math.max(0, window.innerHeight - window.visualViewport.height - window.visualViewport.offsetTop);
            document.documentElement.style.setProperty('--keyboard-offset', `${offset}px`);
            document.body.classList.toggle('support-keyboard-open', offset > 80);
            if (supportMessageBox) requestAnimationFrame(() => { supportMessageBox.scrollTop = supportMessageBox.scrollHeight; });
        };
        window.visualViewport?.addEventListener('resize', updateKeyboardOffset);
        window.visualViewport?.addEventListener('scroll', updateKeyboardOffset);
        updateKeyboardOffset();

        document.querySelectorAll('[data-quick-reply]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.querySelector('[data-message-input]');
                if (!input) return;
                input.value = button.dataset.quickReply;
                input.focus();
                input.dispatchEvent(new Event('input'));
            });
        });

        document.querySelectorAll('[data-message-input]').forEach((input) => {
            let typingTimer;
            const sendTyping = () => {
                @if($selected)
                    if (document.hidden) return;
                    fetch('{{ route('support.typing', $selected) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
                @endif
            };

            input.addEventListener('input', () => {
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 128) + 'px';
                clearTimeout(typingTimer);
                sendTyping();
                typingTimer = setTimeout(sendTyping, 2500);
            });
            input.addEventListener('focus', sendTyping);
            input.addEventListener('focus', () => setTimeout(updateKeyboardOffset, 250));
            input.addEventListener('blur', () => setTimeout(updateKeyboardOffset, 250));
        });

        const vapidPublicKey = @js(config('services.webpush.public_key'));
        const csrfToken = '{{ csrf_token() }}';

        const showSupportToast = (title, body) => {
            const toast = document.querySelector('[data-support-toast]');
            if (!toast) return;
            toast.querySelector('[data-support-toast-title]').textContent = title;
            toast.querySelector('[data-support-toast-body]').textContent = body;
            toast.classList.remove('hidden');
            clearTimeout(window.supportToastTimer);
            window.supportToastTimer = setTimeout(() => toast.classList.add('hidden'), 4500);
        };

        const notifyUser = (title, body) => {
            showSupportToast(title, body);
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            try {
                new Notification(title, { body, icon: '/icons/pattern-192.png', badge: '/icons/pattern-192.png' });
            } catch (error) {}
        };

        const urlBase64ToUint8Array = (base64String) => {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
        };

        document.querySelector('[data-enable-support-alerts]')?.addEventListener('click', async (event) => {
            if (!('Notification' in window)) {
                event.currentTarget.textContent = 'Alerts not supported';
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                event.currentTarget.textContent = 'Alerts blocked';
                return;
            }

            event.currentTarget.textContent = 'Alerts enabled';
            showSupportToast('Support alerts enabled', 'You will see support popups on this screen.');

            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.ready;
                    await registration.showNotification('Support alerts enabled', {
                        body: 'Pattern RMS support notifications are ready.',
                        icon: '/icons/pattern-192.png',
                        badge: '/icons/pattern-192.png',
                        data: { url: '{{ route('support.index') }}' },
                    });
                } catch (error) {}
            }

            if ('serviceWorker' in navigator && 'PushManager' in window && vapidPublicKey) {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.getSubscription() || await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                });
                await fetch('{{ route('push-subscriptions.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(subscription.toJSON()),
                });
            }
        });

        @auth
            const pingPresence = () => {
                if (document.hidden) return;
                fetch('{{ route('support.presence.ping') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
            };
            pingPresence();
            setInterval(pingPresence, 90000);
        @endauth

        @if($selected)
            const updateTypingIndicator = async () => {
                if (document.hidden) return;
                const indicator = document.querySelector('[data-agent-typing]');
                if (!indicator) return;
                const response = await fetch('{{ route('support.typing', $selected) }}', { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const status = await response.json();
                if (status.is_typing) {
                    indicator.querySelector('[data-typing-copy]').textContent = (status.name || 'Someone') + ' is typing';
                    indicator.classList.remove('hidden');
                    indicator.classList.add('flex');
                } else {
                    indicator.classList.add('hidden');
                    indicator.classList.remove('flex');
                }
            };
            setInterval(updateTypingIndicator, 3000);

            setInterval(async () => {
                if (document.hidden) return;
                const box = document.querySelector('[data-support-messages]');
                const input = document.querySelector('[data-message-input]');
                if (!box || (input && input.value.trim())) return;
                const response = await fetch('{{ route('support.messages', $selected) }}', { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                const messages = await response.json();
                const oldCount = Number(box.dataset.messageCount || 0);
                if (messages.length > oldCount) {
                    const latest = messages[messages.length - 1];
                    notifyUser('New support message', latest.body || '{{ $selected->ticket_no }}');
                    setTimeout(() => window.location.reload(), 1200);
                }
            }, 15000);
        @endif
    </script>
</x-app-layout>
