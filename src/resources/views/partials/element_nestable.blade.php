<li class="dd-item" data-id="{{ $element->id }}">
    <div class="dd-handle">Drag</div>
    <div class="dd-content">{{ $element->getName() }} id:{{ $element->id }} sorting_index:{{ $element->sorting_index }}</div>
	@if ($element->children->count() > 0)
	    <ol class="dd-list">
	    	@foreach($element->children as $_element)
		        @include('crud::partials.element_nestable', ['element'=>$_element])
		    @endforeach
	    </ol>
	@endif
</li>
