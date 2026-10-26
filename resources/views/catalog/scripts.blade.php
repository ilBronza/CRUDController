<script>
(function () {
    'use strict';
    if (window.__crudCatalogInstalled || !window.fetch || !window.AbortController) return;
    window.__crudCatalogInstalled = true;
    const states = new Map();

    function initialize(root) {
        root.dataset.enhanced = 'true';
        root.querySelector('[data-catalog-toggle]').hidden = false;
    }

    function stateFor(root) {
        if (!states.has(root.id)) states.set(root.id, { sequence: 0, controller: null });
        return states.get(root.id);
    }

    function busy(root, value) {
        root.setAttribute('aria-busy', String(value));
        root.querySelector('[data-catalog-loading]').hidden = !value;
    }

    function cancel(root) {
        const state = stateFor(root);
        state.sequence++;
        if (state.controller) state.controller.abort();
        state.controller = null;
        busy(root, false);
    }

    function formUrl(form) {
        const url = new URL(form.action, window.location.href);
        url.search = '';
        for (const [key, value] of new FormData(form)) {
            if (value !== '' && key !== 'page') url.searchParams.append(key, value);
        }
        return url;
    }

    async function load(root, target, push) {
        const url = new URL(target, window.location.href);
        if (url.origin !== window.location.origin) return;
        cancel(root);
        const state = stateFor(root);
        const sequence = state.sequence;
        state.controller = new AbortController();
        const errorBox = root.querySelector('[data-catalog-error]');
        errorBox.hidden = true;
        busy(root, true);

        try {
            const response = await fetch(url.href, {
                signal: state.controller.signal,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-Crud-Catalog': '1' }
            });
            if (!response.ok) {
                let message = root.dataset.errorMessage;
                if (response.status === 422 && (response.headers.get('content-type') || '').includes('application/json')) {
                    const payload = await response.json();
                    message = Object.values(payload.errors || {}).flat().join(' ') || message;
                }
                throw new Error(message);
            }
            const html = await response.text();
            if (state.sequence !== sequence || !root.isConnected) return;
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const replacement = parsed.getElementById(root.id);
            if (!replacement || !replacement.matches('[data-crud-catalog]')) {
                window.location.assign(url.href);
                return;
            }

            const focusedId = root.contains(document.activeElement) ? document.activeElement.id : null;
            const optionsScroll = new Map();
            root.querySelectorAll('.crud-catalog-options').forEach(options => {
                const input = options.querySelector('[name]');
                if (input) optionsScroll.set(input.name, options.scrollTop);
            });
            const isOpen = root.dataset.filtersOpen === 'true';
            replacement.dataset.filtersOpen = String(isOpen);
            replacement.querySelector('[data-catalog-toggle]').setAttribute('aria-expanded', String(isOpen));
            if (push && url.href !== window.location.href) window.history.pushState(null, '', url.href);
            root.replaceWith(replacement);
            initialize(replacement);
            if (window.UIkit) window.UIkit.update(replacement);
            const focusTarget = focusedId && document.getElementById(focusedId);
            (focusTarget || replacement.querySelector('[data-catalog-total]')).focus({ preventScroll: true });
            replacement.querySelectorAll('.crud-catalog-options').forEach(options => {
                const input = options.querySelector('[name]');
                if (input && optionsScroll.has(input.name)) options.scrollTop = optionsScroll.get(input.name);
            });
        } catch (error) {
            if (state.sequence !== sequence || error.name === 'AbortError' || !root.isConnected) return;
            errorBox.textContent = error.message || root.dataset.errorMessage;
            errorBox.hidden = false;
        } finally {
            if (state.sequence === sequence) {
                state.controller = null;
                busy(document.getElementById(root.id) || root, false);
            }
        }
    }

    document.addEventListener('submit', function (event) {
        if (!event.target.matches('[data-catalog-form]')) return;
        event.preventDefault();
        load(event.target.closest('[data-crud-catalog]'), formUrl(event.target), true);
    });

    document.addEventListener('input', function (event) {
        const root = event.target.closest('[data-crud-catalog]');
        if (root && event.target.matches('input, select')) cancel(root);
    });

    document.addEventListener('change', function (event) {
        const root = event.target.closest('[data-crud-catalog]');
        if (!root || !event.target.matches('select, input[type="checkbox"]')) return;
        const form = root.querySelector('[data-catalog-form]');
        if (form.reportValidity()) load(root, formUrl(form), true);
    });

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-catalog-toggle]');
        if (toggle) {
            const root = toggle.closest('[data-crud-catalog]');
            const open = root.dataset.filtersOpen !== 'true';
            root.dataset.filtersOpen = String(open);
            toggle.setAttribute('aria-expanded', String(open));
            return;
        }
        const link = event.target.closest('a[data-catalog-link]');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
        const root = link.closest('[data-crud-catalog]');
        if (!root || new URL(link.href).origin !== window.location.origin) return;
        event.preventDefault();
        load(root, link.href, true);
    });

    document.addEventListener('keydown', function (event) {
        const root = event.target.closest('[data-crud-catalog]');
        if (event.key === 'Escape' && root && root.dataset.filtersOpen === 'true' && window.matchMedia('(max-width: 959px)').matches) {
            root.dataset.filtersOpen = 'false';
            const toggle = root.querySelector('[data-catalog-toggle]');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });

    window.addEventListener('popstate', function () {
        document.querySelectorAll('[data-crud-catalog]').forEach(root => load(root, window.location.href, false));
    });
    document.querySelectorAll('[data-crud-catalog]').forEach(initialize);
})();
</script>
