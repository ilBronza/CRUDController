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
    <div class="uk-card-body">
        @if ($elements->count() > 0)
            <div class="dd dd-item" data-id="{{ ($modelInstance)? $modelInstance->getKey() : 0 }}">
                <ol class="dd-list">
                @foreach ($elements as $element)
                    @include('crud::nestable.element_nestable', compact('element'))
                @endforeach
                </ol>
            </div>
        @else
            <div class="uk-alert-primary uk-alert">
                @lang('crud::nestableNoItemsPresent')
            </div>
        @endif        
    </div>
    <div class="uk-card-footer"></div>
</div>


<script type="text/javascript">
    $(document).ready(function() {

        $('body').on('mouseenter', '.dd-content', function()
        {
            let elementId = $(this).parent('.dd-item').data('id');
            let elementText = $(this).text();

            $(this).append('<a class="createchild uk-align-right" href="' + '{{ $reorderByUrl }}'.replace('%s', elementId) + '">@lang('crud::nestable.reorderBy') ' + elementText + '</a>');

            @if($createChildUrl)
            $(this).append('<a class="sortby uk-align-right" href="' + '{{ $createChildUrl }}'.replace('%s', elementId) + '">@lang('crud::nestable.createChild') ' + elementText + '</a>');
            @endif
        });

        $('body').on('mouseleave', '.dd-content', function()
        {
            $(this).find('a.sortby').remove();
            $(this).find('a.createchild').remove();
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


        $('.dd').nestable({
            /* config options */
            maxDepth: {{ $maxDepth }},

            callback: function(l,e){
                // l is the main container
                // e is the element that was moved
                var id = e.data('id'), parent = e.parent().closest('.dd-item');
                var parent_id = parent.data('id');
                let parentText = parent.children('.dd-content').text();

                var childrens = parent.find('ol.dd-list').first().children('li.dd-item');
                // var order = JSON.stringify($('.dd').nestable('serialize'));
                // console.log( l );
                var siblings = [];
                if( childrens != null){
                    childrens.each(function( index ) {
                        siblings.push($( this ).data('id'));
                        // console.log( index + ": " + $( this ).data('id') );
                    });
                }

                $.post('{{ $action }}', {
                    element_id: id,
                    parent_id: parent_id,
                    siblings: JSON.stringify(siblings),
                }, function (data) {
                    window.addSuccessNotification(id+ "{{ __('crud::nestableElementMovedTo') }} " + parentText);
                });
    

                // console.log( order );
                // UIkit.notification(id+' was moved to '+ parent_id, 'success');
            }
        });

    });
</script>