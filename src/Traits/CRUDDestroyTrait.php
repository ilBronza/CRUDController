<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use ilBronza\Form\Facades\Form;

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

	public function destroy($modelInstanceId)
	{
		$this->modelInstance = $this->modelClass::withTrashed()->findOrFail($modelInstanceId);
		$this->modelInstance->destroy();

		return redirect()->to(
			$this->getDestroyedRedirectUrl()
		);
	}
}