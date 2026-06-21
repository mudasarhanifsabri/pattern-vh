<div style="font-family:Arial,sans-serif;background:#eef3f9;padding:24px;color:#071a3b">
    <div style="max-width:640px;margin:auto;background:#ffffff;border-radius:24px;padding:28px;border:1px solid #dbe4f0">
        <img src="{{ asset('brand/pattern-logo.jpeg') }}" alt="Pattern Vacation Homes Rental" style="height:54px;object-fit:contain;margin-bottom:20px">
        <p style="font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#2563eb;margin:0 0 8px">Owner contract signature</p>
        <h1 style="font-size:26px;margin:0 0 12px">Your contract is ready to review</h1>
        <p style="font-size:15px;line-height:1.7;color:#52627a;margin:0 0 18px">
            Dear {{ $contract->owner_name }}, please open the secure link below to review your Pattern owner contract and draw your signature online.
        </p>
        <div style="background:#f8faff;border:1px solid #dbe4f0;border-radius:18px;padding:16px;margin:18px 0">
            <p style="margin:0 0 6px;font-size:13px;color:#64748b">Contract</p>
            <strong>{{ $contract->contract_no }}</strong>
            <p style="margin:8px 0 0;font-size:13px;color:#64748b">{{ $contract->unit?->building?->name }} / Unit {{ $contract->unit?->unit_no }}</p>
        </div>
        <a href="{{ $signatureLink }}" style="display:inline-block;background:#2563eb;color:white;text-decoration:none;font-weight:800;border-radius:14px;padding:14px 22px">Review and sign contract</a>
        <p style="font-size:12px;line-height:1.6;color:#64748b;margin-top:22px">
            If the button does not open, copy this link:<br>
            <span style="word-break:break-all">{{ $signatureLink }}</span>
        </p>
    </div>
</div>
