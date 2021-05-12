<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'missing' }}</title>

    <script src="/js/jquery/jquery.min.js"></script>

	<script type="text/javascript">

		@if(isset($refresh))
		window.opener.location.reload();
		@endif

		@if(isset($callertablename))
		window.opener.table{{ $callertablename }}.ajax.reload();
		@endif

		@if(isset($message))
		window.opener.addSuccessNotification('{{ $message }}');
		@endif

		window.close();

	</script>

</head>

<body>
	@if(isset($refresh))
	window.opener.location.reload();
	@endif

	@if(isset($callertablename))
	window.opener.table{{ $callertablename }}.ajax.reload();
	@endif

	@if(isset($message))
	window.opener.addSuccessNotification('{{ $message }}');
	@endif

	window.close();
</body>
</html>
