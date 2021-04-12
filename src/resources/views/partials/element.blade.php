<div class="uk-margin" id="element_{{ $element->id }}">
	<div class="uk-card uk-card-default uk-card-body uk-card-small">
	    <span class="uk-sortable-handle uk-margin-small-right" uk-icon="icon: table"></span> {{ $element->getName() }}
	</div>
@if ($element->children->count() > 0)
    <div class="group" uk-sortable="handle: .uk-sortable-handle; nestable: true; animation: 150; nestable-container-class: group">
    @foreach($element->children as $_element)
        @include('crud::partials.element', ['element'=>$_element])
    @endforeach
    </div>
@endif
</div>