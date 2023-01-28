@if(isset($fieldsets))

<div uk-grid uk-height-match class="uk-grid-divider">

    @foreach($fieldsets as $fieldset)
        {!! $fieldset->renderShow() !!}
    @endforeach
</div>

@elseif(isset($allowedFields))
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
@endif
