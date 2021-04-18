<li class="dd-item" data-id="{{ $element->getKey() }}">

    <a class="dd-handle" uk-icon="icon: table"></a>
    <div class="dd-content">{{ $element->getName() }}</div>

	@if (isset($element->childs) and $element->childs->count() > 0)
	    <ol class="dd-list">
	    	@foreach($element->childs as $_element)
		        @include('crud::nestable.element_nestable', ['element'=>$_element])
		    @endforeach
	    </ol>
	@endif

</li>
