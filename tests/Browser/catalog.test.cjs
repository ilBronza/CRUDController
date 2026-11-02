const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/catalog/scripts.blade.php'), 'utf8')
    .replace(/^<script>\s*/, '').replace(/<\/script>\s*$/, '');
const frontendSource = fs.readFileSync(path.join(__dirname, '../../resources/views/catalog/frontend-scripts.blade.php'), 'utf8')
    .replace(/^<script>\s*/, '').replace(/<\/script>\s*$/, '');
const settle = () => new Promise(resolve => setImmediate(resolve));

// A controlled transport deliberately delivers responses even after abort(),
// reproducing the stale-response race independently of browser cancellation.
function browser({ frontend = null, href, transport = true } = {}) {
    const listeners = {};
    const windowListeners = {};
    const requests = [];
    const history = [];
    let root;
    const window = {
        AbortController,
        location: { href: href || 'https://example.test/catalog?page=3', origin: 'https://example.test', assign(url) { this.href = url; } },
        history: {
            pushState(_state, _title, url) { history.push(url); window.location.href = url; },
            replaceState(_state, _title, url) { window.location.href = url; },
        },
        addEventListener(type, callback) { windowListeners[type] = callback; },
        matchMedia() { return { matches: true }; },
    };
    const document = {
        activeElement: null,
        addEventListener(type, callback) { listeners[type] = callback; },
        querySelectorAll(selector) { return selector === '[data-catalog-filter]' ? root.filters : [root]; },
        getElementById(id) { return id === root.id ? root : null; },
        createElement() { return element(); },
    };
    function element() {
        return {
            hidden: true, attributes: {}, textContent: '', children: [], isConnected: true,
            setAttribute(key, value) { this.attributes[key] = value; },
            append(...nodes) { this.children.push(...nodes); },
            replaceChildren(...nodes) { this.children = nodes; },
            focus() { document.activeElement = this; },
        };
    }
    function makeRoot(content) {
        const node = {
            id: 'crud-catalog', content, isConnected: true, dataset: { errorMessage: 'Loading failed', filterMode: frontend ? 'frontend' : 'ajax' },
            attributes: {}, parts: {},
            options: ['filters[category][]', 'filters[light_source][]'].map(name => ({
                scrollTop: 0, scrollHeight: 900, clientHeight: 288,
                querySelector() { return { name }; },
            })),
            setAttribute(key, value) { this.attributes[key] = value; },
            querySelector(selector) { return this.parts[selector]; },
            querySelectorAll(selector) {
                if (selector === '.crud-catalog-options') return this.options;
                if (selector === '[data-catalog-filter]') return this.filters;
                if (selector === 'template[data-catalog-frontend-item]') return this.templates;
                if (selector === '[data-catalog-reset]') return [this.reset];
                return [];
            },
            matches(selector) { return selector === '[data-crud-catalog]'; },
            contains() { return false; },
            replaceWith(next) { this.isConnected = false; root = next; },
        };
        node.filters = ['category', 'light_source', 'search', 'variant'].map((key, index) => {
            const button = element();
            const title = element();
            const content = { scrollTop: 0, scrollHeight: key === 'search' ? 60 : 1500, clientHeight: 288 };
            button.dataset = { expandLabel: 'Show all', collapseLabel: 'Collapse filter' };
            const filter = {
                open: true, dataset: { catalogFilter: key, filterExpanded: 'open', filterState: 'open' },
                button, title, content,
                querySelector(selector) {
                    if (selector === '.crud-catalog-filter-content') return content;
                    if (selector === '[data-catalog-filter-title]') return title;
                    return button;
                },
                querySelectorAll() { return node.options[index] ? [node.options[index]] : []; },
                matches(selector) { return selector === '[data-catalog-filter]'; },
            };
            button.closest = selector => selector === '[data-catalog-filter-expand]' ? button : selector === '[data-catalog-filter]' ? filter : null;
            title.closest = selector => selector === '[data-catalog-filter-title]' ? title : selector === '[data-catalog-filter]' ? filter : null;
            return filter;
        });
        for (const name of ['loading', 'error', 'toggle', 'total']) node.parts[`[data-catalog-${name}]`] = element();
        for (const selector of ['.crud-catalog-grid', '[data-catalog-active]', '[data-catalog-pagination]']) node.parts[selector] = element();
        node.parts['[data-catalog-frontend]'] = { textContent: JSON.stringify(frontend) };
        node.templates = (frontend?.items || []).map((_, index) => ({ content: { firstElementChild: { index } } }));
        node.reset = element();
        const inputs = [
            { name: 'sort', value: 'subject', type: 'select-one' },
            { name: 'filters[search]', value: '', type: 'search' },
            { name: 'filters[approved]', value: '', type: 'select-one' },
            { name: 'filters[amount][min]', value: '', type: 'number' },
            { name: 'filters[amount][max]', value: '', type: 'number' },
            ...['draft', 'sent'].map(value => ({ name: 'filters[status][]', value, type: 'checkbox', checked: false })),
        ];
        const form = {
            action: 'https://example.test/catalog', entries: [['sort', 'subject']],
            elements: inputs,
            closest() { return node; },
            matches(selector) { return selector === '[data-catalog-form]'; },
            reportValidity() { return true; },
        };
        node.parts['[data-catalog-form]'] = form;
        return node;
    }
    root = makeRoot('initial results');
    const fetch = (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject }));
    if (transport) window.fetch = fetch;
    class FormData {
        constructor(form) { this.entries = form.entries; }
        [Symbol.iterator]() { return this.entries[Symbol.iterator](); }
    }
    class DOMParser {
        parseFromString(html) { return { getElementById() { return makeRoot(html); } }; }
    }
    vm.runInNewContext(frontendSource + '\n' + source, { window, document, fetch, FormData, DOMParser, URL, AbortController, Map });
    return {
        requests, history, window, listeners, windowListeners,
        get root() { return root; },
        get activeElement() { return document.activeElement; },
        get visible() { return root.parts['.crud-catalog-grid'].children.filter(node => node.index !== undefined).map(node => node.index); },
        click(link) {
            listeners.click({
                target: { closest(selector) { return selector === 'a[data-catalog-link]' ? link : null; } },
                button: 0, preventDefault() {},
            });
        },
        change(entries) {
            root.parts['[data-catalog-form]'].entries = entries;
            listeners.change({ target: { closest() { return root; }, matches() { return true; } } });
        },
        submit(entries) {
            const form = root.parts['[data-catalog-form]'];
            form.entries = entries;
            listeners.submit({ target: form, preventDefault() {} });
        },
        input() { listeners.input({ target: { closest() { return root; }, matches() { return true; } } }); },
        expand(index = 0) { listeners.click({ target: root.filters[index].button }); },
        toggleFilter(index = 0) {
            const filter = root.filters[index];
            listeners.click({ target: filter.title });
            filter.open = !filter.open;
            listeners.toggle({ target: filter });
        },
        async respond(index, content) {
            requests[index].resolve({ ok: true, text: async () => content });
            await settle();
        },
    };
}

test('GET form state retains zero and context, removes blanks and resets page', async () => {
    const app = browser();
    app.submit([['project', '12'], ['filters[approved]', '0'], ['filters[search]', ''], ['sort', 'amount_desc'], ['page', '3']]);
    const url = new URL(app.requests[0].url);
    assert.equal(url.searchParams.get('filters[approved]'), '0');
    assert.equal(url.searchParams.get('project'), '12');
    assert.equal(url.searchParams.has('filters[search]'), false);
    assert.equal(url.searchParams.has('page'), false);
    assert.equal(app.root.attributes['aria-busy'], 'true');
    await app.respond(0, 'filtered results');
    assert.equal(app.root.content, 'filtered results');
    assert.equal(app.history.length, 1);
    assert.equal(app.root.attributes['aria-busy'], 'false');
});

function frontendConfig() {
    return {
        filters: {
            search: { type: 'text', label: 'Search' },
            status: { type: 'multiple', label: 'Status', options: { draft: 'Draft', sent: '<Sent>' } },
            amount: { type: 'number-range', label: 'Amount', min: 0 },
            approved: { type: 'boolean', label: 'Approved' },
        },
        sorts: { subject: { column: 'subject' }, amount_desc: { column: 'amount', direction: 'desc' } },
        defaultSort: 'subject', perPage: 2, context: { project: 'authorized' },
        items: [
            { filters: { search: ['Alpha 10%_!', 'First'], status: 'draft', amount: 0, approved: false }, sorts: { subject: 'Alpha', amount_desc: '0.00' } },
            { filters: { search: ['Beta', null], status: 'sent', amount: '25.50', approved: 1 }, sorts: { subject: 'Beta', amount_desc: '25.50' } },
            { filters: { search: ['Gamma', 'ALPHA 10%_!'], status: 'draft', amount: '0', approved: '0' }, sorts: { subject: 'Gamma', amount_desc: '0.00' } },
            { filters: { search: 'Delta', status: 'sent', amount: null, approved: null }, sorts: { subject: 'Delta', amount_desc: null } },
            { filters: { search: 'Epsilon', status: 'draft', amount: 100, approved: true }, sorts: { subject: 'Epsilon', amount_desc: '100.00' } },
        ],
        messages: {
            zero: 'No results', one: '1 result', many: ':count results', empty: 'Nothing matches',
            remove: 'Remove :filter', previous: 'Previous', next: 'Next', pagination: 'Pages', page: 'Page :page',
            yes: 'Yes', no: 'No', from: 'from :value', to: 'to :value', invalid: 'Invalid filters', range: 'Invalid range',
        },
    };
}

test('frontend combines filters across all loaded pages without any transport', () => {
    const app = browser({ frontend: frontendConfig(), href: 'https://example.test/catalog', transport: false });
    assert.deepEqual(app.visible, [0, 1]);
    app.submit([
        ['filters[search]', ' alpha 10%_! '], ['filters[status][]', 'draft'],
        ['filters[approved]', '0'], ['filters[amount][min]', '0'], ['filters[amount][max]', '0'],
    ]);
    assert.deepEqual(app.visible, [0, 2]);
    assert.equal(app.root.parts['[data-catalog-total]'].textContent, '2 results');
    assert.equal(app.requests.length, 0);
    assert.equal(new URL(app.history[0]).searchParams.get('project'), 'authorized');
    assert.equal(app.root.parts['[data-catalog-error]'].hidden, true);
});

test('frontend sorts decimals, paginates locally and retains deterministic ties', () => {
    const app = browser({ frontend: frontendConfig(), href: 'https://example.test/catalog' });
    app.change([['sort', 'amount_desc']]);
    assert.deepEqual(app.visible, [4, 1]);
    const nav = app.root.parts['[data-catalog-pagination]'].children[0];
    const next = nav.children[0].children.at(-1).children[0];
    next.closest = () => app.root;
    next.hasAttribute = () => false;
    app.click(next);
    assert.deepEqual(app.visible, [0, 2]);
    assert.equal(new URL(app.window.location.href).searchParams.get('page'), '2');
    assert.equal(app.requests.length, 0);
});

test('frontend removes individual selections and resets while preserving current sort and context', () => {
    const app = browser({ frontend: frontendConfig(), href: 'https://example.test/catalog' });
    app.submit([['filters[status][]', 'draft'], ['filters[status][]', 'sent'], ['filters[approved]', '0'], ['sort', 'amount_desc']]);
    const active = app.root.parts['[data-catalog-active]'];
    assert.equal(active.children[1].children[0].textContent, 'Status: <Sent> ×');
    const removeDraft = active.children[0].children[0];
    removeDraft.closest = () => app.root;
    removeDraft.hasAttribute = () => false;
    app.click(removeDraft);
    assert.deepEqual(app.visible, []);
    assert.equal(app.root.parts['[data-catalog-total]'].textContent, 'No results');
    app.root.reset.closest = () => app.root;
    app.root.reset.hasAttribute = () => false;
    app.click(app.root.reset);
    assert.deepEqual(app.visible, [4, 1]);
    assert.equal(active.hidden, true);
    assert.equal(new URL(app.window.location.href).searchParams.get('sort'), 'amount_desc');
    assert.equal(new URL(app.window.location.href).searchParams.get('project'), 'authorized');
    assert.equal(app.requests.length, 0);
});

test('frontend restores URL filters, checkboxes and pagination through browser history without requests', () => {
    const app = browser({ frontend: frontendConfig(), href: 'https://example.test/catalog?sort=amount_desc' });
    app.window.location.href = 'https://example.test/catalog?filters[status][0]=draft&filters[approved]=0&page=2';
    app.windowListeners.popstate();
    assert.deepEqual(app.visible, [0, 2]); // Out-of-range page is clamped to the last matching page.
    const inputs = app.root.parts['[data-catalog-form]'].elements;
    assert.equal(inputs.find(input => input.name === 'sort').value, 'subject');
    assert.equal(inputs.find(input => input.name === 'filters[approved]').value, '0');
    assert.equal(inputs.find(input => input.type === 'checkbox' && input.value === 'draft').checked, true);
    assert.equal(inputs.find(input => input.type === 'checkbox' && input.value === 'sent').checked, false);
    assert.equal(app.history.length, 0);
    assert.equal(app.requests.length, 0);
});

test('frontend keeps the previous results and URL when a range or selection is invalid', () => {
    const app = browser({ frontend: frontendConfig(), href: 'https://example.test/catalog' });
    app.submit([['filters[amount][min]', '30'], ['filters[amount][max]', '20']]);
    assert.deepEqual(app.visible, [0, 1]);
    assert.equal(app.root.parts['[data-catalog-error]'].textContent, 'Invalid range');
    app.submit([['filters[status][]', '__proto__']]);
    assert.equal(app.root.parts['[data-catalog-error]'].textContent, 'Invalid filters');
    app.submit([['filters[amount][min]', '0x10']]);
    assert.equal(app.root.parts['[data-catalog-error]'].textContent, 'Invalid range');
    app.submit([['filters[amount][max]', '0']]);
    assert.deepEqual(app.visible, [0, 2]);
    assert.equal(app.root.parts['[data-catalog-error]'].hidden, true);
    assert.equal(app.history.length, 1);
    assert.equal(app.requests.length, 0);
});

test('filter lists retain independent scroll positions, including scrolling while loading', async () => {
    const app = browser();
    const original = app.root;
    original.options[0].scrollTop = 120;
    original.options[1].scrollTop = 640;
    app.submit([['filters[category][]', 'wash']]);
    original.options[0].scrollTop = 272;
    await app.respond(0, 'filtered results');
    assert.notEqual(app.root, original);
    assert.deepEqual(app.root.options.map(options => options.scrollTop), [272, 640]);
});

test('an older response cannot overwrite a newer selection or its history', async () => {
    const app = browser();
    app.submit([['filters[search]', 'old']]);
    app.submit([['filters[search]', 'new']]);
    assert.equal(app.requests[0].options.signal.aborted, true);
    await app.respond(1, 'new results');
    await app.respond(0, 'old results');
    assert.equal(app.root.content, 'new results');
    assert.equal(app.history.length, 1);
    assert.equal(new URL(app.history[0]).searchParams.get('filters[search]'), 'new');
});

test('editing an input cancels an in-flight response even before the next submit', async () => {
    const app = browser();
    app.submit([['filters[search]', 'submitted']]);
    app.input();
    await app.respond(0, 'obsolete results');
    assert.equal(app.root.content, 'initial results');
    assert.equal(app.history.length, 0);
    assert.equal(app.root.attributes['aria-busy'], 'false');
});

test('back/forward restores URL state without appending history', async () => {
    const app = browser();
    app.submit([['sort', 'amount_desc']]);
    app.window.location.href = 'https://example.test/catalog?sort=subject&page=2';
    app.windowListeners.popstate();
    assert.equal(app.requests[0].options.signal.aborted, true);
    assert.equal(app.requests[1].url, app.window.location.href);
    await app.respond(1, 'restored page two');
    await app.respond(0, 'obsolete sorted page');
    assert.equal(app.root.content, 'restored page two');
    assert.equal(app.history.length, 0);
});

test('422 retains current results and displays escaped text, then allows retry', async () => {
    const app = browser();
    app.submit([['filters[amount][min]', '30'], ['filters[amount][max]', '20']]);
    app.requests[0].resolve({
        ok: false, status: 422, headers: { get: () => 'application/json' },
        json: async () => ({ errors: { amount: ['Invalid range <example>'] } }),
    });
    await settle();
    assert.equal(app.root.content, 'initial results');
    assert.equal(app.root.parts['[data-catalog-error]'].textContent, 'Invalid range <example>');
    assert.equal(app.root.parts['[data-catalog-error]'].hidden, false);
    assert.equal(app.history.length, 0);
    app.submit([['sort', 'subject']]);
    await app.respond(1, 'recovered');
    assert.equal(app.root.content, 'recovered');
});

test('failure of an obsolete request does not overwrite newer success', async () => {
    const app = browser();
    app.submit([['sort', 'subject']]);
    app.submit([['sort', 'amount_desc']]);
    await app.respond(1, 'latest');
    app.requests[0].reject(new Error('Old network error'));
    await settle();
    assert.equal(app.root.parts['[data-catalog-error]'].hidden, true);
    assert.equal(app.root.content, 'latest');
});

test('modified link clicks retain browser navigation behavior', () => {
    const app = browser();
    const link = { href: 'https://example.test/catalog?page=2', closest() { return app.root; }, hasAttribute() { return false; } };
    const event = {
        target: { closest(selector) { return selector === 'a[data-catalog-link]' ? link : null; } },
        button: 0, ctrlKey: true, preventDefault() { throw new Error('Must not intercept modified click'); },
    };
    app.listeners.click(event);
    assert.equal(app.requests.length, 0);
});

test('initial partial opening becomes full-open or closed after interaction without changing values', () => {
    const app = browser();
    const filter = app.root.filters[0];
    const form = app.root.parts['[data-catalog-form]'];
    form.entries = [['filters[category][]', 'wash']];
    assert.equal(filter.dataset.filterState, 'open');
    assert.equal(filter.button.textContent, 'Show all');
    app.expand();
    assert.equal(filter.dataset.filterState, 'full-open');
    assert.equal(filter.button.textContent, 'Collapse filter');
    app.toggleFilter();
    assert.equal(filter.dataset.filterState, 'closed');
    app.toggleFilter();
    assert.equal(filter.dataset.filterState, 'full-open');
    app.expand();
    assert.equal(filter.dataset.filterState, 'closed');
    assert.equal(filter.button.hidden, true);
    assert.equal(app.activeElement, filter.title);
    app.toggleFilter();
    assert.equal(filter.dataset.filterState, 'full-open');
    app.toggleFilter();
    assert.equal(filter.dataset.filterState, 'closed');
    assert.equal(app.root.filters[1].dataset.filterState, 'open');
    assert.deepEqual(form.entries, [['filters[category][]', 'wash']]);
    assert.equal(app.requests.length, 0);
    assert.equal(app.history.length, 0);
});

test('Ajax preserves independent accordion states, including changes while loading', async () => {
    const app = browser();
    app.submit([['filters[category][]', 'wash']]);
    app.expand();
    app.expand(1);
    app.toggleFilter(1);
    await app.respond(0, 'filtered results');
    assert.equal(app.root.filters[0].dataset.filterState, 'full-open');
    assert.equal(app.root.filters[1].dataset.filterState, 'closed');
    assert.equal(app.root.filters[2].dataset.filterState, 'open');
    app.toggleFilter(1);
    assert.equal(app.root.filters[1].dataset.filterState, 'full-open');
    app.windowListeners.popstate();
    await app.respond(1, 'previous results');
    assert.equal(app.root.filters[0].dataset.filterState, 'full-open');
    assert.equal(app.root.filters[1].dataset.filterState, 'full-open');
    assert.equal(app.history.length, 1);
});

test('expansion controls measure the entire content, including custom controls without option lists', () => {
    const app = browser();
    assert.equal(app.root.filters[0].button.hidden, false);
    assert.equal(app.root.filters[2].button.hidden, true);
    assert.equal(app.root.filters[3].button.hidden, false);
    app.expand(3);
    assert.equal(app.root.filters[3].dataset.filterState, 'full-open');
    app.root.filters[0].content.scrollHeight = 100;
    app.windowListeners.resize();
    assert.equal(app.root.filters[0].button.hidden, true);
    app.root.filters[0].content.scrollHeight = 900;
    app.windowListeners.resize();
    assert.equal(app.root.filters[0].button.hidden, false);
});

test('the summary closes an initially partial custom filter and reopens it fully', () => {
    const app = browser();
    app.toggleFilter(3);
    assert.equal(app.root.filters[3].dataset.filterState, 'closed');
    app.toggleFilter(3);
    assert.equal(app.root.filters[3].dataset.filterState, 'full-open');
    assert.equal(app.requests.length, 0);
});

test('Ajax retains scrolling of the entire partially open filter', async () => {
    const app = browser();
    app.root.filters[3].content.scrollTop = 350;
    app.submit([['filters[variant][pixel_pitch][]', '1.5']]);
    app.root.filters[3].content.scrollTop = 480;
    await app.respond(0, 'filtered variants');
    assert.equal(app.root.filters[3].content.scrollTop, 480);
    assert.equal(app.root.filters[3].dataset.filterState, 'open');
});
