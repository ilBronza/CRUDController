<script>
(function () {
    'use strict';
    if (window.CrudCatalogFrontend) return;
    const catalogs = new WeakMap();
    const types = ['text', 'multiple', 'number-range', 'boolean'];
    const list = value => Array.isArray(value) ? value : [value];
    const present = value => value !== null && value !== undefined && value !== '';
    const numeric = value => /^[+-]?(?:\d+\.?\d*|\.\d+)(?:e[+-]?\d+)?$/i.test(String(value).trim()) && Number.isFinite(Number(value));

    function read(config, url) {
        const values = Object.create(null);
        for (const [name, value] of url.searchParams) {
            if (name === 'filters') throw new Error(config.messages.invalid);
            if (!name.startsWith('filters[') || value === '') continue;
            const match = /^filters\[([^\]]+)\](?:\[([^\]]*)\])?$/.exec(name);
            if (!match || !Object.hasOwn(config.filters, match[1])) throw new Error(config.messages.invalid);
            const [_, key, part] = match;
            const filter = config.filters[key];
            if (filter.type === 'multiple') {
                if (part === undefined || (part !== '' && !/^\d+$/.test(part))) throw new Error(config.messages.invalid);
                (values[key] ||= []).push(value);
            } else if (filter.type === 'number-range') {
                if (!['min', 'max'].includes(part)) throw new Error(config.messages.range);
                (values[key] ||= {})[part] = value;
            } else {
                if (part !== undefined) throw new Error(config.messages.invalid);
                values[key] = filter.type === 'text' ? value.trim() : value;
            }
        }
        for (const [key, value] of Object.entries(values)) {
            const filter = config.filters[key];
            if (filter.type === 'text') {
                if (value.length > (filter.maxLength ?? 200)) throw new Error(config.messages.invalid);
                if (!value) delete values[key];
            }
            if (filter.type === 'boolean' && !['0', '1'].includes(value)) throw new Error(config.messages.invalid);
            if (filter.type === 'multiple') {
                if (value.length > (filter.maxSelections ?? 100) || value.some(option => !Object.hasOwn(filter.options || {}, option)))
                    throw new Error(config.messages.invalid);
                values[key] = [...new Set(value)];
            }
            if (filter.type === 'number-range') {
                for (const number of Object.values(value)) {
                    if (!numeric(number) ||
                        (filter.min !== undefined && Number(number) < Number(filter.min)) ||
                        (filter.max !== undefined && Number(number) > Number(filter.max))) throw new Error(config.messages.range);
                }
                if (value.min !== undefined && value.max !== undefined && Number(value.min) > Number(value.max))
                    throw new Error(config.messages.range);
            }
        }
        const sort = url.searchParams.get('sort') ?? config.defaultSort;
        const rawPage = url.searchParams.get('page') ?? '1';
        if (!Object.hasOwn(config.sorts, sort) || !/^\d+$/.test(rawPage) || Number(rawPage) < 1 || Number(rawPage) > 1000000)
            throw new Error(config.messages.invalid);
        return { values, sort, page: Number(rawPage) };
    }

    function urlFor(root, config, state) {
        const url = new URL(root.querySelector('[data-catalog-form]').action, window.location.href);
        url.search = '';
        for (const [key, value] of Object.entries(config.context)) url.searchParams.set(key, String(typeof value === 'boolean' ? Number(value) : value));
        for (const [key, value] of Object.entries(state.values)) {
            const type = config.filters[key].type;
            if (type === 'multiple') value.forEach(option => url.searchParams.append(`filters[${key}][]`, option));
            else if (type === 'number-range') Object.entries(value).forEach(([bound, number]) => url.searchParams.set(`filters[${key}][${bound}]`, number));
            else url.searchParams.set(`filters[${key}]`, value);
        }
        url.searchParams.set('sort', state.sort);
        if (state.page > 1) url.searchParams.set('page', state.page);
        return url;
    }

    function matches(type, actual, selected) {
        if (type === 'text') return list(actual).some(value => present(value) && String(value).toLocaleLowerCase().includes(selected.toLocaleLowerCase()));
        if (type === 'multiple') return list(actual).some(value => present(value) && selected.includes(String(value)));
        if (type === 'boolean') return selected === '1' ? [true, 1, '1'].includes(actual) : [false, 0, '0'].includes(actual);
        if (type === 'number-range') return present(actual) && Number.isFinite(Number(actual)) &&
            (selected.min === undefined || Number(actual) >= Number(selected.min)) &&
            (selected.max === undefined || Number(actual) <= Number(selected.max));
        return false;
    }

    function compare(left, right, type) {
        if (left === right) return 0;
        if (left === null || left === undefined) return -1;
        if (right === null || right === undefined) return 1;
        if (type !== 'text' && numeric(left) && numeric(right))
            return Number(left) - Number(right);
        return String(left).localeCompare(String(right), undefined, { sensitivity: 'base' });
    }

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function link(root, config, state, text) {
        const node = element('a', '', text);
        node.href = urlFor(root, config, state).href;
        node.setAttribute('data-catalog-link', '');
        return node;
    }

    function syncForm(root, config, state) {
        for (const input of root.querySelector('[data-catalog-form]').elements) {
            if (input.name === 'sort') { input.value = state.sort; continue; }
            const match = /^filters\[([^\]]+)\](?:\[([^\]]*)\])?$/.exec(input.name || '');
            if (!match || !Object.hasOwn(config.filters, match[1])) continue;
            const value = state.values[match[1]];
            if (input.type === 'checkbox') input.checked = (value || []).includes(input.value);
            else if (input.type === 'radio') input.checked = value === input.value;
            else input.value = match[2] !== undefined ? (value?.[match[2]] ?? '') : (value ?? '');
        }
    }

    function selections(root, config, state) {
        const container = root.querySelector('[data-catalog-active]');
        container.replaceChildren();
        for (const [key, value] of Object.entries(state.values)) {
            const filter = config.filters[key];
            const choices = filter.type === 'multiple' ? value : [null];
            for (const choice of choices) {
                let label = String(value);
                if (filter.type === 'multiple') label = filter.options[choice];
                if (filter.type === 'boolean') label = value === '1' ? config.messages.yes : config.messages.no;
                if (filter.type === 'number-range') label = ['min', 'max'].filter(bound => value[bound] !== undefined)
                    .map(bound => config.messages[bound === 'min' ? 'from' : 'to'].replace(':value', value[bound])).join(' ');
                label = `${filter.label}: ${label}`;
                const values = { ...state.values };
                if (choice !== null && value.length > 1) values[key] = value.filter(option => option !== choice);
                else delete values[key];
                const anchor = link(root, config, { ...state, values, page: 1 }, `${label} ×`);
                anchor.className = 'uk-button uk-button-default uk-button-small';
                anchor.setAttribute('aria-label', config.messages.remove.replace(':filter', label));
                const item = element('li');
                item.append(anchor);
                container.append(item);
            }
        }
        container.hidden = Object.keys(state.values).length === 0;
        root.querySelectorAll('[data-catalog-reset]').forEach(anchor => {
            anchor.href = urlFor(root, config, { ...state, values: {}, page: 1 }).href;
        });
    }

    function pagination(root, config, state, pages) {
        const container = root.querySelector('[data-catalog-pagination]');
        container.replaceChildren();
        if (pages < 2) return;
        const nav = element('nav', 'uk-margin-top');
        nav.setAttribute('aria-label', config.messages.pagination);
        const items = element('ul', 'uk-pagination uk-flex-center uk-flex-wrap');
        const add = (page, label, disabled, current = false) => {
            const item = element('li', current ? 'uk-active' : disabled ? 'uk-disabled' : '');
            const node = disabled || current ? element('span', '', label) : link(root, config, { ...state, page }, label);
            if (current) node.setAttribute('aria-current', 'page');
            else if (!disabled) node.setAttribute('aria-label', config.messages.page.replace(':page', page));
            item.append(node);
            items.append(item);
        };
        add(state.page - 1, config.messages.previous, state.page === 1);
        const visible = [...new Set([1, state.page - 1, state.page, state.page + 1, pages])]
            .filter(page => page >= 1 && page <= pages).sort((a, b) => a - b);
        let previous = 0;
        for (const page of visible) {
            if (previous && page - previous > 1) add(0, '…', true);
            add(page, String(page), false, page === state.page);
            previous = page;
        }
        add(state.page + 1, config.messages.next, state.page === pages);
        nav.append(items);
        container.append(nav);
    }

    function load(root, target, push) {
        const { config, nodes } = catalogs.get(root);
        const error = root.querySelector('[data-catalog-error]');
        const focused = document.activeElement;
        try {
            const state = read(config, target);
            const matching = config.items.map((data, index) => ({ data, index }))
                .filter(({ data }) => Object.entries(state.values).every(([key, value]) => matches(config.filters[key].type, data.filters[key], value)));
            const sort = config.sorts[state.sort];
            matching.sort((a, b) => compare(a.data.sorts[state.sort], b.data.sorts[state.sort], sort.type) * (sort.direction === 'desc' ? -1 : 1) || a.index - b.index);
            const pages = Math.max(1, Math.ceil(matching.length / config.perPage));
            state.page = Math.min(state.page, pages);
            const grid = root.querySelector('.crud-catalog-grid');
            const visible = matching.slice((state.page - 1) * config.perPage, state.page * config.perPage);
            grid.replaceChildren(...visible.map(({ index }) => nodes[index]));
            if (!matching.length) grid.append(element('div', 'crud-catalog-empty uk-placeholder uk-text-center', config.messages.empty));
            const total = root.querySelector('[data-catalog-total]');
            total.textContent = (matching.length === 0 ? config.messages.zero : matching.length === 1 ? config.messages.one : config.messages.many.replace(':count', matching.length));
            syncForm(root, config, state);
            selections(root, config, state);
            pagination(root, config, state, pages);
            error.hidden = true;
            const url = urlFor(root, config, state);
            if (push && url.href !== window.location.href) window.history.pushState(null, '', url.href);
            else if (!push && Number(target.searchParams.get('page') || 1) !== state.page) window.history.replaceState(null, '', url.href);
            if (focused && !focused.isConnected) total.focus({ preventScroll: true });
            if (window.UIkit) window.UIkit.update(root);
        } catch (exception) {
            error.textContent = exception.message || config.messages.invalid;
            error.hidden = false;
        }
    }

    window.CrudCatalogFrontend = {
        initialize(root) {
            if (catalogs.has(root)) return;
            const config = JSON.parse(root.querySelector('[data-catalog-frontend]').textContent);
            if (Object.values(config.filters).some(filter => !types.includes(filter.type))) throw new Error(config.messages.invalid);
            const nodes = Array.from(root.querySelectorAll('template[data-catalog-frontend-item]'), template => template.content.firstElementChild);
            catalogs.set(root, { config, nodes });
            load(root, new URL(window.location.href), false);
        },
        load,
    };
})();
</script>
