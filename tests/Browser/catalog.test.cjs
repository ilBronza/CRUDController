const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/catalog/scripts.blade.php'), 'utf8')
    .replace(/^<script>\s*/, '').replace(/<\/script>\s*$/, '');
const settle = () => new Promise(resolve => setImmediate(resolve));

// A controlled transport deliberately delivers responses even after abort(),
// reproducing the stale-response race independently of browser cancellation.
function browser() {
    const listeners = {};
    const windowListeners = {};
    const requests = [];
    const history = [];
    let root;
    const window = {
        AbortController,
        location: { href: 'https://example.test/catalog?page=3', origin: 'https://example.test', assign(url) { this.href = url; } },
        history: { pushState(_state, _title, url) { history.push(url); window.location.href = url; } },
        addEventListener(type, callback) { windowListeners[type] = callback; },
        matchMedia() { return { matches: true }; },
    };
    const document = {
        activeElement: null,
        addEventListener(type, callback) { listeners[type] = callback; },
        querySelectorAll() { return [root]; },
        getElementById(id) { return id === root.id ? root : null; },
    };
    function element() {
        return {
            hidden: true, attributes: {}, textContent: '',
            setAttribute(key, value) { this.attributes[key] = value; },
            focus() { document.activeElement = this; },
        };
    }
    function makeRoot(content) {
        const node = {
            id: 'crud-catalog', content, isConnected: true, dataset: { errorMessage: 'Loading failed' },
            attributes: {}, parts: {},
            options: ['filters[category][]', 'filters[light_source][]'].map(name => ({
                scrollTop: 0,
                querySelector() { return { name }; },
            })),
            setAttribute(key, value) { this.attributes[key] = value; },
            querySelector(selector) { return this.parts[selector]; },
            querySelectorAll(selector) { return selector === '.crud-catalog-options' ? this.options : []; },
            matches(selector) { return selector === '[data-crud-catalog]'; },
            contains() { return false; },
            replaceWith(next) { this.isConnected = false; root = next; },
        };
        for (const name of ['loading', 'error', 'toggle', 'total']) node.parts[`[data-catalog-${name}]`] = element();
        const form = {
            action: 'https://example.test/catalog', entries: [['sort', 'subject']],
            closest() { return node; },
            matches(selector) { return selector === '[data-catalog-form]'; },
            reportValidity() { return true; },
        };
        node.parts['[data-catalog-form]'] = form;
        return node;
    }
    root = makeRoot('initial results');
    const fetch = (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject }));
    window.fetch = fetch;
    class FormData {
        constructor(form) { this.entries = form.entries; }
        [Symbol.iterator]() { return this.entries[Symbol.iterator](); }
    }
    class DOMParser {
        parseFromString(html) { return { getElementById() { return makeRoot(html); } }; }
    }
    vm.runInNewContext(source, { window, document, fetch, FormData, DOMParser, URL, AbortController, Map });
    return {
        requests, history, window, listeners, windowListeners,
        get root() { return root; },
        submit(entries) {
            const form = root.parts['[data-catalog-form]'];
            form.entries = entries;
            listeners.submit({ target: form, preventDefault() {} });
        },
        input() { listeners.input({ target: { closest() { return root; }, matches() { return true; } } }); },
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
        target: { closest(selector) { return selector === '[data-catalog-toggle]' ? null : link; } },
        button: 0, ctrlKey: true, preventDefault() { throw new Error('Must not intercept modified click'); },
    };
    app.listeners.click(event);
    assert.equal(app.requests.length, 0);
});
