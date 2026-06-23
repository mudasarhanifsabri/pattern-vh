@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-identity-ocr]').forEach((root) => {
                const fileInput = root.querySelector('input[type="file"][name="document"]');
                const button = root.querySelector('[data-ocr-scan]');
                const status = root.querySelector('[data-ocr-status]');
                const form = root.closest('form');

                if (!fileInput || !button || !status || !form) return;

                const setStatus = (message, tone = 'slate') => {
                    status.textContent = message;
                    status.className = 'mt-3 rounded-xl px-3 py-2 text-xs font-bold ' + {
                        slate: 'bg-slate-50 text-slate-500',
                        blue: 'bg-blue-50 text-blue-700',
                        emerald: 'bg-emerald-50 text-emerald-700',
                        amber: 'bg-amber-50 text-amber-700',
                        rose: 'bg-rose-50 text-rose-700',
                    }[tone];
                    status.classList.remove('hidden');
                };

                const fillField = (name, value) => {
                    if (!value) return;
                    const input = form.querySelector(`[name="${name}"]`);
                    if (!input) return;
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                };

                button.addEventListener('click', async () => {
                    if (!fileInput.files.length) {
                        setStatus('Choose a passport or Emirates ID file first.', 'amber');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('document', fileInput.files[0]);
                    button.disabled = true;
                    button.textContent = 'Scanning...';
                    setStatus('Scanning document with OCR. Please wait...', 'blue');

                    try {
                        const response = await fetch('{{ route('identity-documents.ocr') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const responseText = await response.text();
                        let data = {};

                        try {
                            data = responseText ? JSON.parse(responseText) : {};
                        } catch (error) {
                            data = { message: responseText || 'OCR scan failed. Please fill manually.' };
                        }

                        if (!response.ok) {
                            setStatus(data.message || 'OCR scan failed. Please fill manually.', 'rose');
                            return;
                        }

                        Object.entries(data.fields || {}).forEach(([field, value]) => fillField(field, value));
                        setStatus(data.message || 'Document scanned. Please review before saving.', data.ok ? 'emerald' : 'amber');
                    } catch (error) {
                        setStatus('OCR scan failed. Please check AWS Textract settings or fill manually.', 'rose');
                    } finally {
                        button.disabled = false;
                        button.textContent = 'Scan & fill form';
                    }
                });
            });
        });
    </script>
@endonce
