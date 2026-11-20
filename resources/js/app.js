// Mobile slide-out menu: the only script the public site needs; everything else is plain HTML.
const menu = document.querySelector('[data-menu]');

if (menu) {
    const openers = document.querySelectorAll('[data-menu-open]');

    const setOpen = (open) => {
        menu.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        openers.forEach((button) => button.setAttribute('aria-expanded', String(open)));

        if (open) {
            menu.querySelector('a, button')?.focus();
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
