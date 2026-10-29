@foreach($model->getMetaTags($locale ?? null) as $name => $value)
    @if($name === 'title')
        <title>{{ $value }}</title>
    @elseif($name === 'canonical')
        <link rel="canonical" href="{{ $value }}">
    @elseif(strpos($name, 'og:') === 0)
        <meta property="{{ $name }}" content="{{ $value }}">
    @else
        <meta name="{{ $name }}" content="{{ $value }}">
    @endif
@endforeach
