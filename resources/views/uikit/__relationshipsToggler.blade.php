<div class="uk-card-footer">

@foreach($relationshipManager->getSections() as $section)
    <div  class="uk-margin" >
        <button class="uk-button uk-button-default" type="button" uk-toggle="target: #{{ $section->getToggleId() }}">{!! $section->getCardTitle() !!}</button>
        <div id="{{ $section->getToggleId() }}" hidden>
            <div class="uk-card uk-card-small">
                <div class="uk-card-header">
                    <span class="uk-h3">
                        {!! $section->getCardTitle() !!}
                    </span>
                </div>
                <div class="uk-card-body">
                    {!! $section->render() !!}
                </div>
            </div>
        </div>
    </div>    
@endforeach

</div>
