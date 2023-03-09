<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;
use IlBronza\AccountManager\Models\User;
use Illuminate\Database\Eloquent\Model;

trait CRUDModelUserRightsTrait
{
    public static function getBaseUserRightsResult() : ? bool
    {
        if(! $user = Auth::user())
            return false;

        if($user->isSuperadmin())
            return true;

        if($user->hasRole('administrator'))
            return true;

        return null;
    }

    public function userCanUpdate(User $user = null)
    {
        if(! is_null($result = $this->getBaseUserRightsResult()))
            return $result;

        return $this->user_id == $user->getKey();
    }

    static function userCanCreate(User $user = null)
    {
        if(! is_null($result = static::getBaseUserRightsResult()))
            return $result;

        return false;
    }

    public function userCanSee(User $user = null)
    {
        if(Auth::guest())
            return false;

        return true;
    }

    public function userCanSeeTeaser(User $user = null)
    {
        if(Auth::guest())
            return false;

        return true;
    }

    public function owns(Model $model)
    {
        if(Auth::user()->isSuperadmin())
            return true;

        $owningMethod = $this->getOwningMethod($model);
        if(method_exists($this, $owningMethod))
            return $this->$owningMethod($model);

        if($model->{$this->getForeignKey()} == $this->getKey())
            return true;

        return false;
    }

    public function getOwningMethod(Model $model)
    {
        return  'owns' . class_basename($model);
    }

}