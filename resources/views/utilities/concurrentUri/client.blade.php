<script>
(function () {
    'use strict';
    function getParentClient() {
        try {
            if (window.parent !== window && window.parent.location.origin === window.location.origin) {
                return window.parent.ibConcurrentUri || null;
            }
        } catch (_) {
            // Frames on another origin must never share page credentials.
        }
        return null;
    }

    const parentClient = getParentClient();
    const config = parentClient?.config || window.ibConcurrentUriConfig;
    if (!config || window.ibConcurrentUri) return;

    const originalFetch = window.fetch.bind(window);
    let pageKey;
    let owner = false;
    let deadline = 0;
    let timer;
    let expiryTimer;
    let inFlight = null;
    let generation = 0;
    let stopped = false;
    let receivedStatus = false;
    let currentMessage = config.messages.checking;
    let currentState = 'checking';

    function newPageKey() {
        return Array.from(window.crypto.getRandomValues(new Uint8Array(24)),
            value => value.toString(16).padStart(2, '0')).join('');
    }

    function isOwner() {
        if (parentClient) return parentClient.isOwner();
        return owner && Date.now() < deadline;
    }

    function unavailableMessage() {
        if (parentClient) return parentClient.message?.() || config.messages.expired;
        return owner && Date.now() >= deadline ? config.messages.expired : currentMessage;
    }

    function getState() {
        if (parentClient) return parentClient.state?.() || (parentClient.isOwner() ? 'owner' : 'unavailable');
        return owner && Date.now() >= deadline ? 'expired' : currentState;
    }

    function setState(allowed, message, level = 'warning', state = allowed ? 'owner' : 'unavailable') {
        owner = allowed;
        currentMessage = message;
        currentState = state;
        window.concurrentUriStatus = allowed ? 'owner' : 'notOwner';
        document.body?.classList.toggle('ib-concurrent-uri-owner', isOwner());
        const banner = document.querySelector('#ib-concurrent-uri .uk-alert');
        if (banner) {
            banner.classList.remove('uk-alert-success', 'uk-alert-warning', 'uk-alert-danger');
            banner.classList.add('uk-alert-' + level);
            const text = banner.querySelector('[data-concurrent-uri-message]');
            if (text) text.textContent = message;
        }
        window.dispatchEvent(new CustomEvent('ib-concurrent-uri-state', {
            detail: { state: getState(), owner: isOwner(), message, level },
        }));
    }

    function unsafeLocalRequest(url, method) {
        const target = new URL(url, window.location.href);
        // These client exclusions do not bypass the server-side middleware.
        const independentPaths = config.clientExcludedPaths || [];
        return !['GET', 'HEAD', 'OPTIONS'].includes(String(method || 'GET').toUpperCase())
            && target.origin === window.location.origin
            && !independentPaths.includes(target.pathname);
    }

    function heartbeatUrl(url) {
        const path = new URL(url, window.location.href).pathname;
        return path === new URL(config.tickUrl, window.location.href).pathname
            || path === new URL(config.leaveUrl, window.location.href).pathname;
    }

    function credentials() {
        if (parentClient) return parentClient.headers();
        return { 'X-CRUD-Concurrent-Token': config.token, 'X-CRUD-Page-Key': pageKey };
    }

    function attachForm(form, action = form.action || window.location.href, method = form.method) {
        if (!unsafeLocalRequest(action, method)) {
            form.querySelectorAll('input[name="_crud_concurrent_token"], input[name="_crud_page_key"]').forEach(input => input.remove());
            return;
        }
        const headers = credentials();
        for (const [name, value] of Object.entries({
            _crud_concurrent_token: headers['X-CRUD-Concurrent-Token'],
            _crud_page_key: headers['X-CRUD-Page-Key'],
        })) {
            let input = form.querySelector('input[name="' + name + '"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                form.appendChild(input);
            }
            input.value = value;
        }
    }

    function attachForms() {
        document.querySelectorAll('form').forEach(form => attachForm(form));
    }

    function payload() {
        return new URLSearchParams({
            url: config.url,
            token: config.token,
            pageKey,
            _token: document.querySelector('meta[name="csrf-token"]')?.content || '',
        });
    }

    function schedule() {
        clearTimeout(timer);
        if (!stopped) timer = setTimeout(tick, config.interval);
    }

    async function tick() {
        if (parentClient) return parentClient.refresh();
        if (inFlight || stopped) return;
        clearTimeout(timer);
        const epoch = generation;
        const started = Date.now();
        const controller = new AbortController();
        inFlight = controller;
        const timeout = setTimeout(() => controller.abort(), Math.max(1000, config.interval * 2));
        try {
            const response = await originalFetch(config.tickUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: payload(),
                signal: controller.signal,
            });
            if (epoch !== generation) return;
            if ([401, 403, 404, 419].includes(response.status)) {
                stopped = true;
                throw new Error(config.messages.expired);
            }
            if (!response.ok) throw new Error(config.messages.offline);
            const data = await response.json();
            if (epoch !== generation) return;
            const allowed = ['gained', 'kept'].includes(data.status);
            clearTimeout(expiryTimer);
            deadline = started + config.lifetime;
            if (allowed && Date.now() < deadline) {
                const message = !receivedStatus ? config.messages.owner : data.message;
                setState(true, message || config.messages.owner, 'success');
                expiryTimer = setTimeout(() => {
                    setState(false, config.messages.expired, 'warning', 'expired');
                    tick();
                }, Math.max(0, deadline - Date.now()));
            } else {
                setState(false, data.message || config.messages.offline,
                    data.status === 'notAllowed' ? 'danger' : 'warning',
                    ['morePages', 'notAllowed'].includes(data.status) ? 'conflict' : 'unavailable');
            }
            receivedStatus = true;
        } catch (error) {
            if (epoch === generation) setState(false, stopped ? config.messages.expired : config.messages.offline,
                'warning', stopped ? 'expired' : 'offline');
        } finally {
            clearTimeout(timeout);
            if (epoch === generation) {
                inFlight = null;
                schedule();
            }
        }
    }

    // Supply credentials to fetch callers, including inline editors.
    window.fetch = function (input, options = {}) {
        const url = input instanceof Request ? input.url : String(input);
        const method = options.method || (input instanceof Request ? input.method : 'GET');
        if (!unsafeLocalRequest(url, method) || heartbeatUrl(url)) return originalFetch(input, options);
        if (!isOwner()) return Promise.reject(new Error(unavailableMessage()));
        const headers = new Headers(options.headers || (input instanceof Request ? input.headers : undefined));
        Object.entries(credentials()).forEach(([name, value]) => headers.set(name, value));
        return originalFetch(input, { ...options, headers });
    };

    // jQuery, Dropzone and plain XMLHttpRequest use the same server-side guard.
    const xhrRequests = new WeakMap();
    const originalOpen = XMLHttpRequest.prototype.open;
    const originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (method, url) {
        xhrRequests.set(this, { method, url });
        return originalOpen.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function () {
        const request = xhrRequests.get(this);
        if (request && unsafeLocalRequest(request.url, request.method) && !heartbeatUrl(request.url)) {
            Object.entries(credentials()).forEach(([name, value]) => this.setRequestHeader(name, value));
        }
        return originalSend.apply(this, arguments);
    };

    function prepareForm(form, submitter) {
        const method = submitter?.getAttribute('formmethod') || form.method;
        const action = submitter?.getAttribute('formaction') || form.action || window.location.href;
        if (unsafeLocalRequest(action, method) && !isOwner()) {
            setState(false, unavailableMessage(), 'warning', getState());
            tick();
            return false;
        }
        attachForm(form, action, method);

        return true;
    }

    // Datatable modals submit temporary forms synchronously. Native submit()
    // skips the submit event and runs before MutationObserver can attach context.
    const originalSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () {
        if (!prepareForm(this)) return;
        return originalSubmit.apply(this, arguments);
    };

    document.addEventListener('submit', function (event) {
        if (!prepareForm(event.target, event.submitter)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    function start() {
        pageKey = newPageKey();
        stopped = false;
        receivedStatus = false;
        deadline = 0;
        setState(false, config.messages.checking, 'warning', 'checking');
        attachForms();
        tick();
    }

    function leave() {
        if (stopped) return;
        const body = payload();
        stopped = true;
        generation++;
        clearTimeout(timer);
        clearTimeout(expiryTimer);
        if (inFlight) inFlight.abort();
        inFlight = null;
        setState(false, config.messages.checking, 'warning', 'checking');
        // pagehide does not fire when the user cancels a beforeunload prompt.
        if (!navigator.sendBeacon || !navigator.sendBeacon(config.leaveUrl, body)) {
            originalFetch(config.leaveUrl, {
                method: 'POST', credentials: 'same-origin', body, keepalive: true,
            }).catch(() => {});
        }
    }

    window.ibConcurrentUri = { isOwner, headers: credentials, refresh: tick, config, message: unavailableMessage, state: getState };
    if (parentClient) {
        const syncParentState = () => {
            const allowed = isOwner();
            setState(allowed, allowed ? config.messages.owner : unavailableMessage(), allowed ? 'success' : 'warning', getState());
        };
        const subscribe = () => {
            window.parent.addEventListener('ib-concurrent-uri-state', syncParentState);
            syncParentState();
        };
        subscribe();
        window.addEventListener('pagehide', () => {
            window.parent.removeEventListener('ib-concurrent-uri-state', syncParentState);
        });
        window.addEventListener('pageshow', event => {
            if (event.persisted) {
                subscribe();
                attachForms();
            }
        });
    } else {
        pageKey = newPageKey();
        setState(false, config.messages.checking, 'warning', 'checking');
        window.addEventListener('pagehide', leave);
        window.addEventListener('pageshow', event => {
            if (event.persisted) start(); // A new key makes delayed release messages harmless.
        });
        window.addEventListener('focus', () => {
            if (!isOwner()) setState(false, config.messages.checking, 'warning', 'checking');
            tick();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                if (!isOwner()) setState(false, config.messages.checking, 'warning', 'checking');
                tick();
            }
        });
    }

    function ready() {
        attachForms();
        new MutationObserver(records => {
            records.forEach(record => record.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                if (node.matches('form')) attachForm(node);
                node.querySelectorAll('form').forEach(form => attachForm(form));
            }));
        }).observe(document.body, { childList: true, subtree: true });
        if (!parentClient) tick();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready, { once: true });
    else ready();
})();
</script>
