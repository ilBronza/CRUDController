@extends('layouts.app')

@section('content')

<div class="uk-card uk-card-default">
    <div class="uk-card-header">
        <div uk-grid>
            <div class="uk-width-expand">
                <span class="uk-h3 uk-display-block">@indexLink($modelInstance) {{ $modelInstance->getName() }}</span>

                @if((isset($backToListUrl))||(isset($showButtons)))
                    <nav class="uk-navbar-container" uk-navbar>
                        <div class="uk-navbar-left">
                            <ul class="uk-navbar-nav">
                                @isset($backToListUrl)
                                <li><a href="{{ $backToListUrl }}">@lang('crud::crud.backToList')</a></li>
                                @endisset

                                @if(isset($showButtons))
                                    @foreach($showButtons as $showButton)
                                        @if($showButton)
                                            <li>{!! $showButton->renderLink() !!}</li>
                                        @endif
                                    @endforeach
                                @endif
                            </ul>
                        </div>
                    </nav>
                @endif
            </div>

            @if($modelInstance->userCanUpdate(Auth::user()))
                <div class="uk-width-auto">
                    <a href="{{ $modelInstance->getEditURL() }}">@lang('crud::crud.editElement', ['element' => $modelInstance->getName()])</a>
                </div>
            @endif

        </div>
    </div>
    <div class="uk-card-body">
        <dl>
        @foreach($allowedFields as $field)
            <dt>{{ __('fields.' . $field) }}</dt>
            <dd>
                @if((is_object($modelInstance->{$field}))||(is_array($modelInstance->{$field})))
                    {{ json_encode($modelInstance->{$field}) }}
                @else
                    {{ $modelInstance->{$field} }}
                @endif
            </dd>
        @endforeach
        </dl>
    </div>

    @include('crud::uikit._relationships')

</div>

@endsection