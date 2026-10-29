<fieldset class="uk-fieldset">
    <legend class="uk-legend">SEO</legend>
    @foreach($fields as $name => $parameters)
        <div class="uk-margin">
            <label class="uk-form-label">{{ $name }}
                @if($parameters['type'] === 'textarea')
                    <textarea class="uk-textarea" name="seo[{{ $name }}]">{{ old('seo.'.$name, $seo?->getAttribute($name) ?? ($name === 'locale' ? $locale : '')) }}</textarea>
                @else
                    <input class="uk-input" type="text" name="seo[{{ $name }}]" value="{{ old('seo.'.$name, $seo?->getAttribute($name) ?? ($name === 'locale' ? $locale : '')) }}" @if($name === 'locale') required @endif>
                @endif
            </label>
            @error('seo.'.$name)<div class="uk-text-danger">{{ $message }}</div>@enderror
        </div>
    @endforeach
</fieldset>
