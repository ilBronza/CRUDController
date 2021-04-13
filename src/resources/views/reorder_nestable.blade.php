<!DOCTYPE html>
<html>
<head>
	<title></title>
	@include('layouts._headerScriptsSICURI')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.js"></script>

    <style type="text/css">
         /**
         * Nestable Draggable Handles
         */

        .dd-content {
            display: block;
            height: 30px;
            margin: 5px 0;
            padding: 5px 10px 5px 40px;
            color: #333;
            text-decoration: none;
            font-weight: bold;
            border: 1px solid #ccc;
            background: #fafafa;
            background: -webkit-linear-gradient(top, #fafafa 0%, #eee 100%);
            background: -moz-linear-gradient(top, #fafafa 0%, #eee 100%);
            background: linear-gradient(top, #fafafa 0%, #eee 100%);
            -webkit-border-radius: 3px;
            border-radius: 3px;
            box-sizing: border-box;
            -moz-box-sizing: border-box;
        }

        .dd-content:hover {
            color: #2ea8e5;
            background: #fff;
        }

        .dd-dragel > .dd-item > .dd-content {
            margin: 0;
        }

        .dd-item > button {
            margin-left: 30px;
        }

        .dd-handle {
            position: absolute;
            margin: 0;
            left: 0;
            top: 0;
            cursor: pointer;
            width: 30px;
            text-indent: 30px;
            white-space: nowrap;
            overflow: hidden;
            border: 1px solid #aaa;
            background: #ddd;
            background: -webkit-linear-gradient(top, #ddd 0%, #bbb 100%);
            background: -moz-linear-gradient(top, #ddd 0%, #bbb 100%);
            background: linear-gradient(top, #ddd 0%, #bbb 100%);
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .dd-handle:before {
            content: '≡';
            display: block;
            position: absolute;
            left: 0;
            top: 3px;
            width: 100%;
            text-align: center;
            text-indent: 0;
            color: #fff;
            font-size: 20px;
            font-weight: normal;
        }

        .dd-handle:hover {
            background: #ddd;
        }
    </style>

</head>
<body>

	<div class="uk-container uk-padding">
		STORE REORDER NESTABLE

		variabili $elements 
		variabili $action
	</div>

    <div class="uk-container uk-padding">

        <h1>Nestable JS</h1>

        <menu id="nestable-menu">
            <button type="button" data-action="expand-all">Expand All</button>
            <button type="button" data-action="collapse-all">Collapse All</button>
        </menu>

        @if ($elements->count() > 0)
            <div class="dd dd-item" data-id="0">
                <ol class="dd-list">
                @foreach ($elements as $element)
                    @include('crud::partials.element_nestable', compact('element'))
                @endforeach
                </ol>
            </div>
        @else
            @include('crud::partials.elements-none')
        @endif

    </div>

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

            let dump = function (obj) {
                console.log( JSON.stringify(obj, null, 2) );
            }


            $('.dd').nestable({
                /* config options */
                maxDepth: 6,

                callback: function(l,e){
                    // l is the main container
                    // e is the element that was moved
                    var id = e.data('id'), parent = e.parent().closest('.dd-item');
                    var parent_id = parent.data('id');
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

                    $.post('{{ route('categories.stroreReorder') }}', {
                        element_id: id,
                        parent_id: parent_id,
                        siblings: JSON.stringify(siblings),
                        _token: '{{ csrf_token() }}'
                    }, function (data) {
                        UIkit.notification(id+ "{{ __('crud::element_moved_to') }}"+ parent_id, 'success');
                    });
        

                    // console.log( order );
                    // UIkit.notification(id+' was moved to '+ parent_id, 'success');
                }
            });

        });
    </script>

</body>
</html>