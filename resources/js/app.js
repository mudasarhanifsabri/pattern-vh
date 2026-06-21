

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

let deferredInstallPrompt = null;

const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const isMobileViewport = () => window.matchMedia('(max-width: 767px)').matches;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    showTenantInstallPrompt();
});

window.addEventListener('load', () => {
    window.setTimeout(showTenantInstallPrompt, 1200);
});

function showTenantInstallPrompt() {
    const prompt = document.getElementById('tenant-install-prompt');
    const button = document.getElementById('tenant-install-button');
    const dismiss = document.getElementById('tenant-install-dismiss');
    const copy = document.getElementById('tenant-install-copy');

    if (!prompt || !button || !dismiss || !isMobileViewport() || isStandalone()) return;
    if (localStorage.getItem('patternTenantInstallDismissed') === '1') return;

    if (!deferredInstallPrompt) {
        copy.textContent = 'Tap Install app for instructions. Direct install appears only when the browser allows PWA install.';
        button.textContent = 'Install app';
        button.classList.remove('hidden');
    }

    prompt.classList.remove('hidden');

    dismiss.onclick = () => {
        localStorage.setItem('patternTenantInstallDismissed', '1');
        prompt.classList.add('hidden');
    };

    button.onclick = async () => {
        if (!deferredInstallPrompt) {
            const isIOS = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
            copy.textContent = isIOS
                ? 'iPhone: tap Share in Safari, then Add to Home Screen.'
                : 'Android: open Chrome menu, then Install app/Add to Home screen. For real install prompt, open the app with HTTPS.';
            return;
        }
        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        prompt.classList.add('hidden');
    };
}
