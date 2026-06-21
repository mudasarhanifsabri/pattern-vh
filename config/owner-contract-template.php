<?php

return [
    'tags' => [
        '{{contract_no}}' => 'Contract reference number',
        '{{company_name}}' => 'Pattern legal company name',
        '{{company_registration_no}}' => 'Company registration number',
        '{{company_contact_no}}' => 'Company phone number',
        '{{company_email}}' => 'Company email',
        '{{company_address}}' => 'Company address',
        '{{owner_name}}' => 'Owner full name',
        '{{owner_nationality}}' => 'Owner nationality',
        '{{owner_passport_no}}' => 'Owner passport or Emirates ID number',
        '{{owner_contact_no}}' => 'Owner mobile number',
        '{{owner_email}}' => 'Owner email address',
        '{{property_name}}' => 'Building or property name',
        '{{floor_no}}' => 'Floor number',
        '{{community}}' => 'Community or area',
        '{{property_no}}' => 'Unit number',
        '{{property_type}}' => 'Unit type',
        '{{dewa_account_no}}' => 'DEWA account number',
        '{{management_fee_percent}}' => 'Management fee percentage',
        '{{startup_fee}}' => 'Startup fee amount',
        '{{furniture_fee}}' => 'Furniture fee amount',
        '{{vat_amount}}' => 'VAT amount',
        '{{grand_total}}' => 'Grand total',
        '{{bank_account_holder}}' => 'Owner payout account holder',
        '{{bank_currency}}' => 'Owner payout currency',
        '{{bank_name}}' => 'Owner bank name',
        '{{bank_account_no}}' => 'Owner account number',
        '{{iban}}' => 'Owner IBAN',
        '{{swift_code}}' => 'Owner SWIFT code',
        '{{special_terms}}' => 'Special contract terms',
    ],

    'clauses' => [
        [
            'number' => '3',
            'title_en' => 'Definition of Terms & Conditions',
            'title_ar' => 'تعريف الشروط والأحكام',
            'items' => [
                ['3.1', 'The following terms and phrases, wherever they appear in this Contract, shall have the meanings specified below unless the context requires otherwise.', 'يكون للمصطلحات والعبارات التالية حيثما وردت في هذا العقد المعاني الواردة أدناه ما لم يقتض السياق خلاف ذلك.'],
                ['3.2', 'Company refers to Pattern Vacation Homes Rental, registered in Dubai Tourism and Commerce Marketing.', 'الشركة وتعني باترن لتأجير بيوت العطلات، والمسجلة لدى الجهات المختصة في دبي.'],
                ['3.3', 'Owner means the individual, group, or entity that holds legal title to the Property.', 'المالك هو الفرد أو الكيان الذي يحمل حق الملكية القانونية للعقار.'],
                ['3.4', 'Property refers to the unit owned by the Owner and operated by the Company as a holiday home.', 'العقار يشير إلى الوحدة المملوكة من قبل المالك والتي تديرها الشركة كبيت عطلات.'],
                ['3.5', 'Effective Date refers to the date of issuance of the unit permit or the date agreed by both Parties.', 'تاريخ السريان هو تاريخ إصدار تصريح الوحدة أو التاريخ المتفق عليه بين الطرفين.'],
            ],
        ],
        [
            'number' => '4',
            'title_en' => 'Obligations of the Owner',
            'title_ar' => 'التزامات المالك',
            'items' => [
                ['4.1', 'The Owner authorises the Company to manage and lease the Property to third parties.', 'يخول المالك الشركة بإدارة وتأجير العقار لأطراف خارجية.'],
                ['4.2', 'The Owner shall hand over the Property vacant and ready in accordance with DTCM standards.', 'يلتزم المالك بتسليم العقار شاغرا وجاهزا وفقا لمعايير دائرة السياحة والتسويق التجاري.'],
                ['4.3', 'The Owner shall provide all personal and Property related documents required for holiday home operations.', 'يلتزم المالك بتقديم جميع الوثائق الشخصية والوثائق الخاصة بالعقار المطلوبة للتشغيل.'],
                ['4.4', 'The Owner shall bear currency exchange differences and bank charges related to transfers to their bank account.', 'يتحمل المالك فروق صرف العملات والرسوم البنكية المتعلقة بالتحويل إلى حسابه المصرفي.'],
            ],
        ],
        [
            'number' => '5',
            'title_en' => 'Obligations of the Company',
            'title_ar' => 'التزامات الشركة',
            'items' => [
                ['5.1', 'Upon signing, the Company shall present operating documents required for the holiday home management workflow.', 'تلتزم الشركة بتقديم المستندات التشغيلية المطلوبة لإدارة بيت العطلات عند التوقيع.'],
                ['5.2', 'The Company shall conduct property and inventory checks upon guest arrival and departure.', 'تلتزم الشركة بفحص العقار والمخزون عند وصول ومغادرة النزلاء.'],
                ['5.3', 'The Company shall coordinate maintenance issues at the earliest reasonable time to maintain revenue continuity.', 'تلتزم الشركة بتنسيق أعمال الصيانة في أقرب وقت مناسب للحفاظ على استمرارية الإيرادات.'],
                ['5.4', 'The Owner may use the Property for personal purposes up to thirty calendar days per year during off-season only.', 'يجوز للمالك استخدام العقار لأغراض شخصية لمدة تصل إلى ثلاثين يوما في السنة خلال غير موسم الذروة فقط.'],
                ['5.5', 'Maintenance charges may be processed without prior approval when the cost does not exceed AED 500.', 'يجوز تنفيذ مصاريف الصيانة دون موافقة مسبقة إذا لم تتجاوز التكلفة 500 درهم.'],
                ['5.6', 'The Company will transfer net booking revenues after deducting approved dues according to the owner statement and payout rules.', 'تقوم الشركة بتحويل صافي عوائد الحجوزات بعد خصم المستحقات المعتمدة وفقا لكشف حساب المالك وقواعد الدفع.'],
            ],
        ],
        [
            'number' => '6',
            'title_en' => 'Financial Obligations',
            'title_ar' => 'الالتزامات المالية',
            'items' => [
                ['6.1', 'The Owner is obligated to pay the agreed management fee percentage to the Company.', 'يلتزم المالك بدفع النسبة المتفق عليها للشركة كرسوم إدارة.'],
                ['6.2', 'The Company shall submit owner statements showing booking revenue, expenses, deductions, and payout amounts.', 'تلتزم الشركة بتقديم كشوف حساب توضح إيرادات الحجوزات والمصاريف والخصومات ومبالغ الدفع.'],
                ['6.3', 'The Owner undertakes to cover required startup, furnishing, licensing, or compliance amounts where applicable.', 'يتعهد المالك بتغطية مبالغ بدء التشغيل أو الأثاث أو الترخيص أو الامتثال عند الاقتضاء.'],
            ],
        ],
        [
            'number' => '7',
            'title_en' => 'Termination of the Contract',
            'title_ar' => 'فسخ العقد',
            'items' => [
                ['7.1', 'The initial term of this Agreement is twelve months starting from the effective date unless otherwise agreed.', 'المدة الأولية لهذه الاتفاقية اثنا عشر شهرا من تاريخ السريان ما لم يتم الاتفاق على خلاف ذلك.'],
                ['7.2', 'Either Party may terminate the Agreement with thirty days written notice to the other Party.', 'يجوز لأي طرف إنهاء الاتفاقية بإشعار خطي للطرف الآخر قبل ثلاثين يوما.'],
                ['7.3', 'Existing reservations, outstanding payments, and future penalties must be honoured before termination.', 'يجب احترام الحجوزات القائمة وتسوية المدفوعات المستحقة وأي غرامات مستقبلية قبل الإنهاء.'],
            ],
        ],
        [
            'number' => '8',
            'title_en' => 'Additions',
            'title_ar' => 'أخرى',
            'items' => [
                ['8.1', 'This Agreement constitutes the entire agreement between the Parties and may only be amended in writing.', 'تشكل هذه الاتفاقية كامل الاتفاق بين الطرفين ولا يجوز تعديلها إلا كتابة.'],
                ['8.2', 'This Agreement supersedes previous verbal or written negotiations between the Parties.', 'تحل هذه الاتفاقية محل أي اتفاق أو تفاوض شفوي أو مكتوب سابق بين الطرفين.'],
                ['8.3', 'Either Party may propose renewal by providing written notice to the other Party.', 'يجوز لأي طرف اقتراح التجديد بإشعار خطي للطرف الآخر.'],
                ['8.4', 'Communication with tenants is managed through the Company official channels and authorised staff.', 'تتم إدارة التواصل مع المستأجرين من خلال القنوات الرسمية للشركة وموظفيها المخولين.'],
            ],
        ],
        [
            'number' => '9',
            'title_en' => 'Applicable Law and Jurisdiction',
            'title_ar' => 'القوانين المعمول بها والاختصاص القضائي',
            'items' => [
                ['9.1', 'This Agreement shall be governed by the laws of Dubai and the federal laws of the United Arab Emirates.', 'تخضع هذه الاتفاقية لقوانين إمارة دبي والقوانين الاتحادية لدولة الإمارات العربية المتحدة.'],
            ],
        ],
    ],

    'management_services' => [
        ['1', 'Guest Relations and Support', 'خدمة النزلاء والدعم'],
        ['2', 'Financial Management and Reporting', 'الإدارة المالية وإعداد التقارير'],
        ['3', 'Regular Property Inspections', 'عمليات التفتيش والجرد المنتظم'],
        ['4', 'Housekeeping & Cleaning Services', 'خدمات التدبير المنزلي والتنظيف'],
        ['5', 'Maintenance coordination', 'التنسيق لإجراءات الصيانة'],
    ],

    'startup_services' => [
        ['1', 'Property Assessment and Initial Consultation', 'التقييم والاستشارة المبدئية'],
        ['2', 'Professional Photography', 'التصوير الاحترافي'],
        ['3', 'Compliance and Licensing (DTCM)', 'استيفاء الشروط والترخيص لدى دائرة السياحة والتسويق التجاري'],
        ['4', 'Property Insurance', 'تأمين العقار'],
        ['5', 'Listing Creation and Marketing Setup', 'التسويق والإعلان'],
    ],
];
