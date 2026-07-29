{{-- Smart mobile photo uploader: use this partial for every maintainer task image field. --}}
@php($inputId = str($field)->replace('_', '-')->append('-upload')->toString())

<section data-mobile-image-upload data-max-files="5" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
    {{-- The named input is populated by JavaScript so camera and library selections submit as one image array. --}}
    <input id="{{ $inputId }}" data-mobile-image-files type="file" name="{{ $field }}[]" multiple accept="image/*,.heic,.heif" class="hidden">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-black text-[#071a3b]">{{ $label }}</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Take photos now or choose them from your phone. Up to 5 images, 5 MB each.</p>
        </div>
        <span data-mobile-image-count class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-slate-500 shadow-sm">0 / 5</span>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3">
        {{-- capture="environment" requests the rear camera on supported mobile devices. --}}
        <label for="{{ $inputId }}-camera" class="flex min-h-16 cursor-pointer items-center justify-center gap-2 rounded-xl bg-[#6d3be8] px-3 text-center text-xs font-black text-white shadow-lg shadow-purple-600/20">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h4l2-3h4l2 3h4v12H4zM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8"/></svg>
            Camera
        </label>
        <input id="{{ $inputId }}-camera" data-mobile-image-source type="file" accept="image/*,.heic,.heif" capture="environment" class="hidden">

        {{-- This second source leaves capture unset, allowing the normal phone photo library picker. --}}
        <label for="{{ $inputId }}-library" class="flex min-h-16 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-center text-xs font-black text-slate-700">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v14H4zM8 11l2.5 3 2-2 3.5 5M8 9h.01"/></svg>
            Photo Library
        </label>
        <input id="{{ $inputId }}-library" data-mobile-image-source type="file" accept="image/*,.heic,.heif" multiple class="hidden">
    </div>

    {{-- JavaScript renders selected photo previews and gives each file a remove action before upload. --}}
    <div data-mobile-image-previews class="mt-4 grid grid-cols-2 gap-3 empty:hidden"></div>

    {{-- This live status block becomes visible during XMLHttpRequest upload progress and on completion. --}}
    <div data-mobile-image-status class="mt-4 hidden" aria-live="polite">
        <div class="flex items-center justify-between gap-3 text-xs font-black text-slate-600"><span data-mobile-image-status-text>Ready to upload</span><span data-mobile-image-progress-value>0%</span></div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200"><div data-mobile-image-progress class="h-full w-0 rounded-full bg-emerald-500 transition-[width] duration-200"></div></div>
    </div>
</section>

@once
    @push('scripts')
    <script>
        // Smart uploader: merge camera and library picks into one named input and render local previews.
        (() => {
            const imageContainers = document.querySelectorAll('[data-mobile-image-upload]');

            imageContainers.forEach((container) => {
                const maximumFiles = Number(container.dataset.maxFiles || 5);
                const canonicalInput = container.querySelector('[data-mobile-image-files]');
                const sourceInputs = container.querySelectorAll('[data-mobile-image-source]');
                const previewArea = container.querySelector('[data-mobile-image-previews]');
                const count = container.querySelector('[data-mobile-image-count]');
                const status = container.querySelector('[data-mobile-image-status]');
                const statusText = container.querySelector('[data-mobile-image-status-text]');
                const progress = container.querySelector('[data-mobile-image-progress]');
                const progressValue = container.querySelector('[data-mobile-image-progress-value]');
                let selectedFiles = [];

                // Keep the server-bound input synchronized with all of the selected photos.
                const syncFiles = () => {
                    const transfer = new DataTransfer();
                    selectedFiles.forEach((file) => transfer.items.add(file));
                    canonicalInput.files = transfer.files;
                    count.textContent = `${selectedFiles.length} / ${maximumFiles}`;
                };

                // Render lightweight previews from browser object URLs without uploading files twice.
                const renderPreviews = () => {
                    previewArea.replaceChildren();
                    selectedFiles.forEach((file, index) => {
                        const tile = document.createElement('div');
                        tile.className = 'relative overflow-hidden rounded-xl border border-slate-200 bg-white';
                        const preview = document.createElement('img');
                        preview.className = 'h-24 w-full object-cover';
                        preview.alt = `Selected photo ${index + 1}`;
                        preview.src = URL.createObjectURL(file);
                        preview.addEventListener('load', () => URL.revokeObjectURL(preview.src), { once: true });
                        const details = document.createElement('div');
                        details.className = 'flex items-center justify-between gap-2 p-2';
                        const name = document.createElement('span');
                        name.className = 'min-w-0 truncate text-[10px] font-bold text-slate-500';
                        name.textContent = file.name || `Photo ${index + 1}`;
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'shrink-0 text-xs font-black text-rose-600';
                        remove.textContent = 'Remove';
                        remove.addEventListener('click', () => {
                            selectedFiles.splice(index, 1);
                            syncFiles();
                            renderPreviews();
                        });
                        details.append(name, remove);
                        tile.append(preview, details);
                        previewArea.append(tile);
                    });
                };

                // Display a shared upload status for every image component in a submitted form.
                const setUploadStatus = (message, percentage, state = 'uploading') => {
                    status.classList.remove('hidden');
                    statusText.textContent = message;
                    progress.style.width = `${percentage}%`;
                    progressValue.textContent = `${percentage}%`;
                    progress.classList.toggle('bg-emerald-500', state === 'uploaded');
                    progress.classList.toggle('bg-[#6d3be8]', state !== 'uploaded');
                };

                sourceInputs.forEach((source) => source.addEventListener('change', () => {
                    const incomingFiles = Array.from(source.files || []);
                    const availableSlots = maximumFiles - selectedFiles.length;
                    const newFiles = incomingFiles
                        .filter((file) => file.type.startsWith('image/') || /\.(heic|heif)$/i.test(file.name))
                        .filter((file) => file.size <= 5 * 1024 * 1024)
                        .filter((file) => !selectedFiles.some((selected) => selected.name === file.name && selected.size === file.size && selected.lastModified === file.lastModified))
                        .slice(0, Math.max(availableSlots, 0));

                    selectedFiles = [...selectedFiles, ...newFiles];
                    source.value = '';
                    syncFiles();
                    renderPreviews();
                    if (incomingFiles.length !== newFiles.length) setUploadStatus(`Only ${maximumFiles} images up to 5 MB can be uploaded.`, 0, 'warning');
                }));

                // Expose the upload UI to the form-level XHR progress handler below.
                container.mobileImageUpload = { hasFiles: () => selectedFiles.length > 0, setUploadStatus };
            });

            // Submit image forms through XHR so mobile users see real byte-level upload progress.
            document.querySelectorAll('form[data-mobile-photo-upload]').forEach((form) => form.addEventListener('submit', (event) => {
                const uploaders = Array.from(form.querySelectorAll('[data-mobile-image-upload]')).map((container) => container.mobileImageUpload).filter(Boolean);
                if (! uploaders.some((uploader) => uploader.hasFiles())) return;

                event.preventDefault();
                form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => button.disabled = true);
                uploaders.forEach((uploader) => uploader.setUploadStatus('Uploading photos…', 0));

                const request = new XMLHttpRequest();
                request.open((form.method || 'POST').toUpperCase(), form.action);
                request.upload.addEventListener('progress', (progressEvent) => {
                    if (! progressEvent.lengthComputable) return;
                    const percentage = Math.min(99, Math.round((progressEvent.loaded / progressEvent.total) * 100));
                    uploaders.forEach((uploader) => uploader.setUploadStatus(`Uploading photos… ${percentage}%`, percentage));
                });
                request.addEventListener('load', () => {
                    const responsePath = request.responseURL ? new URL(request.responseURL).pathname : window.location.pathname;
                    if (responsePath === window.location.pathname) {
                        // A validation redirect returned this same form; render it directly so its errors stay visible.
                        document.open();
                        document.write(request.responseText);
                        document.close();
                        return;
                    }

                    uploaders.forEach((uploader) => uploader.setUploadStatus('Photos uploaded', 100, 'uploaded'));
                    window.setTimeout(() => window.location.assign(request.responseURL), 250);
                });
                request.addEventListener('error', () => {
                    uploaders.forEach((uploader) => uploader.setUploadStatus('Upload failed. Please try again.', 0, 'warning'));
                    form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => button.disabled = false);
                });
                request.send(new FormData(form));
            }));
        })();
    </script>
    @endpush
@endonce
