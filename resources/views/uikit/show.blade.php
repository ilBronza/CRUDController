@extends('app')

@section('content')

@include('uikittemplate::utilities.__extraViews', ['position' => 'top'])

<div class="uk-card uk-card-default show-{{ strtolower(class_basename($modelInstance)) }}">

    <div class="uk-card-header">
        <div uk-grid>
            <div class="uk-width-expand">
                <span class="uk-h3 uk-display-block">
                    {{ $modelInstance->getName() }}

                    @if($modelInstance->userCanUpdate(Auth::user()))
                    @if(($editUrl = ($editModelInstanceUrl ?? $modelInstance->getEditURL()))&&((((! isset($canEditModelInstance))||($canEditModelInstance)))))
                        <a href="{{ $editUrl }}">
                            {!! FaIcon::edit() !!}
                        </a>
                    @endif
                    @endif

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

            @if($showEditLink)
                @include('crud::utilities.editLink', ['element' => $modelInstance])
            @endif

            @if($indexUrl = $modelInstance->getIndexUrl())
                <div class="uk-width-auto">
                    <a class="uk-display-inline-block" href="{{ $indexUrl }}">
                        <i class="fa-solid fa-list"></i>
                        Torna alla lista {{ $modelInstance->getPluralTranslatedClassname() }}
                    </a>
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