<div class="uk-card-footer relationships {{ strtolower($relationshipManager->getModel()->getMorphClass()) }}">

	<ul id="relationswitcher{{ $relationshipManager->getModel()->getKey() }}"
		class="relationships-list uk-subnav uk-subnav-pill" uk-switcher>
		@foreach($relationshipManager->getSections() as $section)
			<li class="{{ Str::slug($section->getName()) }}"><a class="uk-button uk-button-small uk-button-default"
				   href="#">{!! $section->getCardTitle() !!}</a></li>
		@endforeach
	</ul>

	<ul class="uk-switcher uk-margin">
		@foreach($relationshipManager->getSections() as $section)
			<li class="{{ Str::slug($section->getName()) }}">
				<div class="uk-card uk-card-small">
					<div class="uk-card-body">
						{!! $section->render() !!}
					</div>
				</div>
			</li>
		@endforeach
	</ul>

</div>

<script type="text/javascript">

	window.strSanitize = function(string)
    {
        return string.toLowerCase().replace(/[^a-zA-Z]/g, "");
    }

    $('body').on('click', '#relationswitcher{{ $relationshipManager->getModel()->getKey() }} li a', function ()
    {
        var hash = window.strSanitize($(this).text());

        window.location.hash = hash;
    })


    UIkit.util.ready(function ()
    {
        var liveSwithcerItems = [];

        $("#relationswitcher{{ $relationshipManager->getModel()->getKey() }} li a").each(function()
        {
            const str = $(this).text();

            liveSwithcerItems.push(
                window.strSanitize(str)
			);
		});

        var itemIndex = liveSwithcerItems.indexOf(window.location.href.split('#')[1]);

        if (itemIndex > 0)
        {
            UIkit.switcher('#relationswitcher{{ $relationshipManager->getModel()->getKey() }}').show(itemIndex);
        }
    });
</script>
