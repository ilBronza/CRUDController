<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use IlBronza\Form\Facades\Form;

trait CRUDDestroyTrait
{
	/**
	 * get after destroy redirect url
	 *
	 * @return string url
	 */
	public function getDestroyedRedirectUrl()
	{
		return $this->getRouteUrlByType('index');
	}

	// public function _destroy($modelInstanceId)
	// {
	// 	$this->modelInstance = $this->getModelClass()::withTrashed()->findOrFail($modelInstanceId);
	// 	$this->modelInstance->destroy();

	// 	return redirect()->to(
	// 		$this->getDestroyedRedirectUrl()
	// 	);
	// }
}