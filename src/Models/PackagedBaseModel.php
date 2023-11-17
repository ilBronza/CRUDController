<?php

namespace IlBronza\CRUD\Models;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;

class PackagedBaseModel extends BaseModel
{
	use CRUDModelTrait;
	use PackagedModelsTrait;

	use PackagedModelsTrait {
		PackagedModelsTrait::getRouteBaseNamePrefix insteadof CRUDModelTrait;
		PackagedModelsTrait::getPluralTranslatedClassname insteadof CRUDModelTrait;
		PackagedModelsTrait::getTranslatedClassname insteadof CRUDModelTrait;
	}

}