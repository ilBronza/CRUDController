<link rel="stylesheet" type="text/css" href="/css/nestable.css?v={{ config('uikittemplate.version', "1.0.0") }}"/>
<script src="/js/ilbronza.crud.nestable.min.js"></script>

<div class="uk-card uk-card-default uk-card-default">
    <div class="uk-card-header">@lang('crud::sortElements')</div>
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
    {{-- <div class="uk-card-body" id="nestablelist"> --}}
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
    {{-- </div> --}}
    <div class="uk-card-footer"></div>
</div>

<script>

$(document).ready(function()
{
    $('#nestablelist').nestable({
        maxDepth : 99,
    }).on('dragEnd', function(
        e,
        item,               // List item
        sourceList,         // Source list
        destinationList,    // Destination list
        position            // Position        
        )
    {
        var element_id = $(item).data('id');
        var element = $('#' + element_id);
        var parent = $(element).parents('.dd-item');
        var parent_id = $(parent).data('id');

        setTimeout(function()
        {
            var childrens = element.parent().children();

            var siblings = [];

            if( childrens != null){
                childrens.each(function( index )
                {
                    siblings.push($( this ).data('id'));
                });
            }

            $.ajax({
                url : '{{ $action }}',
                data : {
                    element_id: element_id,
                    parent_id: parent_id,
                    siblings: JSON.stringify(siblings)
                },
                type : 'POST',
                success : function(response, message, jqXhr)
                {
                    if(response.success == true)
                        window.addSuccessNotification(element_id + " {{ __('crud::nestableElementMovedTo') }} " + parent_id);
                    else
                        this.error(response, message, jqXhr);

                },
                error : function(response, message, xhr)
                {
                    alert(response.responseText);
                    window.location.reload();
                }
            });
        }, 1000);
    });
});
</script>


<script type="text/javascript">

    $(document).ready(function() {

        $('body').on('mouseenter', '.dd-content', function()
        {
            let elementKey = $(this).parent('.dd-item').data('key');
            // let elementText = $(this).text();

            // $(this).append('<a class="sortby uk-align-right" href="' + '{{ $reorderByUrl }}'.replace('%s', elementKey) + '">@lang('crud::nestable.reorderBy') ' + elementText + '</a>');
            $(this).append('<a class="sortby uk-align-right" href="' + '{{ $reorderByUrl }}'.replace('%s', elementKey) + '">@lang('crud::nestable.reorderBy')</a>');

            @if($replaceElementUrl)
            // $(this).append('<a class="replaceelement uk-align-right" href="' + '{{ $replaceElementUrl }}'.replace('%s', elementKey) + '">@lang('crud::nestable.replaceElement') ' + elementText + '</a>');
            $(this).append('<a class="replaceelement uk-align-right" href="' + '{{ $replaceElementUrl }}'.replace('%s', elementKey) + '">@lang('crud::nestable.replaceElement')</a>');
            @endif

            @if($editUrl)
            // $(this).append('<a class="editelement uk-align-right" href="' + '{{ $editUrl }}'.replace('%s', elementKey) + '">@lang('crud::nestable.editElement') ' + elementText + '</a>');
            $(this).append('<a class="editelement uk-align-right" href="' + '{{ $editUrl }}'.replace('%s', elementKey) + '">@lang('crud::nestable.editElement')</a>');
            @else
            $(this).append('<a class="editelement uk-align-right" href="' + $(this).data('editurl') + '">@lang('crud::nestable.editElement')</a>');
            @endif

            @if($createChildUrl)
            // $(this).append('<a class="createchild uk-align-right" href="' + '{{ $createChildUrl }}'.replace('%s', elementKey) + '">@lang('crud::nestable.createChild') ' + elementText + '</a>');
            $(this).append('<a class="createchild uk-align-right" href="' + '{{ $createChildUrl }}'.replace('%s', elementKey) + '">@lang('crud::nestable.createChild')</a>');
            @endif
        });

        $('body').on('mouseleave', '.dd-content', function()
        {
            $(this).find('a.sortby').remove();
            $(this).find('a.createchild').remove();
            $(this).find('a.replaceelement').remove();
            $(this).find('a.editelement').remove();

        });

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

        let dump = function (obj) {
            console.log( JSON.stringify(obj, null, 2) );
        }


        // $('.dd').nestable({

        //     maxDepth: {{ $maxDepth }},

        //     callback: function(l,e){
        //         // l is the main container
        //         // e is the element that was moved
        //         var id = e.data('id'), parent = e.parent().closest('.dd-item');
        //         var parent_id = parent.data('id');
        //         let parentText = parent.children('.dd-content').text();

        //         var childrens = parent.find('ol.dd-list').first().children('li.dd-item');
        //         // var order = JSON.stringify($('.dd').nestable('serialize'));
        //         // console.log( l );
        //         var siblings = [];
        //         if( childrens != null){
        //             childrens.each(function( index ) {
        //                 siblings.push($( this ).data('id'));
        //                 // console.log( index + ": " + $( this ).data('id') );
        //             });
        //         }

        //         $.post('{{ $action }}', {
        //             element_id: id,
        //             parent_id: parent_id,
        //             siblings: JSON.stringify(siblings),
        //         }, function (data) {
        //             window.addSuccessNotification(id+ "{{ __('crud::nestableElementMovedTo') }} " + parentText);
        //         });
    

        //         // console.log( order );
        //         // UIkit.notification(id+' was moved to '+ parent_id, 'success');
        //     }
        // });

    });

</script>