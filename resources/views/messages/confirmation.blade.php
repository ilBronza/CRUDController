@extends('uikittemplate::app')

@section('content')
@php
	$formMethod = strtoupper($formMethod ?? 'POST');
	$methodAttribute = in_array($formMethod, ['GET', 'POST']) ? $formMethod : 'POST';
	$hiddenParameters = $hiddenParameters ?? [];

	$renderHiddenInputs = function(array $parameters, ?string $parentKey = null) use (&$renderHiddenInputs)
	{
		$result = '';

		foreach($parameters as $key => $value)
		{
			$fieldName = $parentKey ? "{$parentKey}[{$key}]" : $key;

			if(is_array($value))
			{
				$result .= $renderHiddenInputs($value, $fieldName);
				continue;
			}

			if(is_null($value))
				$value = '';

			if(is_bool($value))
				$value = $value ? '1' : '0';

			if(! is_scalar($value))
				continue;

			$result .= '<input type="hidden" name="' . e($fieldName) . '" value="' . e((string) $value) . '">' . PHP_EOL;
		}

		return $result;
	};
@endphp

<div class="uk-container uk-container-small uk-margin-large-top">
	<div class="uk-card uk-card-default">
		<div class="uk-card-header">
			<h1 class="uk-card-title">{{ $title ?? 'Conferma operazione' }}</h1>
		</div>

		<div class="uk-card-body">
			@if($messages ?? null)
				<div class="uk-alert-warning" uk-alert>
					@foreach((array) $messages as $message)
						<p>{!! $message !!}</p>
					@endforeach
				</div>
			@endif

			@if($description ?? null)
				<p>{!! $description !!}</p>
			@endif

			<form method="{{ $methodAttribute }}" action="{{ $formAction }}">
				@if($methodAttribute === 'POST')
					@csrf
				@endif

				@if(! in_array($formMethod, ['GET', 'POST']))
					@method($formMethod)
				@endif

				{!! $renderHiddenInputs($hiddenParameters) !!}

				@if($confirmationField ?? null)
					<input type="hidden" name="{{ $confirmationField }}" value="{{ $confirmationValue ?? 1 }}">
				@endif

				<button type="submit" class="uk-button uk-button-primary">
					{{ $confirmButtonText ?? 'Conferma' }}
				</button>

				@if($cancelUrl ?? null)
					<a href="{{ $cancelUrl }}" class="uk-button uk-button-default">{{ $cancelButtonText ?? 'Annulla' }}</a>
				@endif
			</form>
		</div>
	</div>
</div>
@endsection
