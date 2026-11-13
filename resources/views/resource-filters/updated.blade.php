<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><title>{{ __('crud::resource-filters.applied') }}</title></head>
<body>
<p>{{ __('crud::resource-filters.applied') }}</p>
@if($resource)
    <a href="{{ route('crud.resourceFilters.edit', $resource) }}">{{ __('crud::resource-filters.edit') }}</a>
@endif
<script>
    if (window.parent !== window) window.parent.location.reload();
</script>
</body>
</html>
