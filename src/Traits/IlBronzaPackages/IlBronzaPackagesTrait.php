<?php

namespace IlBronza\CRUD\Traits\IlBronzaPackages;

trait IlBronzaPackagesTrait
{
    static function getPackageConfigPrefix()
    {
        return static::$packageConfigPrefix;
    }

    public function getRoutePrefix() : ? string
    {
        return config(static::getPackageConfigPrefix() . ".routePrefix");
    }

    static function getController(string $target, string $type = null)
    {
        if($type)
            try
            {
                return config(static::getPackageConfigPrefix() . ".models.{$target}.controllers.{$type}");
            }
            catch(\Throwable $e)
            {
                dd([$e->getMessage(), 'dichiara ' . static::getPackageConfigPrefix() . ".models.{$target}.controllers.{$type}"]);
            }

        try
        {
            return config(static::getPackageConfigPrefix() . ".models.{$target}.controller");
        }
        catch(\Throwable $e)
        {
            dd([$e->getMessage(), 'dichiara ' . static::getPackageConfigPrefix() . ".models.{$target}.controller"]);
        }
    }	
}
