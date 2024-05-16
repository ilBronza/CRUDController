<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;
use IlBronza\AccountManager\Models\User;
use Illuminate\Database\Eloquent\Model;

trait CRUDModelUserRightsTrait
{
    public static function getBaseUserRightsResult(User $user = null) : ? bool
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
        if(is_null($user))
            $user = Auth::user();

        if(! is_null($result = $this->getBaseUserRightsResult($user)))
            return $result;

        return $this->user_id == $user->getKey();
    }

    public function userCanDelete(User $user = null)
    {
        if(is_null($user))
            $user = Auth::user();

        if(! is_null($result = $this->getBaseUserRightsResult($user)))
            return $result;

        return $this->user_id == $user->getKey();
    }

    static function userCanCreate(User $user = null)
    {
        if(! is_null($result = static::getBaseUserRightsResult($user)))
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