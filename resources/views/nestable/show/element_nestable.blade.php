<li
	class="dd-item"
	data-key="{{ $element->getKey() }}"
	data-id="{{ $element->getNestableKey() }}"
	id="{{ $element->getNestableKey() }}"
	>
	{{-- <div class="dd-handle"></div> --}}
	<div class="dd-content">
		<a href="{{ $element->getShowUrl() }}">
			{{ $element->getNestableName() }}
		</a>
	</div>

	@if (isset($element->childs) and $element->childs->count() > 0)
	<ol class="dd-list">
		@foreach($element->childs->sortBy('sorting_index') as $_element)
			@include($nestableElementViewName, ['element' => $_element])
		@endforeach
	</ol>
	@endif

</li>
