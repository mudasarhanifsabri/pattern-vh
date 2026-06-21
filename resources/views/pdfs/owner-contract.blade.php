@php
    $companyAddress = $contract->company_address ?: 'Office 413, Al Attar Business Centre, Al Barsha, Dubai, United Arab Emirates';
    $propertyName = $contract->property_name ?: $contract->unit->building?->name;
    $propertyNo = $contract->property_no ?: $contract->unit->unit_no;
    $currency = $contract->bank_currency ?: 'UAE Dirhams (AED)';
    $money = fn ($value) => $value !== null && $value !== '' ? number_format((float) $value, 2).' AED' : '';
    $tableValue = fn ($value) => filled($value) ? $value : ' ';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px 54px 58px 54px; footer: html_patternFooter; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; color: #000; font-size: 11px; line-height: 1.34; }
        .page { page-break-after: always; position: relative; }
        .page:last-child { page-break-after: auto; }
        .header { position: relative; height: 58px; margin-bottom: 8px; }
        .mosaic { position: absolute; left: -54px; top: -20px; width: 112px; height: 112px; opacity: .52; }
        .mosaic span { float: left; width: 13px; height: 13px; margin: 1px; border-radius: 2px; background: #b79062; opacity: .85; }
        .mosaic span:nth-child(3n) { background: #d8c3a7; opacity: .65; }
        .mosaic span:nth-child(4n) { background: #ebe4d8; opacity: .5; }
        .logo { text-align: center; padding-top: 2px; }
        .logo img { width: 205px; height: auto; }
        .ref { position: absolute; right: 0; top: 20px; font-size: 11px; }
        .title { text-align: center; margin: 12px 0 18px; }
        .title .ar { font-size: 13px; margin-bottom: 2px; direction: rtl; }
        .title .en { font-size: 18px; }
        .intro { display: table; width: 100%; margin-bottom: 10px; }
        .intro div { display: table-cell; width: 50%; vertical-align: top; font-size: 11px; }
        .intro .ar { direction: rtl; text-align: right; }
        .box { border: 1px solid #111; margin-bottom: 4px; }
        .section-row { display: table; width: 100%; background: #e7e7e7; border-bottom: 1px solid #777; font-size: 13px; }
        .section-row div { display: table-cell; padding: 5px 8px; }
        .section-row .ar { text-align: right; direction: rtl; }
        .sub-row { display: table; width: 100%; background: #efefef; border-bottom: 1px solid #777; }
        .sub-row div { display: table-cell; padding: 5px 28px; font-size: 12px; }
        .sub-row .ar { text-align: right; direction: rtl; }
        table.form-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .form-table td { padding: 5px 6px; vertical-align: middle; }
        .label-en { width: 28%; font-size: 12px; }
        .value { width: 44%; text-align: center; font-size: 13px; }
        .label-ar { width: 28%; text-align: right; direction: rtl; font-size: 11px; }
        .spacer-row td { padding: 12px 6px; }
        .clause-box { border: 1px solid #111; margin-bottom: 6px; }
        .clause-title { display: table; width: 100%; background: #e7e7e7; border-bottom: 1px solid #777; }
        .clause-title div { display: table-cell; padding: 7px 8px; font-size: 13px; }
        .clause-title .ar { text-align: right; direction: rtl; }
        .clause { display: table; width: 100%; border-bottom: 0; page-break-inside: avoid; }
        .clause .en, .clause .ar { display: table-cell; width: 50%; padding: 8px 12px; vertical-align: top; }
        .clause .en { font-size: 11.3px; text-align: left; }
        .clause .ar { font-size: 10.4px; text-align: right; direction: rtl; }
        .appendix-title { font-weight: bold; font-size: 12px; margin: 12px 0 8px 8px; }
        .appendix-title .muted { color: #777; font-style: italic; font-weight: normal; }
        .appendix-title .ar { float: right; direction: rtl; font-weight: normal; }
        table.appendix { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 4px; }
        .appendix th { background: #e7e7e7; border: 1px solid #111; padding: 6px 8px; font-size: 12px; font-weight: normal; text-align: left; }
        .appendix th.ar { text-align: right; direction: rtl; }
        .appendix td { border-left: 1px solid #111; border-right: 1px solid #111; border-bottom: 1px dotted #777; padding: 5px 6px; vertical-align: middle; }
        .appendix .caption td { background: #e7e7e7; border: 1px solid #111; text-align: center; font-size: 12px; padding: 4px; }
        .center { text-align: center; }
        .right-ar { direction: rtl; text-align: right; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .signature-table td { width: 50%; vertical-align: top; padding: 8px 12px; }
        .sig-line { height: 42px; border-bottom: 1px solid #111; margin: 12px 0 6px; }
        .signature-img { max-height: 42px; max-width: 200px; }
        .tag-list { column-count: 2; font-size: 9px; line-height: 1.5; }
        .tag-list code { font-family: "DejaVu Sans Mono", monospace; font-weight: bold; }
        .pdf-footer { border-top: 1px solid #bbb; padding-top: 6px; font-size: 9px; color: #000; }
        .pdf-footer img { width: 80px; opacity: .18; float: left; }
        .pdf-footer .footer-main { text-align: center; line-height: 1.55; }
        .pdf-footer .footer-page { float: right; font-size: 13px; font-weight: bold; margin-top: -18px; }
    </style>
</head>
<body>
<htmlpagefooter name="patternFooter">
    <div class="pdf-footer">
        @if($logo)<img src="{{ $logo }}" alt="Pattern">@endif
        <div class="footer-main">
            {{ $companyAddress }}<br>
            pattern.ae &nbsp;&nbsp;&nbsp;&nbsp; {{ $contract->company_email ?: 'customerservice@pattern.ae' }} &nbsp;&nbsp;&nbsp;&nbsp; {{ $contract->company_contact_no ?: '+971 (4) 329 9693' }}
        </div>
        <div class="footer-page">{PAGENO}</div>
    </div>
</htmlpagefooter>

<section class="page">
    <div class="header">
        <div class="mosaic">@for($i = 0; $i < 36; $i++)<span></span>@endfor</div>
        <div class="logo">@if($logo)<img src="{{ $logo }}" alt="Pattern">@endif</div>
        <div class="ref">Ref No. {{ $contract->contract_no }}</div>
    </div>
    <div class="title"><div class="ar">عقد إدارة عقار</div><div class="en">Property Management Contract</div></div>
    <div class="intro"><div>This contract constitutes an agreement to operate a property<br>as holiday homes between:</div><div class="ar">هذا العقد يشكل اتفاقية لتشغيل عقار كبيوت للعطلات ما بين:</div></div>

    <div class="box">
        <div class="section-row"><div>1) &nbsp; The Parties</div><div class="ar">الأطراف &nbsp; (1</div></div>
        <div class="sub-row"><div>1.1. First Party</div><div class="ar">1.1. الطرف الأول</div></div>
        <table class="form-table">
            <tr><td class="label-en">Company Name</td><td class="value">{{ $contract->company_name }}</td><td class="label-ar">الشركة</td></tr>
            <tr><td class="label-en">Registration No</td><td class="value">{{ $contract->company_registration_no }}</td><td class="label-ar">رقم الرخصة</td></tr>
            <tr><td class="label-en">Contact No</td><td class="value">{{ $contract->company_contact_no }}</td><td class="label-ar">رقم الاتصال</td></tr>
            <tr><td class="label-en">Email Address</td><td class="value">{{ $contract->company_email }}</td><td class="label-ar">البريد الإلكتروني</td></tr>
            <tr><td class="label-en">Address</td><td class="value">{{ $companyAddress }}</td><td class="label-ar">العنوان</td></tr>
            <tr class="spacer-row"><td colspan="3"></td></tr>
        </table>
        <div class="sub-row"><div>1.2. &nbsp; Second Party</div><div class="ar">1.2. الطرف الثاني</div></div>
        <table class="form-table">
            <tr><td class="label-en">Full Name</td><td class="value">{{ $contract->owner_name }}</td><td class="label-ar">اسم المالك</td></tr>
            <tr><td class="label-en">Nationality</td><td class="value">{{ $contract->owner_nationality }}</td><td class="label-ar">الجنسية</td></tr>
            <tr><td class="label-en">Passport no</td><td class="value">{{ $contract->owner_passport_no }}</td><td class="label-ar">رقم جواز السفر</td></tr>
            <tr><td class="label-en">Contact no</td><td class="value">{{ $contract->owner_contact_no }}</td><td class="label-ar">رقم الاتصال</td></tr>
            <tr><td class="label-en">Email Address</td><td class="value">{{ $contract->owner_email }}</td><td class="label-ar">البريد الإلكتروني</td></tr>
        </table>
    </div>
    <div class="box">
        <div class="section-row"><div>2) &nbsp; Property Detail</div><div class="ar">تفاصيل العقار &nbsp; (2</div></div>
        <table class="form-table">
            <tr><td class="label-en">Property Name</td><td class="value">{{ $propertyName }}</td><td class="label-ar">اسم العقار</td></tr>
            <tr><td class="label-en">Floor No.</td><td class="value">{{ $contract->floor_no }}</td><td class="label-ar">رقم الطابق</td></tr>
            <tr><td class="label-en">Community</td><td class="value">{{ $contract->community }}</td><td class="label-ar">المنطقة</td></tr>
            <tr><td class="label-en">Property No.</td><td class="value">{{ $propertyNo }}</td><td class="label-ar">رقم الوحدة</td></tr>
            <tr><td class="label-en">Property Type</td><td class="value">{{ $contract->property_type }}</td><td class="label-ar">نوع الوحدة</td></tr>
            <tr><td class="label-en">DEWA Account no</td><td class="value">{{ $contract->dewa_account_no }}</td><td class="label-ar">رقم حساب كهرباء ومياه دبي</td></tr>
        </table>
    </div>
</section>

@foreach(array_chunk($clauses, 2) as $chunk)
    <section class="page">
        <div class="header">
            <div class="logo">@if($logo)<img src="{{ $logo }}" alt="Pattern">@endif</div>
            <div class="ref">Ref No. {{ $contract->contract_no }}</div>
        </div>
        @foreach($chunk as $section)
            <div class="clause-box">
                <div class="clause-title"><div>{{ $section['number'] }}) &nbsp; {{ $section['title_en'] }}</div><div class="ar">{{ $section['title_ar'] }} &nbsp; ({{ $section['number'] }}</div></div>
                @foreach($section['items'] as $item)
                    <div class="clause">
                        <div class="en">{{ $item[0] }}. &nbsp; {{ $item[1] }}</div>
                        <div class="ar">{{ $item[2] }} &nbsp; .{{ $item[0] }}</div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </section>
@endforeach

<section class="page">
    <div class="header">
        <div class="logo">@if($logo)<img src="{{ $logo }}" alt="Pattern">@endif</div>
        <div class="ref">Ref No. {{ $contract->contract_no }}</div>
    </div>
    <div class="clause-box">
        <div class="clause-title"><div>10) &nbsp; Parties Acknowledgments</div><div class="ar">إقرار الأطراف &nbsp; (10</div></div>
        <table class="signature-table">
            <tr>
                <td>
                    <strong>The COMPANY - First Party</strong><br>
                    {{ $contract->company_name }}<br><br>
                    Sultan Alhemeiri<br>Chief Executive Officer<br><br>
                    Authorised Signatory<br>
                    <div class="sig-line">@if($contract->company_signature_data)<img class="signature-img" src="{{ $contract->company_signature_data }}">@endif</div>
                    Date: {{ $contract->company_signed_at?->format('d/m/Y') }}
                </td>
                <td class="right-ar">
                    <strong>الشركة - الطرف الأول</strong><br>
                    {{ $contract->company_name }}<br><br>
                    سلطان الحميري<br>الرئيس التنفيذي<br><br>
                    المخول بالتوقيع<br>
                    <div class="sig-line"></div>
                    التاريخ: {{ $contract->company_signed_at?->format('d/m/Y') }}
                </td>
            </tr>
            <tr>
                <td>
                    <strong>The Owner - Second Party</strong><br>
                    Name: {{ $contract->owner_name }}<br><br>
                    Signature<br>
                    <div class="sig-line">@if($contract->owner_signature_data)<img class="signature-img" src="{{ $contract->owner_signature_data }}">@endif</div>
                    Date: {{ $contract->owner_signed_at?->format('d/m/Y') }}
                </td>
                <td class="right-ar">
                    <strong>المالك - الطرف الثاني</strong><br>
                    الاسم: {{ $contract->owner_name }}<br><br>
                    التوقيع<br>
                    <div class="sig-line"></div>
                    التاريخ: {{ $contract->owner_signed_at?->format('d/m/Y') }}
                </td>
            </tr>
        </table>
    </div>
</section>

<section class="page">
    <div class="header">
        <div class="logo">@if($logo)<img src="{{ $logo }}" alt="Pattern">@endif</div>
        <div class="ref">Ref No. {{ $contract->contract_no }}</div>
    </div>
    <div class="section-row" style="border:1px solid #111;"><div>11) &nbsp; Appendices</div><div class="ar">الملاحق (11)</div></div>
    <table class="appendix">
        <tr><th>11.1. &nbsp; Bank Account Details of the Company</th><th class="ar">11.1. تفاصيل الحساب البنكي للشركة</th></tr>
        <tr><td>Company Name</td><td class="center">{{ $contract->company_name }}</td><td class="right-ar">اسم الشركة</td></tr>
        <tr><td>Currency</td><td class="center">UAE Dirhams (AED)</td><td class="right-ar">عملة</td></tr>
        <tr><td>Name of Bank</td><td class="center">Abu Dhabi Commercial Bank - ADCB</td><td class="right-ar">اسم البنك</td></tr>
        <tr><td>Account No</td><td class="center">12691898820001</td><td class="right-ar">رقم الحساب البنكي</td></tr>
        <tr><td>IBAN</td><td class="center">AE690030012691898820001</td><td class="right-ar">رقم الحساب المصرفي الدولي</td></tr>
        <tr><td>SWIFT Code</td><td class="center">ADCBAEAA</td><td class="right-ar">رقم التحويل المالي</td></tr>
        <tr class="caption"><td colspan="2">Table 1.1</td><td>جدول 1.1</td></tr>
    </table>
    <table class="appendix">
        <tr><th>11.2. &nbsp; Bank Account Details of the client</th><th class="ar">11.2. تفاصيل الحساب البنكي للعميل</th></tr>
        <tr><td>Account Holder Name</td><td class="center">{{ $contract->bank_account_holder }}</td><td class="right-ar">اسم صاحب الحساب</td></tr>
        <tr><td>Currency</td><td class="center">{{ $currency }}</td><td class="right-ar">عملة</td></tr>
        <tr><td>Name of Bank</td><td class="center">{{ $contract->bank_name }}</td><td class="right-ar">اسم البنك</td></tr>
        <tr><td>Account No</td><td class="center">{{ $contract->bank_account_no }}</td><td class="right-ar">رقم الحساب البنكي</td></tr>
        <tr><td>IBAN</td><td class="center">{{ $contract->iban }}</td><td class="right-ar">رقم الحساب المصرفي الدولي</td></tr>
        <tr><td>SWIFT Code</td><td class="center">{{ $contract->swift_code }}</td><td class="right-ar">رقم التحويل المالي</td></tr>
        <tr class="caption"><td colspan="2">Table 1.2</td><td>جدول 1.2</td></tr>
    </table>
    <div class="appendix-title">Property Management Services <span class="muted">(Ongoing fee)</span><span class="ar">خدمات إدارة الممتلكات (رسوم دورية)</span></div>
    <table class="appendix">
        <tr><td>Management Fee</td><td class="center">{{ $contract->management_fee_percent }}%</td><td class="right-ar">رسوم إدارية</td></tr>
        @foreach($managementServices as $service)
            <tr><td class="center">{{ $service[0] }}</td><td>{{ $service[1] }}</td><td class="right-ar">{{ $service[2] }} &nbsp; {{ $service[0] }}</td></tr>
        @endforeach
        <tr class="caption"><td colspan="2">Table 2.1</td><td>جدول 2.1</td></tr>
    </table>
</section>

<section class="page">
    <div class="header">
        <div class="logo">@if($logo)<img src="{{ $logo }}" alt="Pattern">@endif</div>
        <div class="ref">Ref No. {{ $contract->contract_no }}</div>
    </div>
    <div class="appendix-title">Initial Setup and Onboarding <span class="muted">(startup fees)</span><span class="ar">الإعدادات الأولية والتجهيز</span></div>
    <table class="appendix">
        @foreach($startupServices as $service)
            <tr><td class="center">{{ $service[0] }}</td><td>{{ $service[1] }}</td><td class="right-ar">{{ $service[2] }} &nbsp; {{ $service[0] }}</td></tr>
        @endforeach
        <tr class="caption"><td colspan="2">Table 2.2</td><td>جدول 2.2</td></tr>
    </table>
    <div class="appendix-title">Furniture Supply & Installation<span class="ar">توريد وتركيب الأثاث</span></div>
    <table class="appendix">
        <tr><td>Fully equipping an apartment and installing furniture according to DTCM standards</td><td class="right-ar">تجهيز الشقة بالكامل وتوريد وتركيب الأثاث حسب المعايير المطلوبة</td></tr>
        <tr class="caption"><td>Table 2.3</td><td>جدول 2.3</td></tr>
    </table>
    <div class="appendix-title">Financial Liabilities<span class="ar">الالتزامات المالية</span></div>
    <table class="appendix">
        <tr><td>Furniture Supply & Installation</td><td class="center">{{ $money($contract->furniture_fee) }}</td><td class="right-ar">مجموع خدمة توريد وتركيب الأثاث</td></tr>
        <tr><td>Startup Fee</td><td class="center">{{ $money($contract->startup_fee) }}</td><td class="right-ar">إجمالي رسوم بدء التشغيل</td></tr>
        <tr><td>VAT (5%)</td><td class="center">{{ $money($contract->vat_amount) }}</td><td class="right-ar">ضريبة القيمة المضافة 5%</td></tr>
        <tr><td>Grand Total</td><td class="center">{{ $money($contract->grand_total) }}</td><td class="right-ar">المجموع الكلي</td></tr>
        <tr><td>Management Fee</td><td class="center">{{ $contract->management_fee_percent }}%</td><td class="right-ar">رسوم إدارية</td></tr>
        <tr class="caption"><td colspan="2">Table 3.1</td><td>جدول 3.1</td></tr>
    </table>
    <div style="margin-top:14px; border:1px solid #bbb; padding:10px;">
        <strong>Template Tags</strong>
        <div class="tag-list">
            @foreach($tags as $tag => $description)
                <div><code>{{ $tag }}</code> - {{ $description }}</div>
            @endforeach
        </div>
    </div>
</section>
</body>
</html>
