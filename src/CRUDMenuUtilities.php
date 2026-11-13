<?php

namespace IlBronza\CRUD;

use IlBronza\CRUD\ResourceFilters\ResourceFilters;

use function route;

class CRUDMenuUtilities
{
    public function manageMenuButtons()
    {
        if(! $menu = app('menu'))
            return;

        if ($navbar = config('resource-filters.navbar'))
            foreach (app(ResourceFilters::class)->menuButtons() as $button)
                $button->addToNavbar($menu->provideNavbar($navbar));

        $settings = $menu->provideButton([
                'text' => 'menu::menu.settings',
                'name' => 'settings',
                'icon' => 'gear',
                'roles' => ['administrator']
            ]);

        $cacheButton = $menu->createButton([
            'name' => 'cacheButton',
            'icon' => 'rotate-left',
            'text' => 'crud::crud.cache',
        ]);

	    $cacheButton->addChild($menu->createButton([
		    'name' => 'cacheClear',
		    'icon' => 'rotate-left',
		    'text' => 'crud::crud.clearCache',
		    'href' => route('cache.clear')
	    ]));

		if(config('crud.cache.highlightClickedLinks.enabled', false))
		    $cacheButton->addChild($menu->createButton([
			    'name' => 'clickedLinksClear',
			    'icon' => 'rotate-left',
			    'text' => 'crud::crud.clearClickedLinksCache',
			    'href' => '#',
		    ]));

	    $cacheButton->addChild($menu->createButton([
            'name' => 'serverCacheClear',
            'icon' => 'rotate-left',
            'text' => 'crud::crud.serverClearCache',
            'href' => route('servercache.clear')
        ]));

		$settings->addChild($cacheButton);
    }
}