<div class="uk-card-footer">

@foreach($relationshipManager->getRelationships() as $relationship)
    <div  class="uk-margin" >
        <button class="uk-button uk-button-default" type="button" uk-toggle="target: #{!! $relationship->getToggleId() !!}">{!! $relationship->getCardTitle() !!}</button>
        <div id="{!! $relationship->getToggleId() !!}" hidden>
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
        </div>
    </div>    
@endforeach

</div>