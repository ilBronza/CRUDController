@if($image)
	<img src="{{ $image }}" alt="{{ $modelInstance->getName() }} Logo">
@else
	<img src="{{ $modelInstance->getMissingLogoUrl() }}" alt="{{ $modelInstance->getName() }} Logo">
@endif

@if($canUpload ?? true)
	<a class="uk-button uk-button-small uk-button-primary" href="{{ $modelInstance->getUploadLogoFormUrl() }}">
		@lang('crud::buttons.uploadNewImage')
	</a>
@endif