<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Auth;
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

	private function returnDeletionResponse($element)
	{
		$name = $element->getName();
		$message = __('messages.elementSuccesfullyDeleted', ['element' => $name]);

		if(request()->ajax())
			return response()->success($message);

		return redirect()->to(
				$this->getDeletedRedirectUrl()
			)->with('crud.success', $message);
	}

	public function forceDelete($id)
	{
		$element = $this->modelClass::withTrashed()->find($id);
		$element->deleterForceDelete();

		return $this->returnDeletionResponse($element);
	}

	public function _delete($modelInstance)
	{
		$modelInstance->deleterDelete();

		return $this->returnDeletionResponse($modelInstance);
	}
}