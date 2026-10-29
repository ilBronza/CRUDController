<div class="uk-margin">
    <label @class(['uk-form-label', 'uk-hidden-visually' => $inAccordion ?? false]) for="{{ $inputId }}">{{ $control['label'] }}</label>
    <input class="uk-input" type="search" id="{{ $inputId }}" name="filters[{{ $control['key'] }}]" value="{{ $value }}"
        maxlength="{{ $control['maxLength'] ?? 200 }}" placeholder="{{ $control['placeholder'] ?? '' }}">
</div>
