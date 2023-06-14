<div class="uk-card-footer">

    <ul class="uk-subnav uk-subnav-pill" uk-switcher>
    @foreach($relationshipManager->getRelationships() as $relationship)
        <li><a href="#">{!! $relationship->getCardTitle() !!}</a></li>
    @endforeach
    </ul>

<ul class="uk-switcher uk-margin">
    @foreach($relationshipManager->getRelationships() as $relationship)
    <li>
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
    </li>
    @endforeach
</ul>

</div>