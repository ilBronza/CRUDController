<?php

namespace IlBronza\CRUD;

use Illuminate\Routing\ResourceRegistrar as BaseResourceRegistrar;

class ResourceRegistrar extends BaseResourceRegistrar
{
    protected $resourceDefaults = [
        'index', 'archived', 'create', 'createEmpty', 'store', 'show', 'edit', 'update', 'destroy', 'deleted'
    ];

    /**
     * Add the endpoint used by the optional index `createEmpty` button.
     */
    public function addResourceCreateEmpty($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/create-empty';

        $action = $this->getResourceAction($name, $controller, 'createEmpty', $options);

        return $this->router->post($uri, $action);
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
     * Add the bulk delete method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    // public function addResourceBulkDelete($name, $base, $controller, $options)
    // {
    //     $uri = $this->getResourceUri($name).'/bulk-delete';

    //     $action = $this->getResourceAction($name, $controller, 'bulkDelete', $options);

    //     return $this->router->post($uri, $action);
    // }
}
