<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Support\Str;

trait CRUDBelongsToModelRouteTrait
{
    abstract public function getOwnerModelLocalKey() : string;
    abstract public function getOwnerModelClass() : string;

    public function getOwnerModelKey() : string
    {
        $localKeyName = $this->getOwnerModelLocalKey();

        return $this->$localKeyName;
    }

    public function getOwnerModelRouteClassname() : string
    {
        return lcfirst(
                class_basename(
                    $this->getOwnerModelClass()
                )
            );
    }

    public function getOwnerModelRouteBasename() : string
    {
        return Str::plural(
            $this->getOwnerModelRouteClassname()
        );
    }

    public function getRouteBasenameByOwnerModel(string $action) : string
    {
        return implode(".", [
            $this->getOwnerModelRouteBasename(),
            $this->getRouteBasename(),
            $action
        ]);

    }

    public function getRouteNameByOwnerModel(string $action) : string
    {
        $routeName = $this->getRouteBasenameByOwnerModel($action);

        return route($routeName, [
            $this->getOwnerModelKey(),
            $this->getKey()
        ]);
    }

    public function getDeleteUrlByOwnerModel(array $data = [])
    {
        return $this->getRouteNameByOwnerModel('destroy');
    }

    public function getEditUrlByOwnerModel(array $data = [])
    {
        return $this->getRouteNameByOwnerModel('edit');
    }

    public function getShowUrlByOwnerModel(array $data = [])
    {
        return $this->getRouteNameByOwnerModel('show');
    }

}