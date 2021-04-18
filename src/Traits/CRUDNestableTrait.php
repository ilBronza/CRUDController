<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

trait CRUDNestableTrait
{
    public $maxReorderDepth = 150;

    public function getSortableElements($modelInstance) : Collection
    {
        //usare modelinstance per avere i suoi figli (per coerenza)
        return $this->modelClass::all();
    }

    public function getSortableUrls() : array
    {
        $modelBasename = lcfirst((class_basename($this->modelClass)));

        $result = [
            'action' => $this->getRouteUrlByType('stroreReorder'),
            'reorderByUrl' => $this->getRouteUrlByType('reorder', [$modelBasename => '%s']),
            'rootUrl' => null,
            'parentUrl' => null
        ];

        if($this->modelInstance)
        {
            $this->modelInstance->load('parent');

            $result['rootUrl'] = $this->getRouteUrlByType('reorder');

            if($this->modelInstance->parent_id)
            {
                $result['parentUrl'] = ($this->modelInstance) ? $this->getRouteUrlByType('reorder', [
                    $modelBasename => $this->modelInstance->parent_id
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

        return view('crud::nestable.index', compact('modelInstance', 'rootUrl', 'parentUrl', 'reorderByUrl', 'elements', 'action', 'maxDepth'));
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
            if($element->parent_id == $parentId)
            {
                # Remove item from tree (we don't need to traverse this again)
                $tree->forget($id);
                // dd($tree);
                # Append the child into result array and parse its children
                if($level <= $this->maxReorderDepth)
                    $element->childs = $this->parseTree($tree, $element->getKey(), $level);

                $return->push($element);
            }
        }
 
        return $return->sortBy('sorting_index');     
    }

    public function stroreReorder(Request $request)
    {
        if ($request->filled('parent_id')) {
            if($request->filled('element_id')){
                if(0 == $parentId = $request->input('parent_id'))
                    $parentId = null;

                $item = $this->modelClass::findOrFail($request->input('element_id'));
                $item->parent_id = $parentId;
                $item->save();
            }
        }

        if ($request->filled('siblings')) {
            $siblings = json_decode($request->input('siblings'));
            foreach ($siblings as $index => $sibling) {
                $item = $this->modelClass::findOrFail($sibling);
                $item->sorting_index = $index;
                $item->save();
            }
        }
    }

}