<div class="uk-card uk-card-default uk-card-small">
    <div class="uk-card-header"><span class="uk-h3">@indexLink($teaserModel): <a href="{{ $teaserModel->getShowUrl() }}">{{ $teaserModel->getName() }}</a></span></div>

    @if(count($teaserModelFields) > 0)
    <div class="uk-card-body">
        <dl class="uk-column-1-4">
        @foreach($teaserModelRelationships as $relation)
            <dt>@indexLink($relation)</dt>
            <dd><a href="{{ $teaserModel->{$relation}->getShowUrl() }}">{{ $teaserModel->{$relation}->getName() }}</a></dd>
        @endforeach
        @foreach($teaserModelFields as $field)
            <dt>{{ $field['name'] }}</dt>
            <dd>{{ json_encode($teaserModel->{$field['name']}) }}</dd>
        @endforeach
        </dl>
    </div>
    @endif
</div>