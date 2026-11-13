<form method="post" class="uk-display-inline-block"
      action="{{ isset($resource) ? route('crud.resourceFilters.reset', $resource) : route('crud.resourceFilters.resetAll') }}">
    @csrf
    @method('DELETE')
    @if($iframed ?? false)
        <input type="hidden" name="iframed" value="1">
    @endif
    @if($iconOnly ?? false)
        <button type="submit" class="uk-icon-button uk-margin-small-right"
                aria-label="{{ __('crud::resource-filters.reset_all') }}"
                title="{{ __('crud::resource-filters.reset_all') }}">
            <span aria-hidden="true" class="uk-text-large">×</span>
        </button>
    @else
        <button type="submit" class="uk-button uk-button-default uk-button-small">
            {{ __('crud::resource-filters.' . (isset($resource) ? 'reset' : 'reset_all')) }}
        </button>
    @endif
</form>
