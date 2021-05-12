@foreach($extraViews[$position] ?? [] as $view => $parameters)
    @include($view, $parameters)
@endforeach