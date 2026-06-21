<div style="font-family:Arial,sans-serif;background:#eef3f9;padding:24px;color:#071a3b">
    <div style="max-width:640px;margin:auto;background:#fff;border:1px solid #dbe4f0;border-radius:24px;padding:28px">
        <img src="{{ asset('brand/pattern-logo.jpeg') }}" alt="Pattern" style="height:50px;max-width:240px;object-fit:contain">
        <p style="margin:20px 0 6px;color:#2563eb;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase">Support Center</p>
        <h1 style="margin:0;font-size:24px">{{ $created ? 'We received your request' : 'You have a new support reply' }}</h1>
        <p style="color:#64748b;line-height:1.7">Ticket {{ $ticket->ticket_no }}: {{ $ticket->subject }}</p>
        @if($supportMessage)<div style="margin:18px 0;padding:16px;background:#f8faff;border-radius:16px;line-height:1.7">{{ $supportMessage->body }}</div>@endif
        <a href="{{ route('support.public.thread', [$ticket, $ticket->public_token]) }}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;font-weight:800;border-radius:14px;padding:13px 20px">Open conversation</a>
    </div>
</div>
