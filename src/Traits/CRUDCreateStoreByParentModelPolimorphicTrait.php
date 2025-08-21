<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\Helpers\ModelHelpers\ModelSchemaHelper;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

trait CRUDCreateStoreByParentModelPolimorphicTrait
{
	public function hasPolimorphicRelationship() : bool
	{
		if(isset($this->hasPolimorphicRelationship))
			return $this->hasPolimorphicRelationship;

		return false;
	}
    public function hasCreatingPolimorphicRelationship() : bool
    {
		if($this->hasPolimorphicRelationship())
			return true;

        return strpos($this->relatedModel, "\\") !== false;
    }

	public function getRelatedModelFullQualifiedClassName() : string
	{
		return Relation::getMorphedModel($this->relatedModel);
	}

    public function setPolimorphicParentModel()
    {
        $this->polimorphicParentModel = $this->getRelatedModelFullQualifiedClassName()::findOrFail($this->relatedKey);
    }

    // public function getDatabaseFields() : array
    // {
    // 	return Schema::getColumnListing($this->getModelClass()::make()->getTable());
    // }

    public function getModelPolimorphicTypeField() : string
    {
        $this->databaseFields = ModelSchemaHelper::getModelDatabaseFieldsByClass(
            $this->getModelClass()
        );

        $result = [];

        foreach($this->databaseFields as $field)
            if(substr($field, -5) == '_type')
                $result[] = $field;

        if(count($result) != 1)
            throw new \Exception('Impossibile trovare il campo TIPO per collegare il polimorfismo, sono stati trovati ' . count($result) . ' possibili campi: ' . json_encode($result));

        return $result[0];
    }

    public function getModelPolimorphicKeyField()
    {
        $morphFieldBaseName = str_replace("type", "", $this->modelTypeField);

        $result = [];

        foreach($this->databaseFields as $field)
            if(strpos($field, $morphFieldBaseName) === 0)
                if($field != $this->modelTypeField)
                    $result[] = $field;

        if(count($result) != 1)
            throw new \Exception('Impossibile trovare il campo ID per collegare il polimorfismo, sono stati trovati ' . count($result) . ' possibili campi: ' . json_encode($result));

        return $result[0];
    }

    public function getModelDefaultPolimorphicParameters()
    {
//        if(! in_array($this->relatedModel, $this->getAssociableClassesList()))
//            throw new \Exception('Impossibile associare questo tipo di classe');

        $this->modelTypeField = $this->getModelPolimorphicTypeField();
        $this->modelKeyField = $this->getModelPolimorphicKeyField();

        $this->setPolimorphicParentModel();

        return [
            $this->modelTypeField => $this->relatedModel,
            $this->modelKeyField => $this->relatedKey
        ];
    }


}