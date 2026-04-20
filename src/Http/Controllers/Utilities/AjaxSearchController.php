<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities;

use IlBronza\CRUD\Helpers\AjaxSearch\AjaxSearchExecutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxSearchController
{
	public function __invoke(Request $request) : JsonResponse
	{
		$field = trim((string) $request->input('field', ''));

		if ($field === '')
			$field = trim((string) $request->input('ajax_search_field', ''));

		$q = $request->input('q', $request->input('search', ''));

		if (! is_string($q))
			$q = '';

		$results = AjaxSearchExecutor::search($field, $q);

		return response()->json([
			'results' => $results,
		]);
	}
}
