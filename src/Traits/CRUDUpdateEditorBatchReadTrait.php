<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;

use function array_filter;
use function array_unique;
use function array_values;
use function is_array;

trait CRUDUpdateEditorBatchReadTrait
{
	/**
	 * Parallel to returnFieldFromEditor — cumulative read for many fields.
	 * Simulated/stub response for now; same shape as the future production endpoint.
	 */
	public function returnFieldsFromEditor(Request $request)
	{
		$fields = $request->input('fields', []);

		if (! is_array($fields))
			$fields = [$fields];

		$fields = array_values(array_unique(array_filter($fields)));

		$model = $this->getModel();
		$values = [];

		foreach ($fields as $field)
			$values[$field] = $model->{$field};

		$updateParameters = [
			'success' => true,
			'fetch-fields-batch' => true,
			'simulated' => true,
			'values' => $values,
			'model-id' => $model->getKey(),
		];

		return $this->returnUpdateParameters($request, $updateParameters);
	}
}
