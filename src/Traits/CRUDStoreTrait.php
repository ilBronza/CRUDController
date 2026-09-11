<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\CrudRequestHelper;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelStorer;
use IlBronza\Form\Facades\Form;
use IlBronza\Form\Helpers\FieldsetsProvider\StoreFieldsetsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

use function dd;

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
	public function store(Request $request, string $model = null)
	{
		return $this->_store($request);
	}

	public function checkUserStoringRights()
	{

	}

	public function performAdditionalOperations()
	{

	}

	/**
	 * A relation create button opened from a DataTable supplies the id of its
	 * caller table. After a successful store, replace the iframe page with the
	 * close response so the parent can refresh that exact table and close modal.
	 */
	protected function shouldCloseIframeAfterStore(Request $request) : bool
	{
		return $this->isIframed() && $request->filled('callertablename');
	}

	protected function closeIframeAfterStore(Request $request)
	{
		return view('crud::utilities.messages.closeIframe', [
			'closeMessage' => __('crud::messages.success'),
			'tableIdsToRefresh' => [$request->input('callertablename')],
		]);
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

		$this->performAdditionalOperations();

		if ($this->shouldCloseIframeAfterStore($request))
			return $this->closeIframeAfterStore($request);

		if(CrudRequestHelper::isSaveAndCopy($request))
			return redirect()->to($this->modelInstance->getEditUrl());

		return redirect()->to(
			$this->getAfterStoredRedirectUrl()
		);
	}
}
