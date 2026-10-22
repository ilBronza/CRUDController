@if($paginator->hasPages())
    <nav aria-label="{{ __('crud::catalog.pagination') }}" class="uk-margin-top">
        <ul class="uk-pagination uk-flex-center uk-flex-wrap">
            <li @class(['uk-disabled' => $paginator->onFirstPage()])>
                @if($paginator->onFirstPage())<span>{{ __('crud::catalog.previous') }}</span>
                @else<a href="{{ $paginator->previousPageUrl() }}" data-catalog-link rel="prev">{{ __('crud::catalog.previous') }}</a>@endif
            </li>
            @foreach($elements as $element)
                @if(is_string($element))<li class="uk-disabled"><span>{{ $element }}</span></li>@endif
                @if(is_array($element))
                    @foreach($element as $page => $url)
                        @if($page == $paginator->currentPage())<li class="uk-active"><span aria-current="page">{{ $page }}</span></li>
                        @else<li><a href="{{ $url }}" data-catalog-link aria-label="{{ __('crud::catalog.page', ['page' => $page]) }}">{{ $page }}</a></li>@endif
                    @endforeach
                @endif
            @endforeach
            <li @class(['uk-disabled' => ! $paginator->hasMorePages()])>
                @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" data-catalog-link rel="next">{{ __('crud::catalog.next') }}</a>
                @else<span>{{ __('crud::catalog.next') }}</span>@endif
            </li>
        </ul>
    </nav>
@endif
