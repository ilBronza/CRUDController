<?php

namespace ilBronza\CRUD\Traits\Model;

use App\User;
use Illuminate\Support\Str;

trait CRUDModelTrait
{
    public function getDeleteUrl()
    {
        return route(
            static::getPluralCamelcaseClassBasename() . '.delete', [$this]
        );
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

        return new \dgButton($href, $text, 'plus');
    }

	private function getKeyedRoute(string $action, array $data)
	{
        $className = lcfirst(class_basename($this));

        return route(Str::plural($className) . '.' . $action, [$className => $this->getKey()], $data);
	}

    public function getShowUrl(array $data = [])
    {
    	return $this->getKeyedRoute('show', $data);
    }

    public function getEditUrl(array $data = [])
    {
    	return $this->getKeyedRoute('edit', $data);
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

    public function userCanSee(User $user = null)
    {
        return true;
    }
}