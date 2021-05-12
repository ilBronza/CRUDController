
<h3>{{ $parentModel->getName() }}</h3>
<dl>
@foreach($parentModel->getAttributes() as $name => $field)
    <dt>{{ __('fields.' . $name) }}</dt>
    <dd>
        @if((is_object($parentModel->{$name}))||(is_array($parentModel->{$name})))
            {{ json_encode($parentModel->{$name}) }}
        @else
            {{ $parentModel->{$name} }}
        @endif
    </dd>
@endforeach
</dl>