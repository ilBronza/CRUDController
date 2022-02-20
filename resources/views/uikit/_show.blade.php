        <dl class="uk-column-1-4">
        @foreach($allowedFields as $field)
            <dt>{{ __('fields.' . $field) }}</dt>
            <dd>
                @if((is_object($modelInstance->{$field}))||(is_array($modelInstance->{$field})))
                    {{ json_encode($modelInstance->{$field}) }}
                @else
                    {{ $modelInstance->{$field} }}
                @endif
            </dd>
        @endforeach
        </dl>