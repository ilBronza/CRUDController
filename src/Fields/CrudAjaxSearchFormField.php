<?php

namespace IlBronza\CRUD\Fields;

use IlBronza\FormField\Fields\TextFormField;

class CrudAjaxSearchFormField extends TextFormField
{
	public $type = 'ajaxSearch';

	public ?string $searchUrl = null;

	public string $searchRouteName = 'crud.ajaxSearch';

	public $viewName = 'ajaxSearch';

	public function getViewName($type) : string
	{
		if ($this->getDisplayMode() == 'show')
			return parent::getShowViewName('text');

		return 'crud::uikit._ajaxSearch';
	}

	public function getShowViewName($type) : string
	{
		return parent::getShowViewName('text');
	}

	public function renderPdf()
	{
		return view('formfield::uikit.pdf._text', ['field' => $this]);
	}

	public function getSearchUrl() : string
	{
		if ($this->searchUrl !== null && $this->searchUrl !== '')
			return $this->searchUrl;

		return route($this->searchRouteName);
	}

	public function setName(string $name) : self
	{
		$this->name = $name;

		return $this;
	}
}
