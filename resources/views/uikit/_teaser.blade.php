<div class="uk-card uk-card-default uk-card-small">
    <div class="uk-card-header">
        <span class="uk-h3">
            @if($indexUrl = $modelInstance->getIndexUrl())
            <a href="{{ $indexUrl }}">
                <i class="fa-solid fa-list"></i>
                Torna alla lista @lang('crudModels.' . Str::plural(strtolower(class_basename($modelInstance))))
            </a> - 
            @endif
            <a href="{{ $modelInstance->getShowUrl() }}">{{ $modelInstance->getName() }}</a>
        </span>
    </div>

    <div uk-grid uk-height-match class="uk-grid-divider">

        @foreach($fieldsets as $fieldset)
            {!! $fieldset->renderShow() !!}
        @endforeach
    </div>

</div>