<fieldset class="uk-fieldset uk-margin">
    <legend @class(['uk-form-label', 'uk-hidden-visually' => $inAccordion ?? false])>{{ $control['label'] }}</legend>
    <div class="crud-catalog-range">
        @foreach(['min', 'max'] as $bound)
            <div>
                <label class="uk-form-label" for="{{ $inputId }}-{{ $bound }}">{{ __('crud::catalog.' . $bound) }}</label>
                <input class="uk-input" type="number" step="{{ $control['step'] ?? 'any' }}" id="{{ $inputId }}-{{ $bound }}"
                    name="filters[{{ $control['key'] }}][{{ $bound }}]" value="{{ $value[$bound] ?? '' }}"
                    @if(isset($control['min'])) min="{{ $control['min'] }}" @endif
                    @if(isset($control['max'])) max="{{ $control['max'] }}" @endif>
            </div>
        @endforeach
    </div>
</fieldset>
