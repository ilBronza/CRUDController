<link rel="stylesheet" type="text/css" href="/css/nestable.css?v={{ config('uikittemplate.version', "1.0.0") }}"/>
<script src="/js/ilbronza.crud.nestable.min.js"></script>

<style type="text/css">
.dd-item > button {
    margin-left: 0px;
}

.dd-content
{
    padding: 5px 10px 5px 5px;
}
</style>

<div class="uk-card uk-card-default uk-card-default">
    <div class="uk-card-header">@lang('crud::crud.viewElementsTree')</div>
    <div class="uk-card-header">
        <menu id="nestable-menu">
            <button class="uk-button uk-button-primary" data-action="expand-all">@lang('crud::nestable.expandAll')</button>
            <button class="uk-button uk-button-secondary" data-action="collapse-all">@lang('crud::nestable.collapseExpandAll')</button>

            @if($parentUrl)
            <a class="uk-button uk-button-danger" href="{{ $parentUrl }}">@lang('crud::nestable.backToParentElement', ['elementName' => $modelInstance->parent->getName()])</a>
            @endif

            @if($rootUrl)
            <a class="uk-button uk-button-danger" href="{{ $rootUrl }}">@lang('crud::nestable.backToRootElement')</a>
            @endif
        </menu>
    </div>
        @if ($elements->count() > 0)
            {{-- <div class="dd dd-item" data-id="{{ ($modelInstance)? $modelInstance->getKey() : 0 }}"> --}}
            <div class="dd pointer-handler" id="nestablelist" data-key="{{ ($modelInstance)? $modelInstance->getKey() : 0 }}" data-id="{{ ($modelInstance)? $modelInstance->getNestableKey() : 0 }}">
                <ol class="dd-list">
                @foreach ($elements as $element)
                    @include($nestableElementViewName, compact('element'))
                @endforeach
                </ol>
            </div>
        @else
            <div class="uk-alert-primary uk-alert">
                @lang('crud::nestableNoItemsPresent')
            </div>
        @endif        
    <div class="uk-card-footer"></div>
</div>

<script>

$(document).ready(function()
{
    $('#nestablelist').nestable({
        maxDepth : 99,
    });
});
</script>


<script type="text/javascript">

    $(document).ready(function() {

        $('#nestable-menu').on('click', function(e) {
            var target = $(e.target),
                action = target.data('action');
            if(action === 'expand-all') {
                $('.dd').nestable('expandAll');
            }
            if(action === 'collapse-all') {
                $('.dd').nestable('collapseAll');
            }
        });
    });

</script>