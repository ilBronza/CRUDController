const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

let playwright;
try {
    playwright = require(process.env.PLAYWRIGHT_MODULE_PATH || 'playwright');
} catch (_) {
    // The other Browser tests have no external dependencies. This suite needs a real browser.
}
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/utilities/concurrentUri/client.blade.php'), 'utf8');
let browser;
test.before(async () => {
    if (playwright) browser = await playwright.chromium.launch({
        headless: true,
        ...(process.env.PLAYWRIGHT_CHANNEL ? { channel: process.env.PLAYWRIGHT_CHANNEL } : {}),
    });
});
test.after(async () => { if (browser) await browser.close(); });

async function fixture(t, initialStatus = 'gained') {
    const context = await browser.newContext();
    t.after(() => context.close());
    const requests = [];
    const errors = [];
    let status = initialStatus;
    let fail = false;
    let blocked = false;
    let pending = [];
    const config = {
        url: 'items/42/edit', token: 'signed-page-context',
        tickUrl: 'https://crud.test/concurrent-uri/tick',
        leaveUrl: 'https://crud.test/concurrent-uri/leave-page',
        interval: 3000, lifetime: 10000,
        messages: { checking: 'Checking', offline: 'Offline', expired: 'Expired', owner: 'Owner', gained: 'Gained' },
    };
    const html = '<!doctype html><html><head><meta name="csrf-token" content="csrf-value"></head><body>'
        + '<div id="ib-concurrent-uri"><div class="uk-alert"><p data-concurrent-uri-message></p></div></div>'
        + '<form method="post" action="/operation"><input name="name" value="Unsaved draft"><button>Save</button></form>'
        + '<script>window.ibConcurrentUriConfig=' + JSON.stringify(config) + ';</script>' + source + '</body></html>';
    await context.route('**/*', async route => {
        const request = route.request();
        const url = new URL(request.url());
        requests.push({ path: url.pathname, method: request.method(), headers: request.headers(), body: request.postData() });
        if (url.pathname.endsWith('/tick')) {
            if (blocked) await new Promise(resolve => pending.push(resolve));
            if (fail) return route.abort('failed');
            return route.fulfill({ contentType: 'application/json', body: JSON.stringify({ status, message: status }) });
        }
        if (request.method() === 'GET' && url.pathname === '/page') {
            return route.fulfill({ contentType: 'text/html', body: html });
        }
        return route.fulfill({
            contentType: 'application/json',
            headers: { 'access-control-allow-origin': '*', 'access-control-allow-headers': '*' },
            body: '{"saved":true,"released":true}',
        });
    });
    async function open() {
        const page = await context.newPage();
        page.on('pageerror', error => errors.push(error.message));
        await page.goto('https://crud.test/page');
        await page.waitForFunction(() => window.ibConcurrentUri && document.querySelector('[data-concurrent-uri-message]').textContent !== 'Checking');
        return page;
    }
    const page = await open();
    t.after(() => assert.deepEqual(errors, []));
    return {
        page, requests, open,
        status(value) { status = value; },
        fail(value) { fail = value; },
        block(value) {
            blocked = value;
            if (!value) { pending.forEach(resolve => resolve()); pending = []; }
        },
    };
}

const options = { skip: !playwright && 'Install Playwright or set PLAYWRIGHT_MODULE_PATH' };

test('owner credentials are sent through forms, fetch and XMLHttpRequest', options, async t => {
    const { page, requests } = await fixture(t);
    assert.equal(await page.evaluate(() => window.ibConcurrentUri.isOwner()), true);
    const key = await page.locator('input[name="_crud_page_key"]').inputValue();
    assert.match(key, /^[a-f0-9]{48}$/);
    await page.evaluate(async () => {
        await fetch('/operation', { method: 'POST', headers: { 'X-Custom': 'kept' }, body: 'fetch' });
        await new Promise(resolve => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/operation');
            xhr.onload = resolve;
            xhr.send('xhr');
        });
    });
    const writes = requests.filter(request => request.path === '/operation');
    assert.equal(writes.length, 2);
    for (const write of writes) {
        assert.equal(write.headers['x-crud-page-key'], key);
        assert.equal(write.headers['x-crud-concurrent-token'], 'signed-page-context');
    }
    assert.equal(writes[0].headers['x-custom'], 'kept');
    assert.equal(await page.locator('input[name="_crud_concurrent_token"]').inputValue(), 'signed-page-context');
});

test('two documents rendered from identical HTML receive distinct tab identities', options, async t => {
    const fixtureData = await fixture(t);
    const second = await fixtureData.open();
    const firstKey = await fixtureData.page.locator('input[name="_crud_page_key"]').inputValue();
    const secondKey = await second.locator('input[name="_crud_page_key"]').inputValue();
    assert.notEqual(firstKey, secondKey);
});

test('a conflicting tab blocks form submission, preserves input and updates text on takeover', options, async t => {
    const fixtureData = await fixture(t, 'morePages');
    const { page } = fixtureData;
    assert.equal(await page.evaluate(() => window.ibConcurrentUri.isOwner()), false);
    assert.equal(await page.evaluate(() => document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))), false);
    assert.equal(await page.locator('input[name="name"]').inputValue(), 'Unsaved draft');
    fixtureData.status('gained');
    await page.evaluate(() => window.ibConcurrentUri.refresh());
    await page.waitForFunction(() => window.ibConcurrentUri.isOwner());
    assert.equal(await page.locator('[data-concurrent-uri-message]').textContent(), 'gained');
    assert.equal(await page.locator('.uk-alert').getAttribute('class'), 'uk-alert uk-alert-success');
});

test('a failed heartbeat suspends operations and recovery retains the draft', options, async t => {
    const fixtureData = await fixture(t);
    fixtureData.fail(true);
    await fixtureData.page.evaluate(() => window.ibConcurrentUri.refresh());
    await fixtureData.page.waitForFunction(() => !window.ibConcurrentUri.isOwner());
    assert.equal(await fixtureData.page.locator('[data-concurrent-uri-message]').textContent(), 'Offline');
    fixtureData.fail(false);
    fixtureData.status('kept');
    await fixtureData.page.evaluate(() => window.ibConcurrentUri.refresh());
    await fixtureData.page.waitForFunction(() => window.ibConcurrentUri.isOwner());
    assert.equal(await fixtureData.page.locator('input[name="name"]').inputValue(), 'Unsaved draft');
});

test('repeated refresh requests never overlap heartbeats', options, async t => {
    const fixtureData = await fixture(t);
    fixtureData.block(true);
    const before = fixtureData.requests.filter(request => request.path.endsWith('/tick')).length;
    const nextRequest = fixtureData.page.waitForRequest(request => request.url().endsWith('/tick'));
    const nextResponse = fixtureData.page.waitForResponse(response => response.url().endsWith('/tick'));
    await fixtureData.page.evaluate(() => {
        window.ibConcurrentUri.refresh();
        window.ibConcurrentUri.refresh();
        window.ibConcurrentUri.refresh();
    });
    await nextRequest;
    fixtureData.block(false);
    await nextResponse;
    assert.equal(fixtureData.requests.filter(request => request.path.endsWith('/tick')).length, before + 1);
});

test('pagehide releases with CSRF and restoring a cached document generates a new identity', options, async t => {
    const fixtureData = await fixture(t);
    const oldKey = await fixtureData.page.locator('input[name="_crud_page_key"]').inputValue();
    const release = fixtureData.page.waitForRequest(request => request.url().endsWith('/leave-page'));
    await fixtureData.page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pagehide')));
    const request = await release;
    const data = new URLSearchParams(request.postData());
    assert.equal(data.get('pageKey'), oldKey);
    assert.equal(data.get('_token'), 'csrf-value');
    await fixtureData.page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true })));
    await fixtureData.page.waitForFunction(() => window.ibConcurrentUri.isOwner());
    assert.notEqual(await fixtureData.page.locator('input[name="_crud_page_key"]').inputValue(), oldKey);
});

test('cancelling a navigation does not release the current tab', options, async t => {
    const fixtureData = await fixture(t);
    await fixtureData.page.evaluate(() => window.dispatchEvent(new Event('beforeunload', { cancelable: true })));
    assert.equal(fixtureData.requests.some(request => request.path.endsWith('/leave-page')), false);
    assert.equal(await fixtureData.page.evaluate(() => window.ibConcurrentUri.isOwner()), true);
});

test('normal form submission includes the context and preserves the submitted values', options, async t => {
    const fixtureData = await fixture(t);
    const requestPromise = fixtureData.page.waitForRequest(request => new URL(request.url()).pathname === '/operation');
    await fixtureData.page.locator('button').click();
    const request = await requestPromise;
    const data = new URLSearchParams(request.postData());
    assert.equal(data.get('_crud_concurrent_token'), 'signed-page-context');
    assert.match(data.get('_crud_page_key'), /^[a-f0-9]{48}$/);
    assert.equal(data.get('name'), 'Unsaved draft');
});

test('expired local leases cannot submit even before a throttled timer fires', options, async t => {
    const { page } = await fixture(t);
    const result = await page.evaluate(() => {
        const now = Date.now;
        Date.now = () => now() + 11000;
        const owner = window.ibConcurrentUri.isOwner();
        const submitted = document.querySelector('form').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        Date.now = now;
        return { owner, submitted };
    });
    assert.deepEqual(result, { owner: false, submitted: false });
});

test('read requests and external requests do not receive page credentials', options, async t => {
    const { page, requests } = await fixture(t);
    await page.evaluate(async () => {
        await fetch('/read');
        await fetch('https://external.test/action', { method: 'POST', body: 'external' });
    });
    for (const request of requests.filter(request => ['/read', '/action'].includes(request.path))) {
        assert.equal(request.headers['x-crud-page-key'], undefined);
        assert.equal(request.headers['x-crud-concurrent-token'], undefined);
    }
});

test('forms inserted after page load receive context and support a POST submitter override', options, async t => {
    const { page } = await fixture(t);
    await page.evaluate(() => {
        const form = document.createElement('form');
        form.id = 'dynamic';
        form.action = '/operation';
        form.method = 'get';
        form.innerHTML = '<button formmethod="post">Dynamic save</button>';
        document.body.appendChild(form);
    });
    const requestPromise = page.waitForRequest(request => new URL(request.url()).pathname === '/operation');
    await page.locator('#dynamic button').click();
    const request = await requestPromise;
    assert.equal(request.method(), 'POST');
    assert.equal(new URLSearchParams(request.postData()).get('_crud_concurrent_token'), 'signed-page-context');
});

test('a submitter targeting another origin never leaks hidden page credentials', options, async t => {
    const { page } = await fixture(t);
    await page.locator('button').evaluate(button => button.setAttribute('formaction', 'https://external.test/action'));
    const requestPromise = page.waitForRequest(request => request.url() === 'https://external.test/action');
    await page.locator('button').click();
    const request = await requestPromise;
    const body = new URLSearchParams(request.postData());
    assert.equal(body.has('_crud_concurrent_token'), false);
    assert.equal(body.has('_crud_page_key'), false);
});
