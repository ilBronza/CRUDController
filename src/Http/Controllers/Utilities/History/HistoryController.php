<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\History;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Helpers\HistoryHelpers\HistoryFinderHelper;
use IlBronza\CRUD\Helpers\ModelHelpers\ModelFinderHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HistoryController extends CRUD
{
	public $allowedMethods = ['show'];

	public function show(string $model, string $key)
	{
		$model = ModelFinderHelper::getByClassKey($model, $key);

		if(! $model)
			abort(404);

		if(! method_exists($model, 'userCanShow') || ! $model->userCanShow(auth()->user()))
			abort(403);

		$activities = $this->getActivities($model)->sortByDesc('created_at')->values();
		HistoryFinderHelper::loadActivityCausersCrossConnection($activities);

		$fields = $this->getFields($activities, $model);
		$activitiesResult = $activities->map(function ($activity) use ($fields, $model) {
			return $this->formatActivity($activity, $fields, $model);
		})->all();

		return view('crud::utilities.history.history', [
			'activitiesResult' => $activitiesResult,
			'model' => $model,
			'fields' => $fields,
			'activities' => $activities,
		]);
	}

	protected function getActivities(Model $model) : Collection
	{
		$subjects = collect([$model]);

		if(method_exists($model, 'getExtraFieldsRelatedModels'))
			$subjects = $subjects->merge($model->getExtraFieldsRelatedModels());

		return $subjects
			->filter(fn ($subject) => $subject instanceof Model && method_exists($subject, 'activities'))
			->unique(fn (Model $subject) => $subject->getConnectionName() . ':' . $subject::class . ':' . $subject->getKey())
			->flatMap(fn (Model $subject) => $subject->activities()->get())
			->unique(fn ($activity) => $activity::class . ':' . $activity->getKey())
			->values();
	}

	protected function getFields(Collection $activities, Model $model) : array
	{
		$fields = [];

		foreach($activities as $activity)
		{
			$properties = HistoryFinderHelper::normalizeActivityProperties($activity->properties);

			foreach(['attributes', 'old'] as $section)
			{
				if(! is_array($properties[$section] ?? null))
					continue;

				foreach($properties[$section] as $field => $value)
					$fields[$field] = true;
			}
		}

		foreach($this->getExcludedFields($model) as $field)
			unset($fields[$field]);

		return $fields;
	}

	protected function getExcludedFields(Model $model) : array
	{
		$fields = [$model->getKeyName(), 'created_at', 'updated_at', 'deleted_at'];

		if(method_exists($model, 'getHistoryExcludedFields'))
			$fields = array_merge($fields, $model->getHistoryExcludedFields());

		return $fields;
	}

	protected function formatActivity($activity, array $fields, Model $model) : array
	{
		$properties = HistoryFinderHelper::normalizeActivityProperties($activity->properties);
		$attributes = is_array($properties['attributes'] ?? null) ? $properties['attributes'] : [];
		$old = is_array($properties['old'] ?? null) ? $properties['old'] : [];

		$item = [
			'activity_id' => $activity->getKey(),
			'activity_created_at' => $activity->created_at?->format('Y-m-d H:i:s'),
			'activity_description' => $activity->event ?? $activity->description,
			'activity_causer' => $this->getCauserName($activity),
		];

		foreach($fields as $field => $true)
		{
			if(array_key_exists($field, $attributes))
				$value = $attributes[$field];
			elseif(array_key_exists($field, $old))
				$value = $old[$field];
			else
				continue;

			$item[$field] = $this->formatFieldValue($model, $field, $value);
		}

		return $item;
	}

	protected function getCauserName($activity) : ?string
	{
		$causer = $activity->causer;

		if(! $causer)
			return null;

		if(method_exists($causer, 'getName') && ($name = $causer->getName()) !== null)
			return (string) $name;

		return $causer->getAttribute('name') ?? (string) $causer->getKey();
	}

	protected function formatFieldValue(Model $model, string $field, mixed $value) : string
	{
		if(str_ends_with($field, '_id') && $value !== null)
			if($relatedName = $this->getRelatedModelName($model, $field, $value))
				return $relatedName;

		return HistoryFinderHelper::formatActivityPropertyValuePlain($value);
	}

	protected function getRelatedModelName(Model $model, string $field, mixed $value) : ?string
	{
		$relationName = Str::camel(Str::beforeLast($field, '_id'));

		if(! $relationName || ! method_exists($model, $relationName))
			return null;

		try
		{
			$relation = $model->$relationName();

			if(! $relation instanceof Relation)
				return null;

			$related = ModelFinderHelper::getByClassKey($relation->getRelated()::class, (string) $value);
		}
		catch(\Throwable)
		{
			return null;
		}

		if(! $related)
			return null;

		if(method_exists($related, 'getName') && ($name = $related->getName()) !== null)
			return (string) $name;

		return $related->getAttribute('name') ?? (string) $related->getKey();
	}
}
