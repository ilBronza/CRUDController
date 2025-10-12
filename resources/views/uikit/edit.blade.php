@extends('uikittemplate::app')

@section('content')

@include('uikittemplate::utilities.__extraViews', ['position' => 'top'])

@if(isset($buttons))
	<nav class="uk-navbar-container" uk-navbar>
		<div class="uk-navbar-left">
			<ul class="uk-navbar-nav">
				@isset($backToListUrl)
				<li><a href="{{ $backToListUrl }}">@lang('crud::crud.backToList')</a></li>
				@endisset

				@foreach($buttons as $button)
					@if($button)
						<li>{!! $button->renderLink() !!}</li>
					@endif
				@endforeach
			</ul>
		</div>
	</nav>
@endif

@include('form::uikit._form')

@include('crud::uikit._relationships')

@include('uikittemplate::utilities.__extraViews', ['position' => 'bottom'])

@endsection