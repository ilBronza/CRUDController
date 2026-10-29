@php
    $accordionState = $control['accordion'] ?? 'open';
    $inAccordion = $accordionState !== false;
    $accordionState = in_array($accordionState, ['open', 'full-open', 'closed'], true) ? $accordionState : 'open';
    $expandedState = $accordionState === 'open' ? 'open' : 'full-open';
@endphp

@if($inAccordion)
    <details class="crud-catalog-filter uk-margin" data-catalog-filter="{{ $control['key'] }}"
        data-filter-state="{{ $accordionState }}" data-filter-expanded="{{ $expandedState }}"
        @if($accordionState !== 'closed') open @endif>
        <summary id="{{ $inputId }}-heading" class="crud-catalog-filter-title uk-form-label" data-catalog-filter-title
            aria-controls="{{ $inputId }}-content">{{ $control['label'] }}</summary>
        <div id="{{ $inputId }}-content" class="crud-catalog-filter-content">
            @include($control['view'])
        </div>
        <button id="{{ $inputId }}-expand" type="button" class="uk-button uk-button-text uk-margin-small-top"
            data-catalog-filter-expand aria-controls="{{ $inputId }}-content"
            data-expand-label="{{ __('crud::catalog.show_all_options') }}"
            data-collapse-label="{{ __('crud::catalog.collapse_filter') }}" hidden>
            {{ __('crud::catalog.' . ($expandedState === 'full-open' ? 'collapse_filter' : 'show_all_options')) }}
        </button>
    </details>
@else
    @include($control['view'])
@endif
