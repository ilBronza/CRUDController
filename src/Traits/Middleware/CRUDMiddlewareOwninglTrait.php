<?php

namespace ilBronza\CRUD\Traits\Middleware;

use App\Permission;
use Closure;
use Auth;

trait CRUDMiddlewareOwninglTrait
{
    public function getModelVariableName(string $modelClass) : string
    {
        $modelPieces = explode("\\", $modelClass);
        return lcfirst(array_pop($modelPieces));
    }

    public function checkSpecificPermission(string $modelVariableName, string $permission)
    {
        $specificPermissionName = $permission . ' ' . $modelVariableName;
        $permission = Permission::where('name', $specificPermissionName)->first();

        if(($permission)&&(! $this->user->can($permission)))
            abort(403, "user doesnt have the rights to {$permission} elements like {$modelVariableName}");
    }

    public function checkSpecificOwningMethod($element, string $modelClass, string $modelVariableName, string $permission)
    {
        if(! $element->hasOwnership())
            return true;

        $methodName = 'owns'. ucfirst($modelVariableName);

        if(method_exists($this->user, $methodName))
        {
            if(! $this->user->$methodName($element))
                abort(403, "You cant {$permissionName} this element");

            return true;
        }
    }

    public function checkPermissions($request, Closure $next, $args, string $permissionName)
    {
        $this->user = Auth::user();

        if($this->user->hasRole('superadmin'))
            return $next($request);

        if(! $this->user->can($permissionName))
            abort(403, "You don't have permssions to {$permissionName} this element");

        foreach($args as $modelClass)
        {
            $modelVariableName = $this->getModelVariableName($modelClass);

            $this->checkSpecificPermission($modelVariableName, $permissionName);

            // $element = $modelClass::withTrashed()->findOrFail($request->$modelVariableName);
            $element = $request->$modelVariableName;

            if($this->checkSpecificOwningMethod($element, $modelClass, $modelVariableName, $permissionName))
                continue;

            if($element->user_id != $this->user->id)
                abort(403, "You don't own this element");
        }

        return $next($request);

    }
}