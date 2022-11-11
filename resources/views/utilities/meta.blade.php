@foreach(app('MetaManager')->getMeta() as $name => $value)
	<meta name="{{ $name }}" content="{!! $value !!}">
@endforeach