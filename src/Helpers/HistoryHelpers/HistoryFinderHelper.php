<?php

namespace IlBronza\CRUD\Helpers\HistoryHelpers;

use IlBronza\FormField\FormField;

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
		// if($field->getName() != 'client_id')
		// 	return null;

		// if($field->isRelationship())
		// 	dd($field);

		$activities = collect($model->activities()->with('causer')->where('properties', 'LIKE', '%' . $field . '%')->get());

		$result = [];

		foreach($activities as $activity)
		{
			$item = [
				'activity_id' => $activity->getKey(),
				'activity_created_at' => $activity->created_at->format('d-m-Y'),
				'activity_description' => $activity->event,
				'activity_causer' => $activity->causer?->getName(),
				'json' => $activity->properties,
				'value' => null,
				'old' => null
			];

			$properties = $activity->properties;

			if(isset($properties['attributes']))
				if(isset($properties['attributes'][$field]))
					$item['value'] = $properties['attributes'][$field];

			if(isset($properties['old']))
				if(isset($properties['old'][$field]))
					$item['value'] = $properties['old'][$field];

			$result[] = $item;
		}

        return view('crud::utilities.history.field', [
            'activities' => $result,
            'model' => $model,
            'field' => $field,
        ]);
	}
}

