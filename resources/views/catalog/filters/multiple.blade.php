<fieldset class="uk-fieldset uk-margin">
    <legend class="uk-form-label">{{ $control['label'] }}</legend>
    <div class="crud-catalog-options">
        @forelse($control['options'] as $option => $label)
            <label class="uk-display-block" for="{{ $inputId }}-{{ $loop->index }}">
                <input class="uk-checkbox" type="checkbox" id="{{ $inputId }}-{{ $loop->index }}"
                    name="filters[{{ $control['key'] }}][]" value="{{ $option }}" @checked(in_array((string) $option, $value ?? [], true))> {{ $label }}
            </label>
        @empty
            <p class="uk-text-muted uk-margin-remove">{{ __('crud::catalog.no_options') }}</p>
        @endforelse
    </div>
</fieldset>
