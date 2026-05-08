<?php

namespace IlBronza\CRUD\Helpers\HistoryHelpers;

use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Models\Casts\ExtraFieldJson;
use IlBronza\CRUD\Traits\Model\CRUDModelExtraFieldsTrait;
use IlBronza\FormField\FormField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HistoryFinderHelper
{
	static function getHistoryUrlByField($model, FormField $field)
	{
		return route('history.historyByField', [
				'model' => $model->getMorphClass(),
				'key' => $model->getKey(),
				'field' => $field->getName()
			]);
	}

	static function getHistoryViewByModelFieldName($model, string $field)
	{
		$historySubject = $model;
		$historyField = $field;

		if(($extrafield = self::resolveExtrafieldHistoryTarget($model, $field)))
		{
			$historySubject = $extrafield['subject'];
			$historyField = $extrafield['field'];
		}

		// if($field->getName() != 'client_id')
		// 	return null;

		// if($field->isRelationship())
		// 	dd($field);

		$activities = $historySubject
			? collect($historySubject->activities()->where('properties', 'LIKE', '%' . $historyField . '%')->get())
			: collect();

		self::loadActivityCausersCrossConnection($activities);

		$result = [];

		foreach($activities as $activity)
		{
			$properties = self::normalizeActivityProperties($activity->properties);

			$olds = isset($properties['old']) && is_array($properties['old']) ? $properties['old'] : [];
			$attrs = isset($properties['attributes']) && is_array($properties['attributes']) ? $properties['attributes'] : [];

			$oldLogged = array_key_exists($historyField, $olds);
			$valueLogged = array_key_exists($historyField, $attrs);

			$item = [
				'activity_id' => $activity->getKey(),
				'activity_created_at' => $activity->created_at->format('d-m-Y'),
				'activity_description' => $activity->event,
				'activity_causer' => $activity->causer?->getName(),
				'old_logged' => $oldLogged,
				'value_logged' => $valueLogged,
				'old' => $oldLogged
					? self::formatActivityPropertyValuePlain($olds[$historyField])
					: __('crud::history.activityPropertyEmpty'),
				'value' => $valueLogged
					? self::formatActivityPropertyValuePlain($attrs[$historyField])
					: __('crud::history.activityPropertyEmpty'),
			];

			$result[] = $item;
		}

        return view('crud::utilities.history.field', [
            'activities' => $result,
            'model' => $historySubject ?: $model,
            'field' => $historyField,
        ]);
	}

	/**
	 * Se il campo è un extrafield (su model collegato), lo storico activity log è sul model correlato
	 * con il nome colonna effettivo sul record collegato.
	 *
	 * @return array{subject: ?Model, field: string}|null null se il campo non è extrafield
	 */
	static function resolveExtrafieldHistoryTarget(Model $model, string $field) : ? array
	{
		if(! in_array(CRUDModelExtraFieldsTrait::class, class_uses_recursive($model), true))
			return null;

		$extraCasts = $model->getExtraFieldsCasts();

		if(! isset($extraCasts[$field]))
			return null;

		$casting = $extraCasts[$field];

		if(stripos($casting, 'ExtraFieldDossier') !== false)
			return null;

		if(stripos($casting, 'ExtraFieldParameter') !== false)
			return null;

		if(stripos($casting, 'CalculatedTotals') !== false)
			return null;

		$castingClass = explode(':', $casting, 2)[0];

		if(
			$castingClass !== ExtraFieldJson::class
			&& ! is_a($castingClass, ExtraField::class, true)
		)
			return null;

		$caster = ExtraField::makeByCastAttribute($casting);

		$storageField = $field;

		if($caster instanceof ExtraField && $caster->extraFieldName)
			$storageField = $caster->extraFieldName;

		if(! $caster->extraModelClassname)
		{
			if(! $model->relationLoaded('extraFields'))
				$model->load('extraFields');

			return [
				'subject' => $model->extraFields,
				'field' => $storageField,
			];
		}

		$relation = $caster->extraModelClassname;

		if(! $model->relationLoaded($relation))
			$model->load($relation);

		return [
			'subject' => $model->$relation,
			'field' => $storageField,
		];
	}

	/**
	 * Evita MorphTo::causer caricato sulla connection dell'activity: ogni classe causer viene
	 * interrogata sulla propria {@see Model::$connection} (supporta anche la morph map).
	 *
	 * @param  \Illuminate\Support\Collection<int, Model>  $activities
	 */
	static function loadActivityCausersCrossConnection(Collection $activities) : void
	{
		$relevant = $activities->filter(function ($activity) {
			return $activity->getAttribute('causer_id') !== null
				&& $activity->getAttribute('causer_type');
		});

		if($relevant->isEmpty())
			return;

		foreach($relevant->groupBy('causer_type') as $morphType => $group)
		{
			$modelClass = Relation::getMorphedModel($morphType) ?? $morphType;

			if(! is_string($modelClass)
				|| ! class_exists($modelClass)
				|| ! is_subclass_of($modelClass, Model::class)
			)
				continue;

			/** @var Model $prototype */
			$prototype = $modelClass::make();
			$keyName = $prototype->getKeyName();

			$ids = $group->pluck('causer_id')->unique()->filter(function ($id) {
				return $id !== '' && $id !== null;
			})->values()->all();

			if($ids === [])
				continue;

			$causers = $modelClass::query()->whereIn($keyName, $ids)->get()->keyBy(fn (Model $m) => (string) $m->getAttribute($keyName));

			foreach($group as $activity)
			{
				$causerKey = $activity->getAttribute('causer_id');

				$matched = $causers->get((string) $causerKey);

				if(! $matched)
					continue;

				$activity->setRelation('causer', $matched);
			}
		}
	}

	static function normalizeActivityProperties(mixed $properties) : array
	{
		if($properties instanceof Collection)
			$properties = $properties->toArray();

		if(is_string($properties))
			$properties = json_decode($properties, true) ?? [];

		if(! is_array($properties))
			return [];

		foreach(['attributes', 'old'] as $block)
			if(isset($properties[$block]) && $properties[$block] instanceof Collection)
				$properties[$block] = $properties[$block]->toArray();

		return $properties;
	}

	static function formatActivityPropertyValuePlain(mixed $value) : string
	{
		if($value === null || $value === '')
			return __('crud::history.activityPropertyEmpty');

		if(is_bool($value))
			return $value ? __('crud::history.activityPropertyYes') : __('crud::history.activityPropertyNo');

		if(is_int($value) || is_float($value))
			return (string) $value;

		if(is_string($value))
			return $value;

		if($value instanceof Collection)
			$value = $value->toArray();

		if(is_array($value))
		{
			if($value === [])
				return __('crud::history.activityPropertyEmpty');

			$chunks = [];

			foreach($value as $k => $v)
			{
				$formatted = self::formatActivityPropertyValuePlain($v);

				if(is_int($k))
					$chunks[] = $formatted;
				else
				{
					$t = __('fields.' . $k);

					$chunks[] = ($t === 'fields.' . $k ? Str::ucfirst(str_replace('_', ' ', $k)) : $t) . ': ' . $formatted;
				}
			}

			return implode('; ', $chunks);
		}

		return (string) $value;
	}
}

