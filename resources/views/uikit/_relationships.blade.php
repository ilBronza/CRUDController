DEPRECATO A MANETTA

@if(isset($relationships) && count($relationships))
<div class="uk-card-footer">

    @foreach($relationships as $name)
        @if(isset($relationshipsTableNames[$name]))

            <h3>Correlato: @indexLink($name)</h3>

            {!! $relationshipsTableNames[$name]->renderPortion(); !!}

        @elseif(! is_null($_item = $relationshipsElements[$name] ?? null))

            <h3>@indexLink($name) :
                <a href="{{ $_item->getShowUrl() }}">{{ $_item->getName() }}</a>
            </h3>

        @endif
    @endforeach

</div>
@endif


DEPRECATO A MANETTA



@if(isset($relationshipManager))
<div class="uk-card-footer">

@foreach($relationshipManager->getReationships() as $relationship)

    <div class="uk-card uk-card-small">
        <div class="uk-card-header">
            <span class="uk-h3">
                {!! $relationship->getCardTitle() !!}
            </span>
        </div>
        <div class="uk-card-body">
            {!! $relationship->render() !!}            
        </div>
    </div>
@endforeach

</div>

@endif