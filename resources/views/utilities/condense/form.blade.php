@extends('uikittemplate::app')

@section('content')

<nav class="uk-navbar-container uk-margin-bottom" uk-navbar>
	<div class="uk-navbar-left">
		<ul class="uk-navbar-nav">
			@if($backToListUrl ?? null)
				<li><a href="{{ $backToListUrl }}">@lang('crud::crud.backToList')</a></li>
			@endif
		</ul>
	</div>
</nav>

<div class="uk-card uk-card-default uk-card-body">
	<h2 class="uk-card-title">@lang('crud::crud.condenseTitle')</h2>

	<p>@lang('crud::crud.condenseDescription')</p>

	<form method="POST" action="{{ $storeCondenseUrl }}">
		@csrf

		@foreach($ids as $id)
			<input type="hidden" name="ids[]" value="{{ $id }}">
		@endforeach

		<div class="uk-margin">
			<label class="uk-form-label">@lang('crud::crud.condenseTargetLabel')</label>

			<div class="uk-margin-small">
				@foreach($models as $model)
					<label class="uk-display-block uk-margin-small">
						<input
							class="uk-radio"
							type="radio"
							name="master_id"
							value="{{ $model->getKey() }}"
							@if($loop->first) checked @endif
						>
						{{ $model->getCondenseName() }}

						@if(($relationships ?? []) && $model instanceof \IlBronza\CRUD\Interfaces\CondensableModelInterface)
							<span class="uk-text-muted uk-text-small">
								@foreach($relationships as $relationship)
									@if(isset($model->{$relationship . '_count'}))
										&mdash; {{ $model->{$relationship . '_count'} }} {{ $relationship }}
									@endif
								@endforeach
							</span>
						@endif
					</label>
				@endforeach
			</div>
		</div>

		<button type="submit" class="uk-button uk-button-primary">
			@lang('crud::crud.condenseSubmit')
		</button>
	</form>
</div>

@endsection
