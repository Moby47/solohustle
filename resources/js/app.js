// The public site's only script: the mobile menu and the analytics consent bar. Everything else is plain HTML.

// Mobile slide-out menu.
const menu = document.querySelector('[data-menu]');

if (menu) {
    const openers = document.querySelectorAll('[data-menu-open]');

    const setOpen = (open) => {
        menu.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        openers.forEach((button) => button.setAttribute('aria-expanded', String(open)));

        if (open) {
            menu.querySelector('a, button, input')?.focus();
        } else {
            openers[0]?.focus();
        }
    };

    openers.forEach((button) => button.addEventListener('click', () => setOpen(true)));
    menu.querySelectorAll('[data-menu-close]').forEach((el) => el.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !menu.classList.contains('hidden')) {
            setOpen(false);
        }
    });
}

// Google Analytics, only after the visitor accepts. window.siteAnalyticsId is set by the layout on the live site.
const analyticsId = window.siteAnalyticsId;
const consentKey = 'sh-analytics-consent';
const consentBar = document.querySelector('[data-cookie-consent]');

const readConsent = () => {
    try {
        return localStorage.getItem(consentKey);
    } catch {
        return null;
    }
};

const saveConsent = (value) => {
    try {
        value === null ? localStorage.removeItem(consentKey) : localStorage.setItem(consentKey, value);
    } catch {
        // Private browsing: the bar simply asks again next visit.
    }
};

const loadAnalytics = () => {
    if (!analyticsId || window.gtag) {
        return;
    }

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(analyticsId)}`;
    document.head.appendChild(script);

    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() {
        window.dataLayer.push(arguments);
    };
    window.gtag('js', new Date());
    window.gtag('config', analyticsId);
};

if (analyticsId) {
    const choice = readConsent();

    if (choice === 'granted') {
        loadAnalytics();
    } else if (choice !== 'denied' && consentBar) {
        consentBar.hidden = false;
    }

    consentBar?.querySelectorAll('[data-consent]').forEach((button) => {
        button.addEventListener('click', () => {
            saveConsent(button.dataset.consent);
            consentBar.hidden = true;

            if (button.dataset.consent === 'granted') {
                loadAnalytics();
            }
        });
    });

    document.querySelectorAll('[data-cookie-settings]').forEach((link) => {
        link.addEventListener('click', () => {
            saveConsent(null);

            if (consentBar) {
                consentBar.hidden = false;
            }
        });
    });
}
