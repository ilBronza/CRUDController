<?php

namespace IlBronza\CRUD\Http\Controllers;

use IlBronza\Products\Http\Controllers\Product\ProductIndexController as IbProductIndexController;

class CateringProductIndexController extends IbProductIndexController
{
	public function getIndexElements()
	{
		return $this->getModelClass()::withCount([
			'orders',
			'activeOrders',
			'productRelations',
		])
			->with(
				'media.model',
				'categories',
				'extraFields',
				'allergens',
				'descendants.allergens',
				'descendants.descendants.allergens',
				'prices',
			)
			->get();
	}
}
