<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

trait CRUDNestableTrait
{
    public $maxReorderDepth = 150;

    public function getSortableElements($modelInstance) : Collection
    {
        //usare modelinstance per avere i suoi figli (per coerenza)
        return $this->getModelClass()::all();
    }

    public function getSortableUrls() : array
    {
        $modelBasename = lcfirst(
            class_basename(
                $this->getModelClass()
            )
        );

        $routeModelBasename = Str::plural($modelBasename);

        //categories.children.create, ['parent_id' => '%s']

        //controllare prima di tutto se posso creare dei children, come su ilbronza/categories, poi eventualmente dichiaro questa cosa

        // $createChildUrl = route(
        //     implode(".", [$routeModelBasename, 'children', 'create']),
        //     ['parent' => '%s']
        // );

        $result = [
            'action' => $this->getRouteUrlByType('storeReorder'),
            'reorderByUrl' => $this->getRouteUrlByType('reorder', [$modelBasename => '%s']),
            'editUrl' => $this->getRouteUrlByType('edit', [$modelBasename => '%s']),
            'createChildUrl' => $createChildUrl ?? null,
            'rootUrl' => null,
            'parentUrl' => null
        ];

        if($this->modelInstance)
        {
            $this->modelInstance->load('parent');

            $result['rootUrl'] = $this->getRouteUrlByType('reorder');

            if($this->modelInstance->{$this->modelInstance->getParentKeyName()})
            {
                $result['parentUrl'] = ($this->modelInstance) ? $this->getRouteUrlByType('reorder', [
                    $modelBasename => $this->modelInstance->{$this->modelInstance->getParentKeyName()}
                ]) : null;                
            }
        }

        return $result;
    }

    public function getMaxReorderDepth() : int
    {
        return $this->maxReorderDepth;
    }

    public function _reorder(Request $request, $modelInstance) : View
    {
        $this->modelInstance = $modelInstance;

        $flatElements = $this->getSortableElements($modelInstance);

        $elements = $this->parseTree(
            $flatElements,
            ($modelInstance)? $modelInstance->getKey() : null
        );

        //obtain action, rootUrl and ParentUrl
        extract($this->getSortableUrls());

        $maxDepth = $this->getMaxReorderDepth();

        return view('crud::nestable.index', compact('modelInstance', 'createChildUrl', 'editUrl', 'rootUrl', 'parentUrl', 'reorderByUrl', 'elements', 'action', 'maxDepth'));
    }

    //https://stackoverflow.com/questions/2915748/convert-a-series-of-parent-child-relationships-into-a-hierarchical-tree
    public function parseTree(Collection $tree, $parentId = null, $level = 0) : Collection
    {
        $return = collect();
        $level++;
        
        # Traverse the tree and search for direct children of the root
        foreach($tree as $id => $element)
        {
            # A direct child is found
            if($element->{$element->getParentKeyName()} == $parentId)
            {
                # Remove item from tree (we don't need to traverse this again)
                $tree->forget($id);

                # Append the child into result array and parse its children
                if($level <= $this->maxReorderDepth)
                    $element->childs = $this->parseTree($tree, $element->getKey(), $level);

                $return->push($element);
            }
        }
 
        return $return->sortBy('sorting_index');     
    }

    private function removeLeadingControlCharacter(string $elementId = null)
    {
        if(! $elementId)
            return $elementId;

        return substr($elementId, strlen(config('crud.nestableLeadingId')));
    }

    public function storeReorder(Request $request)
    {
        $elementId = $this->removeLeadingControlCharacter($request->element_id);
        $parentId = $this->removeLeadingControlCharacter($request->parent_id);

        if(($parentId == 0)||($parentId == ""))
            $parentId = null;

        $item = $this->getModelClass()::findOrFail($elementId);

        $item->{$item->getParentKeyName()} = $parentId;
        $item->save();

        if ($request->filled('siblings')) {
            $siblings = json_decode($request->input('siblings'));

            foreach ($siblings as $index => $sibling)
            {
                $siblingId = $this->removeLeadingControlCharacter($sibling);

                $item = $this->getModelClass()::findOrFail($siblingId);
                $item->sorting_index = $index;
                $item->save();
            }
        }
    }

}