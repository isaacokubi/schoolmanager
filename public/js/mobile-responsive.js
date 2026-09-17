document.addEventListener('DOMContentLoaded', () => {
    const mq = window.matchMedia('(max-width: 800px)');

    const close = (button, target, backdrop) => {
        target?.classList.remove('is-open');
        button?.setAttribute('aria-expanded', 'false');
        button?.setAttribute('aria-label', 'Open navigation');
        backdrop?.classList.remove('is-visible');
        document.body.classList.remove('mobile-menu-open');
    };

    const setupMenu = ({host, target, label, className}) => {
        if (!host || !target || host.querySelector(`.${className}`)) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.setAttribute('aria-controls', target.id || `${className}-target`);
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-label', `Open ${label}`);
        button.innerHTML = '<span></span><span></span><span></span>';

        const backdrop = document.createElement('button');
        backdrop.type = 'button';
        backdrop.className = 'mobile-menu-backdrop';
        backdrop.setAttribute('aria-label', 'Close navigation');
        backdrop.tabIndex = -1;

        if (!target.id) target.id = `${className}-target`;
        target.parentNode.insertBefore(backdrop, target);
        host.insertBefore(button, host.firstChild);

        const toggle = () => {
            const open = !target.classList.contains('is-open');
            if (open) {
                target.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
                button.setAttribute('aria-label', `Close ${label}`);
                backdrop.classList.add('is-visible');
                document.body.classList.add('mobile-menu-open');
            } else {
                close(button, target, backdrop);
            }
        };

        button.addEventListener('click', toggle);
        backdrop.addEventListener('click', () => close(button, target, backdrop));
        target.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
            if (mq.matches) close(button, target, backdrop);
        }));

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && target.classList.contains('is-open')) {
                close(button, target, backdrop);
                button.focus();
            }
        });

        const sync = event => {
            if (!event.matches) close(button, target, backdrop);
        };
        mq.addEventListener?.('change', sync);
    };

    const publicNav = document.querySelector('.nav:not(.admin-top)');
    const publicLinks = publicNav?.querySelector('.navlinks');
    if (publicNav && publicLinks) {
        setupMenu({host: publicNav, target: publicLinks, label: 'navigation', className: 'mobile-menu-toggle'});
    }

    const adminTop = document.querySelector('.admin-top');
    const sidebar = document.querySelector('.admin-layout .sidebar');
    if (adminTop && sidebar) {
        setupMenu({host: adminTop, target: sidebar, label: 'menu', className: 'admin-menu-toggle'});
    }
});
