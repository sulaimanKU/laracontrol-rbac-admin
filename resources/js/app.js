import './bootstrap';

const root = document.documentElement;
const colorScheme = matchMedia('(prefers-color-scheme: dark)');
let preference = 'system';
try {
    const saved = localStorage.getItem('laracontrol.theme');
    if (['light', 'dark', 'system'].includes(saved)) preference = saved;
} catch {}

function applyTheme() {
    const resolved = preference === 'system' ? (colorScheme.matches ? 'dark' : 'light') : preference;
    root.dataset.theme = resolved;
    document.querySelectorAll('[data-theme-choice]').forEach(input => { input.checked = input.value === preference; });
    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
        button.setAttribute('aria-label', resolved === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
        button.title = button.getAttribute('aria-label');
    });
}
function setTheme(value) {
    preference = value;
    let stored = true;
    try { localStorage.setItem('laracontrol.theme', preference); } catch { stored = false; }
    applyTheme();
    document.querySelectorAll('[data-theme-status]').forEach(status => {
        status.textContent = stored
            ? (preference === 'system' ? 'Following your system theme. Preference saved in this browser.' : `${preference === 'dark' ? 'Dark' : 'Light'} theme saved in this browser.`)
            : 'Theme applied for this visit. Browser storage is unavailable.';
    });
}
document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
    setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
}));
document.querySelectorAll('[data-theme-choice]').forEach(input => input.addEventListener('change', () => setTheme(input.value)));
colorScheme.addEventListener('change', () => { if (preference === 'system') applyTheme(); });
window.addEventListener('storage', event => {
    if (event.key === 'laracontrol.theme') {
        preference = ['light', 'dark', 'system'].includes(event.newValue) ? event.newValue : 'system';
        applyTheme();
    }
});
applyTheme();
document.querySelectorAll('[data-theme-status]').forEach(status => {
    status.textContent = preference === 'system' ? 'Following your system theme.' : `${preference === 'dark' ? 'Dark' : 'Light'} theme is selected for this browser.`;
});

// The mobile drawer traps focus, hides background content from keyboard navigation,
// and returns focus to its trigger when closed.
const sidebar = document.getElementById('workspace-sidebar');
const workspace = document.getElementById('workspace-main');
const backdrop = document.getElementById('drawer-backdrop');
const menuButton = document.querySelector('[data-drawer-open]');
const mobile = matchMedia('(max-width: 768px)');
let drawerOpen = false;
function closeDrawer(restoreFocus = true) {
    drawerOpen = false;
    document.body.classList.remove('drawer-open');
    if (sidebar) {
        sidebar.inert = mobile.matches;
        sidebar.removeAttribute('role');
        sidebar.removeAttribute('aria-modal');
    }
    if (workspace) workspace.inert = false;
    if (backdrop) backdrop.hidden = true;
    menuButton?.setAttribute('aria-expanded', 'false');
    if (restoreFocus && mobile.matches) menuButton?.focus();
}
function openDrawer() {
    if (!sidebar || !mobile.matches) return;
    drawerOpen = true;
    sidebar.inert = false;
    sidebar.setAttribute('role', 'dialog');
    sidebar.setAttribute('aria-modal', 'true');
    document.body.classList.add('drawer-open');
    if (workspace) workspace.inert = true;
    if (backdrop) backdrop.hidden = false;
    menuButton?.setAttribute('aria-expanded', 'true');
    sidebar.querySelector('[data-drawer-close]')?.focus();
}
menuButton?.addEventListener('click', openDrawer);
document.querySelector('[data-drawer-close]')?.addEventListener('click', () => closeDrawer());
backdrop?.addEventListener('click', () => closeDrawer());
mobile.addEventListener('change', () => closeDrawer(false));
closeDrawer(false);

// Native details keep menus usable with keyboard input and without JavaScript.
function positionRowMenu(details) {
    const panel = details.querySelector('.dropdown-panel');
    if (!panel || !details.open) return;
    const bounds = details.querySelector('summary').getBoundingClientRect();
    const height = panel.offsetHeight;
    const width = panel.offsetWidth;
    panel.style.left = `${Math.max(8, Math.min(bounds.right - width, innerWidth - width - 8))}px`;
    panel.style.top = `${bounds.bottom + height + 12 > innerHeight ? Math.max(8, bounds.top - height - 6) : bounds.bottom + 6}px`;
}
document.querySelectorAll('.dropdown').forEach(details => {
    details.addEventListener('toggle', () => {
        if (!details.open) return;
        document.querySelectorAll('.dropdown[open]').forEach(other => { if (other !== details) other.open = false; });
        if (details.classList.contains('row-dropdown')) positionRowMenu(details);
    });
});
document.addEventListener('click', event => {
    document.querySelectorAll('.dropdown[open]').forEach(details => { if (!details.contains(event.target)) details.open = false; });
});
window.addEventListener('resize', () => {
    document.querySelectorAll('.row-dropdown[open]').forEach(positionRowMenu);
});
window.addEventListener('scroll', event => {
    document.querySelectorAll('.row-dropdown[open]').forEach(details => {
        if (!details.contains(event.target)) details.open = false;
    });
}, true);

const searchDialog = document.getElementById('workspace-search');
const searchInput = document.getElementById('workspace-query');
function openSearch() {
    if (!searchDialog || searchDialog.open) return;
    searchDialog.showModal();
    searchInput?.focus();
}
document.querySelector('[data-search-open]')?.addEventListener('click', openSearch);
document.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
searchDialog?.addEventListener('click', event => { if (event.target === searchDialog) searchDialog.close(); });
const destinations = [...document.querySelectorAll('[data-search-query]')];
destinations.forEach(link => { link.dataset.base = link.href; });
searchInput?.addEventListener('input', () => {
    destinations.forEach(link => {
        const url = new URL(link.dataset.base);
        if (searchInput.value.trim()) url.searchParams.set('q', searchInput.value.trim());
        link.href = url.href;
    });
});
searchInput?.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
        event.preventDefault();
        const destination = destinations[0] || searchDialog.querySelector('.search-destinations a');
        if (destination) window.location.assign(destination.href);
    }
});
document.addEventListener('keydown', event => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && searchDialog) {
        event.preventDefault();
        openSearch();
    }
    if (event.key === 'Escape') {
        if (drawerOpen) { event.preventDefault(); closeDrawer(); }
        document.querySelectorAll('.dropdown[open]').forEach(details => {
            details.open = false;
            details.querySelector('summary')?.focus();
        });
    }
    if (event.key === 'Tab' && drawerOpen && sidebar) {
        const items = [...sidebar.querySelectorAll('a[href], button:not([disabled])')].filter(item => item.getClientRects().length);
        const first = items[0], last = items.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
});

document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(reveal));
        const label = document.querySelector(`label[for="${input.id}"]`)?.textContent.replace('*', '').trim().toLowerCase() || 'password';
        button.setAttribute('aria-label', `${reveal ? 'Hide' : 'Show'} ${label}`);
    });
});

// Re-submit through the normal form path, preserving validation, method spoofing,
// CSRF fields, and externally associated permission-matrix inputs.
const confirmation = document.getElementById('confirm-dialog');
const approved = new WeakSet();
let pending = null;
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (approved.has(form)) { approved.delete(form); return; }
        if (!confirmation || typeof confirmation.showModal !== 'function') {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
            return;
        }
        event.preventDefault();
        if (pending) return;
        pending = { form, submitter: event.submitter };
        document.getElementById('confirm-message').textContent = form.dataset.confirm;
        confirmation.returnValue = 'cancel';
        confirmation.showModal();
        confirmation.querySelector('[value="cancel"]')?.focus();
    });
});
confirmation?.addEventListener('close', () => {
    const action = pending;
    pending = null;
    if (action && confirmation.returnValue === 'confirm') {
        approved.add(action.form);
        action.form.requestSubmit(action.submitter || undefined);
    }
});
