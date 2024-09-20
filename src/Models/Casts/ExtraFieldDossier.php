<?php

namespace IlBronza\CRUD\Models\Casts;

use IlBronza\FileCabinet\Helpers\DossierCreatorHelper;
use IlBronza\FileCabinet\Models\Form;
use IlBronza\FileCabinet\Models\Formrow;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

use function explode;
use function ff;

class ExtraFieldDossier implements CastsAttributes
{
	public string $formSlug;
	public string $formrowSlug;

	/**
	 * Cast the given value.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @param  string  $key
	 * @param  mixed  $value
	 * @param  array  $attributes
	 * @return mixed
	 */
	public function __construct(string $formSlug, string $formrowSlug)
	{
		$this->formSlug = $formSlug;
		$this->formrowSlug = $formrowSlug;
	}

	public function getFormrowSlug() : string
	{
		return $this->formrowSlug;
	}

	public function getFormSlug() : string
	{
		return $this->formSlug;
	}
	
	public function set($model, string $key, $value, array $attributes)
	{
		$form = Form::gpc()::findCachedByField('slug', $this->getFormSlug());
		$formrow = Formrow::gpc()::findCachedByField('slug', $this->getFormrowSlug());

		$dossier = DossierCreatorHelper::getOrCreateByForm($model, $form);

		return $dossier->setValueByFormrow($formrow, $value);
	}

	public function get($model, string $key, $value, array $attributes)
	{
		$form = Form::gpc()::findCachedByField('slug', $this->getFormSlug());
		$formrow = Formrow::gpc()::findCachedByField('slug', $this->getFormrowSlug());

		$dossier = DossierCreatorHelper::getOrCreateByForm($model, $form);

		return $dossier->getValueByFormrow($formrow);
	}

}
