@props(['catalog', 'id' => 'crud-catalog'])

<section id="{{ $id }}" {{ $attributes->class(['crud-catalog']) }} data-crud-catalog aria-busy="false"
    data-error-message="{{ __('crud::catalog.load_error') }}">
    <div class="crud-catalog-layout">
        <aside class="crud-catalog-sidebar" aria-label="{{ __('crud::catalog.filters') }}">
            <button type="button" class="uk-button uk-button-default crud-catalog-toggle" data-catalog-toggle
                aria-controls="{{ $id }}-filters" aria-expanded="false" hidden>
                {{ __('crud::catalog.filters') }}
            </button>
            <div id="{{ $id }}-filters" class="crud-catalog-filter-panel">
                <h2 class="uk-h4">{{ __('crud::catalog.filters') }}</h2>
                <form id="{{ $id }}-form" action="{{ $catalog->path }}" method="get" data-catalog-form class="uk-form-stacked">
                    @foreach($catalog->context as $key => $contextValue)
                        <input type="hidden" name="{{ $key }}" value="{{ is_bool($contextValue) ? (int) $contextValue : $contextValue }}">
                    @endforeach
                    @foreach($catalog->controls as $key => $control)
                        @include('crud::catalog.filters.accordion', ['control' => $control, 'value' => $catalog->values[$key] ?? null, 'inputId' => $id . '-' . $key])
                    @endforeach
                    <button type="submit" class="uk-button uk-button-primary uk-width-1-1">{{ __('crud::catalog.apply') }}</button>
                    <a href="{{ $catalog->resetUrl() }}" data-catalog-link class="uk-button uk-button-text uk-margin-small-top">{{ __('crud::catalog.reset') }}</a>
                </form>
            </div>
        </aside>
        <div class="crud-catalog-results">
            <div class="uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-margin-bottom">
                <p class="uk-margin-remove" data-catalog-total aria-live="polite" tabindex="-1">{{ trans_choice('crud::catalog.results', $catalog->results->total(), ['count' => $catalog->results->total()]) }}</p>
                <div>
                    <label class="uk-form-label" for="{{ $id }}-sort">{{ __('crud::catalog.sort') }}</label>
                    <select class="uk-select uk-form-width-medium" id="{{ $id }}-sort" name="sort" form="{{ $id }}-form" data-catalog-sort>
                        @foreach($catalog->sorts as $key => $sort)
                            <option value="{{ $key }}" @selected($catalog->sort === $key)>{{ $sort['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if($catalog->activeFilters)
                <ul class="uk-list crud-catalog-active" aria-label="{{ __('crud::catalog.active_filters') }}">
                    @foreach($catalog->activeFilters as $selection)
                        <li><a href="{{ $selection['url'] }}" data-catalog-link class="uk-button uk-button-default uk-button-small"
                            aria-label="{{ __('crud::catalog.remove', ['filter' => $selection['label']]) }}">{{ $selection['label'] }} <span aria-hidden="true">×</span></a></li>
                    @endforeach
                </ul>
            @endif
            <p data-catalog-loading role="status" hidden>{{ __('crud::catalog.loading') }}</p>
            <div data-catalog-error role="alert" class="uk-alert-danger uk-padding-small" hidden></div>
            <div class="crud-catalog-grid">
                @forelse($catalog->results as $item)
                    @include($catalog->cardView, ['item' => $item, 'catalog' => $catalog])
                @empty
                    <div class="crud-catalog-empty uk-placeholder uk-text-center">
                        <p>{{ __('crud::catalog.empty') }}</p>
                        @if($catalog->activeFilters)
                            <a href="{{ $catalog->resetUrl() }}" data-catalog-link>{{ __('crud::catalog.reset') }}</a>
                        @endif
                    </div>
                @endforelse
            </div>
            {{ $catalog->results->onEachSide(1)->links('crud::catalog.pagination') }}
        </div>
    </div>
</section>

@once
    @include('crud::catalog.styles')
    @include('crud::catalog.scripts')
@endonce
