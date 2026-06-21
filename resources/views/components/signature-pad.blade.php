@props([
    'inputName' => 'signature_data',
    'inputId' => 'signature_data',
    'label' => 'Draw signature',
    'existing' => null,
])

<div class="rounded-3xl border border-blue-100 bg-white p-3 shadow-sm shadow-blue-100/50" data-signature-pad="{{ $inputId }}">
    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">{{ $label }}</p>
            <p class="mt-1 text-xs text-slate-500">Use finger, stylus, or mouse. Sign naturally inside the box.</p>
        </div>
        <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50" data-signature-clear>Clear</button>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-dashed border-blue-200 bg-gradient-to-br from-blue-50/70 to-white">
        <canvas class="block h-56 w-full touch-none" data-signature-canvas></canvas>
        <div class="pointer-events-none absolute bottom-8 left-8 right-8 border-b border-slate-300/70"></div>
        <p class="pointer-events-none absolute bottom-3 right-4 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-300">Signature</p>
    </div>

    <input type="hidden" name="{{ $inputName }}" id="{{ $inputId }}" value="{{ old($inputName, $existing) }}" data-signature-input>
    <p class="mt-2 hidden text-xs font-bold text-rose-600" data-signature-error>Please draw your signature before submitting.</p>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-signature-pad]').forEach((pad) => {
                const canvas = pad.querySelector('[data-signature-canvas]');
                const input = pad.querySelector('[data-signature-input]');
                const clear = pad.querySelector('[data-signature-clear]');
                const error = pad.querySelector('[data-signature-error]');
                const context = canvas.getContext('2d');
                let drawing = false;
                let points = [];
                let hasInk = Boolean(input.value);

                const resize = () => {
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    const rect = canvas.getBoundingClientRect();
                    canvas.width = rect.width * ratio;
                    canvas.height = rect.height * ratio;
                    context.setTransform(ratio, 0, 0, ratio, 0, 0);
                    context.lineCap = 'round';
                    context.lineJoin = 'round';
                    context.lineWidth = 2.8;
                    context.strokeStyle = '#071a3b';
                    context.fillStyle = '#ffffff';
                    context.clearRect(0, 0, rect.width, rect.height);
                };

                const pointFromEvent = (event) => {
                    const rect = canvas.getBoundingClientRect();
                    return {
                        x: event.clientX - rect.left,
                        y: event.clientY - rect.top,
                    };
                };

                const drawSmoothLine = () => {
                    if (points.length < 2) {
                        return;
                    }

                    context.beginPath();
                    context.moveTo(points[0].x, points[0].y);

                    for (let i = 1; i < points.length - 1; i++) {
                        const midX = (points[i].x + points[i + 1].x) / 2;
                        const midY = (points[i].y + points[i + 1].y) / 2;
                        context.quadraticCurveTo(points[i].x, points[i].y, midX, midY);
                    }

                    const last = points[points.length - 1];
                    context.lineTo(last.x, last.y);
                    context.stroke();
                };

                const saveSignature = () => {
                    if (hasInk) {
                        input.value = canvas.toDataURL('image/png');
                        error.classList.add('hidden');
                    }
                };

                const start = (event) => {
                    event.preventDefault();
                    drawing = true;
                    hasInk = true;
                    points = [pointFromEvent(event)];
                    canvas.setPointerCapture?.(event.pointerId);
                };

                const move = (event) => {
                    if (! drawing) {
                        return;
                    }

                    event.preventDefault();
                    points.push(pointFromEvent(event));
                    drawSmoothLine();
                    if (points.length > 8) {
                        points = points.slice(-4);
                    }
                    saveSignature();
                };

                const stop = (event) => {
                    if (! drawing) {
                        return;
                    }

                    event.preventDefault();
                    drawing = false;
                    saveSignature();
                    canvas.releasePointerCapture?.(event.pointerId);
                };

                resize();
                window.addEventListener('resize', resize);
                canvas.addEventListener('pointerdown', start);
                canvas.addEventListener('pointermove', move);
                canvas.addEventListener('pointerup', stop);
                canvas.addEventListener('pointercancel', stop);
                clear.addEventListener('click', () => {
                    resize();
                    points = [];
                    hasInk = false;
                    input.value = '';
                });

                canvas.closest('form')?.addEventListener('submit', (event) => {
                    if (! input.value) {
                        event.preventDefault();
                        error.classList.remove('hidden');
                    }
                });
            });
        });
    </script>
@endonce
