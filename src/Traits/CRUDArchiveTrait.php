<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait CRUDArchiveTrait
{
	public function _archive(Request $request, Model $model)
	{
		$request->validate([
			'arvhive' => 'nullable|string'
		]);

/*		$model->archive($request->input('archive'));*/
		$model->archive();

		$updateParameters = [];
		$updateParameters['success'] = true;
		$updateParameters['action'] = 'removeRow';

		return $updateParameters;
	}
}