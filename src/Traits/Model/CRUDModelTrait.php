<?php

namespace IlBronza\CRUD\Traits\Model;

use App\Models\User;
use App\Providers\Helpers\dgButton;
use Auth;
use IlBronza\CRUD\Traits\Model\CRUDDeleterTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;

trait CRUDModelTrait
{
    use LogsActivity;
    use CRUDDeleterTrait;

    // public function hasOwnership()
    // {
    //     if(isset($this->hasOwnership))
    //         return $this->hasOwnership;

    //     return true;
    // }

    public function getBrowserTitle()
    {
        return $this->getName();
    }

    public function getOwningMethod(Model $model)
    {
        return  'owns' . class_basename($model);
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

    public static function getPluralCamelcaseClassBasename()
    {
        return Str::plural(lcfirst(class_basename(static::class)));
    }

    static function getCreateButton(array $routeParameters = [])
    {
        $href = route(
            static::getPluralCamelcaseClassBasename() . '.create',
            $routeParameters
        );

        $text = trans('generals.create' . class_basename(static::class));

        return new dgButton($href, $text, 'plus');
    }

    static function getCreateChildButton(Model $model)
    {
        $singularCamelModel = Str::camel(class_basename($model));
        $pluralRouteModel = Str::plural($singularCamelModel);

        $href = route(
            implode(".", [
                $pluralRouteModel,
                static::getPluralCamelcaseClassBasename(),
                'create'
            ]),
            [$singularCamelModel => $model->getKey()]
        );

        $text = trans('generals.create' . class_basename(static::class));

        return new dgButton($href, $text, 'plus');
    }

    private function getRouteClassname()
    {
        if($this->routeClassname ?? false)
            return $this->routeClassname;

        return lcfirst(class_basename($this));
    }

    public function getRouteBasename()
    {
        if($this->routeBasename ?? false)
            return $this->routeBasename;

        $className = $this->getRouteClassname();

        return Str::plural($className);
    }

	private function getKeyedRoute(string $action, array $data)
	{
        $routeBasename = $this->getRouteBasename();
        $routeClassname = $this->getRouteClassname();

        if(class_basename($this) == 'Filecabinetrow')
            mori($this);

        return route($routeBasename . '.' . $action, [$routeClassname => $this->getKey()], $data);
	}

    public function getShowUrl(array $data = [])
    {
    	return $this->getKeyedRoute('show', $data);
    }

    public function getEditUrl(array $data = [])
    {
    	return $this->getKeyedRoute('edit', $data);
    }

    public function getDestroyUrl()
    {
        return $this->getKeyedRoute('forceDelete', $data);
    }

    public function getDeleteUrl(array $data = [])
    {
        return $this->getKeyedRoute('destroy', $data);
    }

    public function getName()
    {
        return $this->{static::$nameField ?? 'name'};
    }

    public function userCanUpdate(User $user = null)
    {
        if(! $user)
            return false;

        if($user->hasRole('administrator'))
            return true;

        return $this->user_id == $user->getKey();
    }

    static function userCanCreate(User $user = null)
    {
        if(! $user)
            return false;

        if($user->hasRole('administrator'))
            return true;

        return false;
    }

    public function userCanSee(User $user = null)
    {
        return true;
    }
}