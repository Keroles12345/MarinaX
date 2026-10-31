import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('#admin-sidebar');
    const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

    if (sidebar && sidebarOverlay && sidebarToggle) {
        const closeSidebar = () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            sidebarToggle.setAttribute('aria-expanded', 'false');
        };

        sidebarToggle.setAttribute('aria-expanded', 'false');
        sidebarToggle.addEventListener('click', () => {
            const isOpen = sidebar.classList.toggle('-translate-x-full') === false;
            sidebarOverlay.classList.toggle('hidden', !isOpen);
            document.body.classList.toggle('overflow-hidden', isOpen);
            sidebarToggle.setAttribute('aria-expanded', String(isOpen));
        });
        sidebarOverlay.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closeSidebar);
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeSidebar();
        });
    }

    document.querySelectorAll('[data-profile-menu]').forEach((menu) => {
        const toggle = menu.querySelector('[data-profile-menu-toggle]');
        const panel = menu.querySelector('[data-profile-menu-panel]');

        if (!toggle || !panel) return;

        toggle.addEventListener('click', () => {
            const isOpen = panel.classList.toggle('hidden') === false;
            toggle.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target)) {
                panel.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });
});
