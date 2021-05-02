<div class="uk-card uk-card-default uk-card-small">
    <div class="uk-card-header"><span class="uk-h3">@indexLink($parentModel): <a href="{{ $parentModel->getShowUrl() }}">{{ $parentModel->getName() }}</a></span></div>

    <div class="uk-card-body">
        <dl class="uk-column-1-4">
        @foreach($parentModelTeaserAttributes as $attribute => $value)
            <dt>{{ __('fields.' . $attribute) }}</dt>
            <dd>{{ $value }}</dd>
        @endforeach
        </dl>
    </div>
</div>