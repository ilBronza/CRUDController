const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const template = fs.readFileSync(path.join(__dirname, '../../resources/views/uikit/form/translatable-fields.blade.php'), 'utf8')
    .replace(/^<script>\s*/, '').replace(/<\/script>\s*$/, '');

function browser(errors = [], readyState = 'complete') {
    const events = {};
    const changes = [];
    const classes = () => {
        const values = new Set();
        return { add(value) { values.add(value); }, contains(value) { return values.has(value); } };
    };
    const controls = ['title', 'en_title'].map(name => ({ name, focused: false, focus() { this.focused = true; } }));
    const panels = controls.map(control => ({
        classList: classes(),
        querySelectorAll() { return [control]; },
        contains(target) { return target === control; },
    }));
    panels[0].classList.add('uk-active');
    const navigation = {
        children: panels.map(() => ({ classList: classes() })),
        nextElementSibling: { children: panels },
    };
    const group = {
        querySelector() { return navigation; },
        addEventListener(type, listener) { events[type] = listener; },
    };
    const form = { querySelectorAll() { return [group]; } };
    const document = {
        readyState,
        currentScript: { closest() { return form; } },
        addEventListener(type, listener) { events[type] = listener; },
    };
    const window = { UIkit: { switcher(node) {
        assert.equal(node, navigation);
        return { show(index) { changes.push(index); } };
    } } };
    const source = template.replace(/const errors = .*;/, 'const errors = ' + JSON.stringify(errors) + ';');
    vm.runInNewContext(source, { document, window, requestAnimationFrame: callback => callback() });

    return { events, changes, navigation, controls };
}

test('a server validation error opens and marks the corresponding language', () => {
    const page = browser(['en_title']);
    assert.deepEqual(page.changes, [1]);
    assert.equal(page.navigation.children[1].classList.contains('uk-text-danger'), true);
    assert.equal(page.navigation.children[0].classList.contains('uk-text-danger'), false);
});

test('the first erroneous language is selected and all erroneous tabs are marked', () => {
    const page = browser(['title', 'en_title']);
    assert.deepEqual(page.changes, [0]);
    assert.equal(page.navigation.children.every(tab => tab.classList.contains('uk-text-danger')), true);
});

test('a clean form keeps the default language and waits for DOM readiness', () => {
    const page = browser([], 'loading');
    assert.equal(page.events.invalid, undefined);
    page.events.DOMContentLoaded();
    assert.deepEqual(page.changes, []);
    assert.equal(typeof page.events.invalid, 'function');
});

test('native validation opens a hidden language and focuses its field', () => {
    const page = browser();
    let prevented = false;
    page.events.invalid({ target: page.controls[1], preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.deepEqual(page.changes, [1]);
    assert.equal(page.controls[1].focused, true);
});

test('native validation of a visible field preserves the browser behavior', () => {
    const page = browser();
    page.events.invalid({ target: page.controls[0], preventDefault() { assert.fail('Visible validation was intercepted'); } });
    assert.deepEqual(page.changes, []);
});
