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
		$actionString = implode(".", [
			$this->getLcfirstPluralModelClassname($this->modelInstance),
			'index'
		]);

		return route($actionString);
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