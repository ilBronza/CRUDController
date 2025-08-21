<?php

namespace IlBronza\CRUD\Traits\Model;

use App\Models\Referent;
use Exception;
use IlBronza\Buttons\Button;
use IlBronza\Ukn\Facades\Ukn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

use function htmlentities;
use function route;
use function trans;

trait CRUDRelationshipModelTrait
{
	use CRUDDeleterTrait;

	static function getSelfPossibleList() : array
	{
		$elements = static::orderBy('name')->get();

		return static::buildElementsArryForSelect($elements);
	}

	/**
	 * standard method to build array elements for select
	 *
	 * @param  Collection  $elements
	 *
	 * @return array
	 **/
	static public function buildElementsArryForSelect(Collection $elements) : array
	{
		$result = [];

		foreach ($elements as $element)
			$result[$element->getKey()] = $element->getNameForDisplayRelation();

		return $result;
	}

	public function getNameForDisplayRelation()
	{
		return $this->getName();
	}

	public function getCreateByPolimorphicRelatedButton(Model $related) : Button
	{
		$url = $this->getCreateByPolimorphicRelatedUrl($related);

		return $this->_getCreateByRelatedButton($related, $url);
	}

	public function getCreateByPolimorphicRelatedUrl(Model $related) : string
	{
		$relatedRoutePrefix = $this->pluralLowerClass();
		$routeName = $related->getKeyedRouteName("{$relatedRoutePrefix}.create");

		if (Route::has($routeName))
			return route($routeName, [$related]);

		$routeData = [
			'model' => $related->getMorphClass(),
			'key' => $related->getKey()
		];

		try
		{
			return $this->getKeyedRoute("createBy", $routeData, false);
		}
		catch (Exception $e)
		{
			Ukn::e($e->getMessage() . ' Crea la route per ' . get_class($this) . ' o vieta l\'aggiunta automatica del puslante tramite il relationshipManager o il controller');

			return $e->getMessage();
		}
	}

	public function _getCreateByRelatedText(Model $baseModel, Model $related = null)
	{
		return htmlentities(
			trans('crud::crud.createRelatedBy', [
			'by' => $baseModel->getTranslatedClassName(),
			'related' => $related?->getTranslatedClassName()
			])
		);
	}

	public function _getCreateByRelatedButton(Model $baseModel, string $url, Model $related = null) : Button
	{
		return Button::create([
			'href' => $url,
			'translatedText' => $this->_getCreateByRelatedText($baseModel, $related),
			'icon' => 'plus'
		]);
	}

	public function getCreateByRelatedButton(Model $baseModel, $related) : Button
	{
		$url = $this->getCreateByRelatedUrl($baseModel);

		return $this->_getCreateByRelatedButton($baseModel, $url, $related);
	}

	public function getCreateByRelatedUrl(Model $related) : string
	{
		$relatedRoutePrefix = $this->pluralLowerClass();
		$routeName = $related->getKeyedRouteName("{$relatedRoutePrefix}.create");

		if (Route::has($routeName))
			return route($routeName, [$related]);

		$relatedModelBaseClass = $related->getMorphClass();

		$routeName = $this->getKeyedRouteName("createBy{$relatedModelBaseClass}");

		if (Route::has($routeName))
			return route($routeName, [$related]);

		$routeData = [
			'model' => $related->pluralLowerClass(),
			'key' => $related->getKey()
		];

		try
		{
			return $this->getKeyedRoute("createBy", $routeData, false);
		}
		catch (Exception $e)
		{
			Ukn::e($e->getMessage() . ' Crea la route per ' . get_class($this) . ' o vieta l\'aggiunta automatica del puslante tramite il relationshipManager o il controller');

			return $e->getMessage();
		}
	}

	/**
	 * resolve and call getter function for possible related models
	 *
	 * @param  string  $relationship
	 *
	 * @return callable
	 **/
	public function getRelationshipPossibleValuesArray(string $relationship)
	{
		if ($relationship == 'parent')
		{
			if ((! isset($this->parentingTrait))&&(method_exists($this, 'parent') === false))
				throw new Exception('Aggiungere ParentingTrait al model ' . class_basename($this));

			return $this->getParentPossibleValuesArray();
		}

		$getterMethodName = 'getPossible' . ucfirst(Str::plural($relationship)) . 'ValuesArray';

		if (method_exists($this, $getterMethodName))
			return $this->$getterMethodName();

		return $this->_getRelationshipPossibleValuesArray($relationship);
	}

	/**
	 * resolve and call getter function for possible related models
	 *
	 * @param  string  $relationship
	 *
	 * @return callable
	 **/
	public function _getRelationshipPossibleValuesArray(string $relationship) : array
	{
		$relationModelClassBaseName = $this->getRelatedClassByRelationshipName($relationship);

		$elements = $relationModelClassBaseName::all();

		return $this->buildElementsArryForSelect($elements);
	}

	public function getRelatedClassByRelationshipName(string $relationship) : string
	{
		return get_class($this->{$relationship}()->getRelated());
	}

	/**
	 * get edit relation pivot row link
	 *
	 * calculate route to edit pivot row taking current model class and retrieving correct route
	 *
	 * @param  Model  $model
	 *
	 * @return string
	 **/
	public function getRelationEditUrl(Model $model)
	{
		$routePieces = [
			$this->getPluralCamelcaseClassBasename(),
			$model->getPluralCamelcaseClassBasename(),
			'edit'
		];

		return route(implode(".", $routePieces), [$this, $model]);
	}

	/**
	 *
	 **/
	public function getParentingAttributes()
	{
		$attributes = $this->getAttributes();

		if (isset($this->teaserFields))
			return array_intersect_key($attributes, array_flip($this->teaserFields));

		return $attributes;
	}
}