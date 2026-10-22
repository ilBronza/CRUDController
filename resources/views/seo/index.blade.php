<ul class="uk-list">
@foreach($records as $seo)
    <li><a href="{{ \IlBronza\CRUD\Helpers\Seo\SeoFormHelper::url($model, 'edit', $seo->locale) }}">{{ $seo->locale }} — {{ $seo->title }}</a></li>
@endforeach
</ul>
<a href="{{ \IlBronza\CRUD\Helpers\Seo\SeoFormHelper::url($model, 'edit', app()->getLocale()) }}">SEO — {{ app()->getLocale() }}</a>
