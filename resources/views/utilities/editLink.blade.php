@if(($editUrl = ($editModelInstanceUrl ?? $element->getEditURL()))&&($element->userCanUpdate(Auth::user())&&(((! isset($canEditModelInstance))||($canEditModelInstance)))))
    <div class="uk-width-auto">
        <a href="{{ $editUrl }}">
        	@lang('crud::crud.editElement', ['element' => $element->getName()])

            {!! FaIcon::edit() !!}
        </a>
    </div>
@endif
