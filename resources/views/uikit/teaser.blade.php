@extends('app')

@section('content')

<div class="uk-card uk-card-default teaser-{{ strtolower(class_basename($modelInstance)) }}">

    <div class="uk-card-header">
        <div uk-grid>
            <div class="uk-width-expand">
                <span class="uk-h3 uk-display-block">@indexLink($modelInstance) {{ $modelInstance->getName() }}</span>
            </div>
        </div>
    </div>
    <div class="uk-card-body">

        @include('crud::uikit._teaserFields')

    </div>

</div>

@endsection