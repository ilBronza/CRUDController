<div class="uk-card-footer relationships">

	@foreach($relationshipManager->getSections() as $section)

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
	@endforeach

</div>

@include('crud::uikit.__relationshipsTableSizing')
