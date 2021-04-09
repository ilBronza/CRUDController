<!DOCTYPE html>
<html>
<head>
	<title></title>
	<!-- UIkit CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.0-rc.22/css/uikit.min.css" />

        <!-- UIkit JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.0-rc.22/js/uikit.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.0-rc.22/js/uikit-icons.min.js"></script>
        <script src="{{asset('/js/nestable.js')}}"></script>

        <style>

            /* nested sortable groups indention */            
            .uk-sortable .uk-sortable,
            .uk-sortable-drag .uk-sortable {
                margin-left: 50px;
            }

            /* nested sortable groups inner padding to the parent element */
            .uk-sortable .uk-sortable:not(.uk-sortable-empty),
            .uk-sortable .uk-sortable-drag:not(.uk-sortable-empty) {
                padding-top: 20px;
            }

            /* remove uk-sortable-empty min-height sort nested sortables */
            .uk-sortable .uk-sortable.uk-sortable-empty,
            .uk-sortable-drag .uk-sortable.uk-sortable-empty {
                min-height: 0;
            }

            /* Custom placeholder styles, makes it easier to see the current position */
            .uk-sortable-placeholder {
                position: relative;
                opacity: 1;
            }

            .uk-sortable-placeholder > * {
                opacity: 0;
            }

            .uk-sortable-placeholder:after {
                content: '';
                position: absolute;
                top: 0;
                bottom: 0;
                left: 0;
                right: 0;
                border: 1px dashed #E5E5E5;
                opacity: 1;
            }

        </style>
        <script>

            /*var util = UIkit.util;
            util.ready(function () {
                util.on(document.body, 'start moved added removed stop', function (e, sortable, el) {
                    console.log(e.type, sortable, el);
                });
            });*/

        </script>
</head>
<body>

	<div class="uk-container uk-padding">
		STORE REORDER

		variabili $elements 
		variabili $action
	</div>

	<div class="uk-container uk-padding">

        <h1>Nestable</h1>

        @if ($elements->count() > 0)
		    <div class="PARENT" uk-sortable="handle: .uk-sortable-handle; nestable: true; animation: 150; nestable-container-class: group">
		    @foreach ($elements as $element)
		        @include('crud::partials.element', compact('element'))
		    @endforeach
		    </div>
		@else
		    @include('crud::partials.elements-none')
		@endif

</body>
</html>