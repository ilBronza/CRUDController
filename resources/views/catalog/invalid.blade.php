<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ __('crud::catalog.invalid_filters') }}</title></head>
<body>
    <h1>{{ __('crud::catalog.invalid_filters') }}</h1>
    <ul>@foreach($errors as $messages)@foreach($messages as $message)<li>{{ $message }}</li>@endforeach @endforeach</ul>
    <a href="{{ $path }}">{{ __('crud::catalog.reset') }}</a>
</body>
</html>
