<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use ilBronza\Form\Facades\Form;

trait CRUDDeleteTrait
{
	/**
	 * get after delete redirect url
	 *
	 * @return string url
	 */
	public function getDeletedRedirectUrl()
	{
		return $this->getRouteUrlByType('idnex');
	}

	public function _delete($modelInstance)
	{
		$modelInstance->delete();

		return redirect()->to(
			$this->getDeletedRedirectUrl()
		);
	}
}