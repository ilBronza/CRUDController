@extends('uikittemplate::app')

@section('content')

	<div class="uk-card uk-card-default uk-card-body uk-margin-bottom">
		<div class="uk-card-header">
			<div uk-grid>
				@if($modelInstance ?? false)
				<div class="uk-expand">
					<h3 class="uk-h3">
						<a href="{{ $modelInstance->getEditUrl() }}">{!! FaIcon::edit()  !!} </a>
						{{ $modelInstance->getName() }}
					</h3>
				</div>
				@endif
				@if($buttons ?? false)
				<div class="uk-auto">
					@foreach($buttons as $button)
						{!! $button->render() !!}
					@endforeach
				</div>
				@endif
			</div>
		</div>
		<div class="uk-card-body">
			@include('crud::timeline._timeline')
		</div>
	</div>

@endsection