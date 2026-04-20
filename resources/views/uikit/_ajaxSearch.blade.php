@include('formfield::uikit.formRowHeader')

	<input
		@include('formfield::__data')
		@include('formfield::__attributes')

		type="text"
		value="{{ $field->getFormOldValue() }}"
		data-crud-ajax-search-url="{{ $field->getSearchUrl() }}"
		data-crud-ajax-search-field="{{ $field->getName() }}"
		/>

@include('formfield::uikit.formRowFooter')
