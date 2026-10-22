<div class="uk-margin">
    <label class="uk-form-label" for="{{ $inputId }}">{{ $control['label'] }}</label>
    <select class="uk-select" id="{{ $inputId }}" name="filters[{{ $control['key'] }}]">
        <option value="" @selected($value === null)>{{ __('crud::catalog.all') }}</option>
        <option value="1" @selected($value === true)>{{ __('crud::catalog.yes') }}</option>
        <option value="0" @selected($value === false)>{{ __('crud::catalog.no') }}</option>
    </select>
</div>
