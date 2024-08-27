@if($image)
	<img src="{{ $image }}" alt="{{ $modelInstance->getName() }} Logo">
@else
	<img src="{{ $modelInstance->getMissingLogoUrl() }}" alt="{{ $modelInstance->getName() }} Logo">
@endif