<style>
    .crud-catalog { --crud-catalog-sidebar-width: 16rem; --crud-catalog-gap: 1.5rem; --crud-catalog-filter-height: 18rem; }
    .crud-catalog [hidden] { display: none !important; }
    .crud-catalog-layout { display: grid; grid-template-columns: var(--crud-catalog-sidebar-width) minmax(0, 1fr); gap: var(--crud-catalog-gap); align-items: start; }
    .crud-catalog-sidebar { padding: 1.25rem; border: 1px solid #e5e5e5; border-radius: .25rem; }
    .crud-catalog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 15rem), 1fr)); gap: var(--crud-catalog-gap); }
    .crud-catalog-card { min-width: 0; overflow: hidden; overflow-wrap: anywhere; }
    .crud-catalog-image { width: 100%; aspect-ratio: 16 / 10; object-fit: cover; }
    .crud-catalog-empty { grid-column: 1 / -1; }
    .crud-catalog-range { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .crud-catalog-range > div { min-width: 0; }
    .crud-catalog-active, .crud-catalog-badges { display: flex; flex-wrap: wrap; gap: .5rem; padding: 0; list-style: none; }
    .crud-catalog-active > li, .crud-catalog-badges > li { margin: 0 !important; }
    .crud-catalog-active a { text-transform: none; }
    .crud-catalog .crud-catalog-filter-title { display: flex; align-items: center; gap: .5rem; cursor: pointer; list-style: none; }
    .crud-catalog-filter-title::-webkit-details-marker { display: none; }
    .crud-catalog-filter-title::after { content: ''; width: .4rem; height: .4rem; margin-left: auto; border-right: 1.5px solid currentColor; border-bottom: 1.5px solid currentColor; transform: rotate(45deg); flex-shrink: 0; }
    .crud-catalog-filter[open] > .crud-catalog-filter-title::after { transform: rotate(225deg); }
    .crud-catalog-filter-title:focus-visible { outline: 2px solid currentColor; outline-offset: 3px; }
    .crud-catalog-filter-content { padding-top: .5rem; max-height: var(--crud-catalog-filter-height); overflow-y: auto; }
    .crud-catalog-filter-content > .uk-margin:first-child { margin-top: 0 !important; }
    .crud-catalog-filter-content > .uk-margin:last-child { margin-bottom: 0 !important; }
    .crud-catalog-options { max-height: 18rem; overflow-y: auto; }
    .crud-catalog-filter[data-filter-expanded="full-open"] > .crud-catalog-filter-content,
    .crud-catalog-filter-content .crud-catalog-options { max-height: none; overflow-y: visible; }
    .crud-catalog-options label { padding: .25rem 0; }
    .crud-catalog[aria-busy="true"] .crud-catalog-grid { opacity: .55; }
    .crud-catalog-toggle { display: none; }
    @media (max-width: 959px) {
        .crud-catalog-layout { grid-template-columns: minmax(0, 1fr); }
        .crud-catalog-toggle { display: block; width: 100%; }
        .crud-catalog[data-enhanced="true"]:not([data-filters-open="true"]) .crud-catalog-filter-panel { display: none; }
    }
</style>
