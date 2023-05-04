<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Auth;
use IlBronza\Form\Facades\Form;

trait CRUDDeleteTrait
{
	/**
	 * get after delete redirect url
	 *
	 * @return string url
	 */
	// public function getDeletedRedirectUrl()
	// {
	// 	return $this->getRouteUrlByType('index');
	// }

	public function getDeletedRedirectUrl()
	{
		if($url = $this->getReturnUrl())
			return $url;

		if($url = $this->getAfterDeleteRoute())
			return $url;

		if(in_array('index', $this->allowedMethods))
			return $this->getRouteUrlByType('index');

		return url()->previous();
	}


	private function returnDeletionResponse($element)
	{
		$name = $element->getName();
		$message = __('messages.elementSuccesfullyDeleted', ['element' => $name]);

		if(request()->ajax())
		{
			return response()->json([
				'success' => true,
				'message' => $message,
				'action' => 'remove',
				'ids' => [
					$element->getKey()
				]
			]);
		}

		return redirect()->to(
				$this->getDeletedRedirectUrl()
			)->with('crud.success', $message);
	}

	public function forceDelete($id)
	{
		$element = $this->getModelClass()::withTrashed()->find($id);
		$element->deleterForceDelete();

		return $this->returnDeletionResponse($element);
	}

	public function _destroy($modelInstance)
	{
		$modelInstance->deleterDelete();

		return $this->returnDeletionResponse($modelInstance);
	}
}