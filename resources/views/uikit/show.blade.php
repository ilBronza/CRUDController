@extends('app')

@section('content')

@include('uikittemplate::utilities.__extraViews', ['position' => 'top'])

<div class="uk-card uk-card-default show-{{ strtolower(class_basename($modelInstance)) }}">

    <div class="uk-card-header">
        <div uk-grid>
            <div class="uk-width-expand">
                @if($indexUrl = $modelInstance->getIndexUrl())
                <a class="uk-display-inline-block" href="{{ $indexUrl }}">
                    <i class="fa-solid fa-list"></i>
                    Torna alla lista {{ $modelInstance->getPluralTranslatedClassname() }}
                </a>
                @endif

                <span class="uk-h3 uk-display-block">
                    {{ $modelInstance->getName() }}
                </span>

                @if((isset($backToListUrl))||(isset($showButtons)))
                    <nav
                        class="uk-navbar-container"
                        uk-navbar

                        @if($showStickyButtonsNavbar)
                        uk-sticky
                        @endif
                        >
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

            @if(($modelInstance->getEditURL())&&($modelInstance->userCanUpdate(Auth::user())&&($canEditModelInstance)))
                <div class="uk-width-auto">
                    <a href="{{ $editModelInstanceUrl ?? $modelInstance->getEditURL() }}">@lang('crud::crud.editElement', ['element' => $modelInstance->getName()])</a>
                </div>
            @endif

        </div>
    </div>
    <div class="uk-card-body {{ $htmlClasses ?? '' }}">
	    @include($_showView)
    </div>


    @include('crud::uikit._relationships')

</div>

@include('uikittemplate::utilities.__extraViews', ['position' => 'bottom'])

@endsection