<form method="POST" action="{{ route('crud.seo.update', ['model' => $model->getMorphClass(), 'key' => $model->getKey(), 'locale' => $locale]) }}">
    @csrf
    @method('PUT')
    {!! \IlBronza\CRUD\Helpers\Seo\SeoFormHelper::render($model, $locale)->render() !!}
    <button class="uk-button uk-button-primary" type="submit">{{ __('Save') }}</button>
</form>
