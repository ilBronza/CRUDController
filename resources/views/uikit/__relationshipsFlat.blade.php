<div class="uk-card-footer">

@foreach($relationshipManager->getRelationships() as $relationship)

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