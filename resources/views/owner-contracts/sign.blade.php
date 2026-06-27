<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#061a38">
    <title>{{ $contract->contract_no }} - Owner Contract Signature</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icons/pattern-192.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#eef3f9] font-sans antialiased text-[#071a3b]">
    @php
        $companyAddress = $contract->company_address ?: 'Office 413, Al Attar Business Centre, Al Barsha, Dubai, UAE';
        $propertyName = $contract->property_name ?: $contract->unit->building?->name;
        $propertyNo = $contract->property_no ?: $contract->unit->unit_no;
        $propertyTitle = trim($propertyName.' / Unit '.$propertyNo);
        $terms = $contract->special_terms ?: 'Initial term 12 months. Owner personal use up to 30 calendar days per year during off-season only. Management fee deducted from booking revenue.';
        $preparedDocumentUrl = $contract->contract_document_path ? route('owner-contracts.sign.document', [$contract, $token]) : null;
    @endphp

    <div class="min-h-screen">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-56 items-center rounded-2xl bg-white px-4 shadow-sm ring-1 ring-slate-200">
                        <img src="{{ asset('brand/pattern-logo.jpeg') }}" alt="Pattern Vacation Homes Rental" class="max-h-10 w-full object-contain">
                    </span>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-blue-600">Owner contract review</p>
                        <h1 class="mt-1 text-xl font-black tracking-[-0.03em] text-[#071a3b]">{{ $contract->contract_no }}</h1>
                    </div>
                </div>
                <span class="w-fit rounded-full {{ $contract->owner_signed_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-4 py-2 text-xs font-black">
                    {{ $contract->owner_signed_at ? 'Signed' : 'Awaiting owner signature' }}
                </span>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                    <section class="border-b border-slate-200 bg-[#061a38] p-8 text-white">
                        <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.24em] text-blue-200">Management agreement</p>
                                <h2 class="mt-3 text-3xl font-black tracking-[-0.04em]">Owner Unit Contract</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100/75">Please review the full agreement details below. Your digital signature at the end confirms that you accept the recorded parties, property, financial terms, bank details, and special terms.</p>
                            </div>
                            <div class="rounded-2xl bg-white p-3 shadow-lg">
                                <img src="{{ asset('brand/pattern-logo.jpeg') }}" alt="Pattern Vacation Homes Rental" class="h-12 w-56 object-contain">
                            </div>
                        </div>
                    </section>

                    <section class="p-8">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Contract no</p><p class="mt-2 font-black">{{ $contract->contract_no }}</p></div>
                            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Start date</p><p class="mt-2 font-black">{{ $contract->contract_start_date?->format('M d, Y') ?? '-' }}</p></div>
                            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">End date</p><p class="mt-2 font-black">{{ $contract->contract_end_date?->format('M d, Y') ?? '-' }}</p></div>
                        </div>

                        @if($preparedDocumentUrl)
                            <section class="mt-8 overflow-hidden rounded-[2rem] border border-blue-100 bg-blue-50">
                                <div class="flex flex-col gap-3 border-b border-blue-100 bg-white p-5 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <h3 class="text-lg font-black">Uploaded contract PDF</h3>
                                        <p class="mt-1 text-sm text-slate-500">Review this prepared contract document before signing below.</p>
                                    </div>
                                    <a href="{{ $preparedDocumentUrl }}" target="_blank" class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700">Open PDF in new tab</a>
                                </div>
                                <iframe src="{{ $preparedDocumentUrl }}#toolbar=1&navpanes=0" class="h-[78vh] w-full bg-white" title="Owner contract PDF"></iframe>
                            </section>
                        @endif

                        <div class="mt-8 grid gap-6 md:grid-cols-2">
                            <section class="rounded-3xl border border-slate-200 p-5">
                                <h3 class="text-lg font-black">Company details</h3>
                                <dl class="mt-5 space-y-4 text-sm">
                                    <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Company</dt><dd class="mt-1 font-bold">{{ $contract->company_name }}</dd></div>
                                    <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Registration / TRN</dt><dd class="mt-1">{{ $contract->company_registration_no ?: 'Not specified' }}</dd></div>
                                    <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Contact</dt><dd class="mt-1">{{ $contract->company_contact_no ?: 'Not specified' }} / {{ $contract->company_email ?: 'Not specified' }}</dd></div>
                                    <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Address</dt><dd class="mt-1 leading-6">{{ $companyAddress }}</dd></div>
                                </dl>
                            </section>

                            <section class="rounded-3xl border border-slate-200 p-5">
                                <h3 class="text-lg font-black">Owner details</h3>
                                <dl class="mt-5 space-y-4 text-sm">
                                    <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Owner</dt><dd class="mt-1 font-bold">{{ $contract->owner_name }}</dd></div>
                                    <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Nationality</dt><dd class="mt-1">{{ $contract->owner_nationality ?: 'Not specified' }}</dd></div>
                                    <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Passport / Emirates ID</dt><dd class="mt-1">{{ $contract->owner_passport_no ?: 'Not specified' }}</dd></div>
                                    <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Contact</dt><dd class="mt-1">{{ $contract->owner_contact_no ?: 'Not specified' }} / {{ $contract->owner_email ?: 'Not specified' }}</dd></div>
                                </dl>
                            </section>
                        </div>

                        <section class="mt-6 rounded-3xl border border-slate-200 p-5">
                            <h3 class="text-lg font-black">Property details</h3>
                            <dl class="mt-5 grid gap-4 text-sm md:grid-cols-3">
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Property</dt><dd class="mt-1 font-bold">{{ $propertyTitle }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Community</dt><dd class="mt-1">{{ $contract->community ?: $contract->unit->building?->area ?: 'Not specified' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Property type</dt><dd class="mt-1">{{ $contract->property_type ?: $contract->unit->unit_type }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Floor</dt><dd class="mt-1">{{ $contract->floor_no ?: $contract->unit->floor ?: 'Not specified' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">DEWA account</dt><dd class="mt-1">{{ $contract->dewa_account_no ?: 'Not specified' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Effective / permit date</dt><dd class="mt-1">{{ $contract->effective_date?->format('M d, Y') ?? 'Not specified' }}</dd></div>
                            </dl>
                        </section>

                        <section class="mt-6 rounded-3xl border border-slate-200 p-5">
                            <h3 class="text-lg font-black">Financial terms</h3>
                            <dl class="mt-5 grid gap-4 text-sm md:grid-cols-4">
                                <div class="rounded-2xl bg-blue-50 p-4"><dt class="font-bold uppercase tracking-[0.14em] text-blue-500 text-[11px]">Management fee</dt><dd class="mt-2 text-xl font-black">{{ $contract->management_fee_percent }}%</dd></div>
                                <div class="rounded-2xl bg-slate-50 p-4"><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Startup fee</dt><dd class="mt-2 font-black">AED {{ number_format((float) $contract->startup_fee, 2) }}</dd></div>
                                <div class="rounded-2xl bg-slate-50 p-4"><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Furniture fee</dt><dd class="mt-2 font-black">AED {{ number_format((float) $contract->furniture_fee, 2) }}</dd></div>
                                <div class="rounded-2xl bg-slate-900 p-4 text-white"><dt class="font-bold uppercase tracking-[0.14em] text-blue-200 text-[11px]">Grand total</dt><dd class="mt-2 font-black">AED {{ number_format((float) $contract->grand_total, 2) }}</dd></div>
                            </dl>
                        </section>

                        <section class="mt-6 rounded-3xl border border-slate-200 p-5">
                            <h3 class="text-lg font-black">Owner payout bank details</h3>
                            <dl class="mt-5 grid gap-4 text-sm md:grid-cols-2">
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Account holder</dt><dd class="mt-1">{{ $contract->bank_account_holder ?: 'Not specified' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Currency</dt><dd class="mt-1">{{ $contract->bank_currency ?: 'AED' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Bank</dt><dd class="mt-1">{{ $contract->bank_name ?: 'Not specified' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">Account no</dt><dd class="mt-1">{{ $contract->bank_account_no ?: 'Not specified' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">IBAN</dt><dd class="mt-1 break-all">{{ $contract->iban ?: 'Not specified' }}</dd></div>
                                <div><dt class="font-bold uppercase tracking-[0.14em] text-slate-400 text-[11px]">SWIFT</dt><dd class="mt-1">{{ $contract->swift_code ?: 'Not specified' }}</dd></div>
                            </dl>
                        </section>

                        <section class="mt-6 rounded-3xl border border-slate-200 p-5">
                            <h3 class="text-lg font-black">Commercial and operating terms</h3>
                            <div class="mt-5 grid gap-4 text-sm leading-6 md:grid-cols-2">
                                <p class="rounded-2xl bg-slate-50 p-4">Pattern Vacation Homes Rental will manage the unit for holiday home operations, guest coordination, booking administration, and operational follow-up as recorded in the ERP.</p>
                                <p class="rounded-2xl bg-slate-50 p-4">Management fees and owner-linked expenses are deducted from eligible booking revenue and reflected in owner statements and payout schedules.</p>
                                <p class="rounded-2xl bg-slate-50 p-4">Owner bank details are used for future payout processing. Approved rent collections become payable according to the payout rules configured in Pattern RMS.</p>
                                <p class="rounded-2xl bg-slate-50 p-4">The owner confirms the property and identity details are accurate and authorizes Pattern to maintain contract, document, and operational records in the ERP.</p>
                            </div>
                        </section>

                        <section class="mt-6 rounded-3xl border border-blue-100 bg-blue-50 p-5">
                            <h3 class="text-lg font-black">Special terms</h3>
                            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $terms }}</p>
                        </section>

                        <a href="{{ route('owner-contracts.sign.pdf', [$contract, $token]) }}" target="_blank" class="mt-6 inline-flex rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-bold text-blue-700">Open printable PDF</a>

                        <section id="signature" class="mt-8 rounded-[2rem] border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-xl font-black">Signature section</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">By signing below, the owner confirms they reviewed the full contract details on this page and accepts the agreement electronically.</p>

                            @if (! $contract->owner_signed_at)
                                <form method="POST" action="{{ route('owner-contracts.sign.store', [$contract, $token]) }}" class="mt-5 rounded-3xl border border-blue-100 bg-white p-5">
                                    @csrf
                                    <label class="text-sm font-bold text-[#071a3b]" for="signed_by">Signer full name</label>
                                    <input id="signed_by" name="signed_by" value="{{ old('signed_by', $contract->owner_name) }}" class="erp-focus mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" placeholder="Your full name" required>
                                    <div class="mt-5">
                                        <x-signature-pad input-name="signature_data" input-id="owner_contract_signature" label="Owner signature" />
                                    </div>
                                    <label class="mt-5 flex items-start gap-2 text-sm text-slate-600"><input type="checkbox" name="accepted_terms" value="1" class="mt-1 rounded border-slate-300" required> I confirm that I have reviewed all contract sections above and accept this owner management contract.</label>
                                    <button class="mt-5 w-full rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20">Sign owner contract</button>
                                </form>
                            @else
                                <div class="mt-5 rounded-3xl border border-emerald-100 bg-emerald-50 p-5 text-sm text-emerald-700">
                                    <p class="font-bold">Signed by {{ $contract->owner_signature_name }} on {{ $contract->owner_signed_at->format('M d, Y H:i') }}.</p>
                                    @if($contract->owner_signature_data)
                                        <div class="mt-4 rounded-2xl border border-emerald-100 bg-white p-3">
                                            <img src="{{ $contract->owner_signature_data }}" alt="Owner signature" class="h-28 max-w-full object-contain">
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    </section>
                </article>

                <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">Review status</p>
                        <h2 class="mt-2 text-xl font-black">{{ $contract->owner_signed_at ? 'Contract signed' : 'Signature required' }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $contract->owner_signed_at ? 'This contract has been signed electronically.' : 'Read the agreement, then sign at the bottom of the document.' }}</p>
                        <a href="#signature" class="mt-4 inline-flex w-full justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white">Go to signature</a>
                    </div>
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400">Contract summary</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Owner</dt><dd class="text-right font-bold">{{ $contract->owner_name }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Property</dt><dd class="text-right font-bold">{{ $propertyTitle }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Management fee</dt><dd class="text-right font-bold">{{ $contract->management_fee_percent }}%</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd class="text-right font-bold">{{ str($contract->status)->headline() }}</dd></div>
                        </dl>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <script>
        if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('service-worker.js') }}'));
    </script>
</body>
</html>
