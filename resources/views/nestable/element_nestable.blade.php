<li class="dd-item" data-id="{{ $element->getNestableKey() }}" id="{{ $element->getNestableKey() }}">
	<div class="dd-handle"></div>
	<div class="dd-content">{{ $element->getNestableName() }}</div>

	@if (isset($element->childs) and $element->childs->count() > 0)
	<ol class="dd-list">
		@foreach($element->childs->sortBy('sorting_index') as $_element)
			@include('crud::nestable.element_nestable', ['element' => $_element])
		@endforeach
	</ol>
	@endif

</li>
