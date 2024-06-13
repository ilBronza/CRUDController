<script type="text/javascript">

UIkit.util.ready(function () {
    // add all URL parts you need to this array in the same order as the switcher items are
    var switcherItems = [
        @foreach($relationshipManager->getRelationships() as $relationship)
            '{{ Str::slug($relationship->getCardTitle()) }}',
        @endforeach
    ];

    var itemIndex = switcherItems.indexOf(window.location.href.split('#')[1]);

    if (itemIndex > 0)
    {
        UIkit.switcher('#relationswitcher{{ $relationshipManager->getModel()->getKey() }}').show(itemIndex);
    }
});
</script>

<div class="uk-card-footer">

    <ul id="relationswitcher{{ $relationshipManager->getModel()->getKey() }}" class="relationships-list uk-subnav uk-subnav-pill" uk-switcher>
    @foreach($relationshipManager->getRelationships() as $relationship)
        <li><a href="#">{!! $relationship->getCardTitle() !!}</a></li>
    @endforeach
    </ul>

<ul class="uk-switcher uk-margin">
    @foreach($relationshipManager->getRelationships() as $relationship)
    <li>
        <div class="uk-card uk-card-small">
            <div class="uk-card-body">
                {!! $relationship->render() !!}
            </div>
        </div>
    </li>
    @endforeach
</ul>

</div>