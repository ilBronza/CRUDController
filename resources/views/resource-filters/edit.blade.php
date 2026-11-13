@extends('uikittemplate::app')

@section('content')
    {!! $form->_render() !!}
    @if($active)
        @include('crud::resource-filters.reset', ['iframed' => request()->boolean('iframed')])
    @endif
    @if($hasActiveFilters)
        @include('crud::resource-filters.reset', ['resource' => null, 'iframed' => request()->boolean('iframed')])
    @endif
@endsection
