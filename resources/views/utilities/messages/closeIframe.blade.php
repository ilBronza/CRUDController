<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<script type="text/javascript">

		@if(isset($reloadRows)&& isset($callertablename))
        window.parent.reloadTableRows("#{{ $callertablename }}", {!! $reloadRows->toJson() !!});
		@endif

				console.log('effettuo il reload di una riga specifica');

		@if($callertablename = session()->pull('callertablename', false))
				console.log('qua');
			@if($callerrowid = session()->pull('callerrowid'))
            	console.log('altrettanto');
        		window.parent.reloadTableRows("#{{ $callertablename }}", ["{{ $callerrowid }}"]);
            @endif
        @endif

		@if($postTableToUrl ?? false)
        window.parent.postTableToUrl("#{{ $callertablename }}", "{{ $postTableToUrl }}");
		@endif

		@if(isset($reloadAllTables))
        window.parent.__reloadAllTables();
		@endif

		@if(isset($tablesToRefresh))
		@foreach($tablesToRefresh as $tableToRefresh)

        window.parent.___reloadTable(
            window.parent.__getDataTableByClass("{{ $tableToRefresh }}")
        );

		{{--window.___reloadTable(--}}
		{{--    window.__getDataTableByClass("{{ $tableToRefresh }}")--}}
		{{--);--}}

		@endforeach
		@endif

		@if($closeMessage)
            window.parent.addSuccessNotification('{!! $closeMessage !!} ');
		@endif


        window.parent.closeLightbox();
        window.parent.closeModal();


		{{--		@if($callertablename = request()->input('callertablename', false))--}}
		{{--        window.parent.reloadAjaxTable("#{{ $callertablename }}");--}}
		{{--        window.parent.removePopup("#datatablepopup");--}}
		{{--		@endif--}}

		{{--		@if(isset($closeMessage))--}}
		{{--        window.parent.addSuccessNotification('{{ $closeMessage }}');--}}
		{{--		@endif--}}

        // window.close();

	</script>
</head>
<body></body>
</html>