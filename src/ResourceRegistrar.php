<?php

namespace IlBronza\CRUD;

use Illuminate\Routing\ResourceRegistrar as BaseResourceRegistrar;

class ResourceRegistrar extends BaseResourceRegistrar
{
    protected $resourceDefaults = [
        'index', 'archived', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'deleted', 'archive'
    ];

    /**
     * Add the list method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceDeleted($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/deleted';

        $action = $this->getResourceAction($name, $controller, 'list', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the list method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceArchived($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/archived';

        $action = $this->getResourceAction($name, $controller, 'archived', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the list method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    public function addResourceArchive($name, $base, $controller, $options)
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name).'/{'.$base.'}/archive';

        $action = $this->getResourceAction($name, $controller, 'archive', $options);

        return $this->router->put($uri, $action);
    }
}