<?php

namespace IlBronza\CRUD\Buttons;

use IlBronza\Buttons\Button;

use function json_encode;

class CreateEmptyButton extends Button
{
	public string $createEmptyUrl;

	public function setCreateEmptyUrl(string $createEmptyUrl) : static
	{
		$this->createEmptyUrl = $createEmptyUrl;

		return $this;
	}

	public function getCreateEmptyUrl() : string
	{
		return $this->createEmptyUrl;
	}

	/**
	 * The request deliberately does not carry the DataTable origin headers.
	 * The table that creates the model must receive the `created` broadcast too,
	 * so its existing Reverb listener can fetch and append the new row.
	 */
	public function renderJsMethod() : string
	{
		$url = json_encode(
			$this->getCreateEmptyUrl(),
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		);

		return <<<JS
e.preventDefault();

const request = {
	url: {$url},
	type: 'POST',
	headers: {
		'X-Requested-With': 'XMLHttpRequest',
		'Accept': 'application/json'
	}
};

const csrfToken = window.csrfToken || jQuery('meta[name="csrf-token"]').attr('content');

if (csrfToken)
	request.headers['X-CSRF-TOKEN'] = csrfToken;

jQuery.ajax(request).fail(function (response)
{
	if (typeof window.__displayResponseErrors === 'function')
		window.__displayResponseErrors(response);
});
JS;
	}
}
