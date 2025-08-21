<?php

namespace IlBronza\CRUD;

use function route;

class CRUDMenuUtilities
{
    public function manageMenuButtons()
    {
        if(! $menu = app('menu'))
            return;

        $settings = $menu->provideButton([
                'text' => 'generals.settings',
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