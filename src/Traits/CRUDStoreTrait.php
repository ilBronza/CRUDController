<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelStorer;
use IlBronza\Form\Facades\Form;
use IlBronza\Form\Helpers\FieldsetsProvider\StoreFieldsetsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait CRUDStoreTrait
{
	// use CRUDValidateTrait;

	/**
	 * get after store redirect url
	 *
	 * @return string url
	 */
	public function getAfterStoredRedirectUrl()
	{
		if($url = $this->getReturnUrl())
			return $url;

		if($this->isSaveAndNew())
			return $this->getCreateUrl();

		if($this->isSaveAndRefresh())
			return $this->getRouteUrlByType('edit');

		return $this->getRouteUrlByType('index');
	}

	/**
	 * send store success message
	 **/
	public function sendStoreSuccessMessage()
	{

	}

	/**
	 * validate request and store model
	 *
	 * @param Request $request, Model $modelInstance
	 * @return Response redirect
	 **/
	public function store(Request $request)
	{
		return $this->_store($request);
	}

	public function checkUserStoringRights()
	{

	}

	/**
	 * validate request and store model
	 *
	 * @param Request $request, Model $modelInstance
	 * @return Response redirect
	 **/
	public function _store(Request $request)
	{
		$this->checkUserStoringRights();

		$this->modelInstance = CrudModelStorer::saveByRequest(
			$this->makeModel(),
			$this->getStoreParametersClass(),
			$request
		);

		$this->sendStoreSuccessMessage();

		return redirect()->to(
			$this->getAfterStoredRedirectUrl()
		);
	}
}