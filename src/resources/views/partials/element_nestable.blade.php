<li class="dd-item" data-id="{{ $element->id }}">
    <div class="dd-handle">Drag</div>
    <div class="dd-content">{{ $element->getName() }} id:{{ $element->id }} sorting_index:{{ $element->sorting_index }}</div>
	@if (isset($element->childs) and $element->childs->count() > 0)
	    <ol class="dd-list">
	    	@foreach($element->childs as $_element)
		        @include('crud::partials.element_nestable', ['element'=>$_element])
		    @endforeach
	    </ol>
	@endif
</li>
