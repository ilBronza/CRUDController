@php
    $title = trim($item->title) === '' ? __('crud::catalog.untitled') : $item->title;
    $url = \IlBronza\CRUD\Catalog\CatalogItem::safeUrl($item->url);
    $image = \IlBronza\CRUD\Catalog\CatalogItem::safeUrl($item->image);
@endphp
<article class="uk-card uk-card-default crud-catalog-card" data-catalog-item="{{ $item->key }}">
    @if($image)
        <div class="uk-card-media-top"><img src="{{ $image }}" alt="" loading="lazy" class="crud-catalog-image"></div>
    @endif
    <div class="uk-card-body">
        <h3 class="uk-card-title">
            @if($url)<a href="{{ $url }}">{{ $title }}</a>@else{{ $title }}@endif
        </h3>
        @if($item->description !== null && $item->description !== '')<p>{{ $item->description }}</p>@endif
        @if($item->badges)
            <ul class="uk-list crud-catalog-badges">
                @foreach($item->badges as $badge)<li><span class="uk-label">{{ $badge }}</span></li>@endforeach
            </ul>
        @endif
    </div>
</article>
