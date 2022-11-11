<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Support\Str;

trait CRUDHasMetaTrait
{
	abstract public function getMetaTagsNames() : array;

	private function getMetaGetterMethodName(string $metaName)
	{
		return "get" . ucfirst(Str::slug($metaName, '')) . 'MetaTagValue';
	}

	private function translationFieldMeta(array $fieldsNames)
	{
		foreach($fieldsNames as $fieldsName)
			if($value = $this->translatedField('meta_title'))
				return $value;

		trans('meta.' . $fieldsNames[0] . "_" . Str::slug(app('uikittemplate')->getPageTitle()));
	}

    public function getMetaTags()
    {
    	$result = [];

    	$metaTagsNames = $this->getMetaTagsNames();

    	foreach($metaTagsNames as $metaName)
    	{
			$metaGetterMethodName = $this->getMetaGetterMethodName($metaName);

			$result[$metaName] = $this->$metaGetterMethodName();
    	}

    	return $result;
    }
}