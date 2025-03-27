<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\FormField\FormField;
use IlBronza\Form\Form;
use IlBronza\Ukn\Facades\Ukn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

trait CRUDNestableTrait
{
    public $maxReorderDepth = 150;

    public $nestableElementViewName = 'crud::nestable.element_nestable';

    private function canReplaceElement()
    {
        return in_array('replaceElement', $this->allowedMethods);
    }

    public function getSortableElements($modelInstance) : Collection
    {
        //usare modelinstance per avere i suoi figli (per coerenza)
        return $this->getModelClass()::all();
    }

    public function getReplacingElementsListArray($modelInstance)
    {
        return $modelInstance->getParentPossibleValuesArray();
    }

    public function getStoreReoderUrl()
    {
        return $this->getRouteUrlByType('storeReorder');
    }

    public function getReorderByUrl(string $modelBasename)
    {
        return $this->getRouteUrlByType('reorder', [$modelBasename => '%s']);
    }

    public function getEditReorderUrl(array $parameters = null)
    {
        return $this->getRouteUrlByType('edit', $parameters);
    }

    public function getSortableUrls() : array
    {
        $modelBasename = lcfirst(
            class_basename(
                $this->getModel() ?? $this->getModelClass()
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
            'action' => $this->getStoreReoderUrl(),
            'reorderByUrl' => $this->getReorderByUrl($modelBasename),
            'editUrl' => $this->getEditReorderUrl([$modelBasename => '%s']),
            'createChildUrl' => $createChildUrl ?? null,
            'replaceElementUrl' => false, //viene popo0lata dopo da 'canReplaceElement'
            'rootUrl' => null,
            'parentUrl' => null
        ];

        if($this->canReplaceElement())
            $result['replaceElementUrl'] = $this->getRouteUrlByType('replaceElement', [$modelBasename => '%s']);

        if($this->modelInstance)
        {
            // $this->modelInstance->load('parent');

            try
            {
                $result['rootUrl'] = $this->getRouteUrlByType('reorder');                
            }
            catch(\Exception $e)
            {
                Ukn::e($e->getMessage());
            }

            try
            {
                if($this->modelInstance->{$this->modelInstance->getParentKeyName()})
                {
                    $result['parentUrl'] = ($this->modelInstance) ? $this->getRouteUrlByType('reorder', [
                        $modelBasename => $this->modelInstance->{$this->modelInstance->getParentKeyName()}
                    ]) : null;
                }
            }
            catch(\Exception $e)
            {
                Ukn::e($e->getMessage());
            }
        }

        return $result;
    }

    public function getMaxReorderDepth() : int
    {
        return $this->maxReorderDepth;
    }

    public function getNestableElementViewName()
    {
        return $this->nestableElementViewName;
    }

    public function getSortableElementsTree()
    {
        $flatElements = $this->getSortableElements(
            $this->modelInstance
        );

        return $this->parseTree(
            $flatElements,
            ($this->modelInstance)? $this->modelInstance->getKey() : null
        );
    }

    public function _reorder(Request $request, $modelInstance = null) : View
    {
        $this->modelInstance = $modelInstance;

        $elements = $this->getSortableElementsTree();

        //obtain action, rootUrl and ParentUrl
        extract($this->getSortableUrls());

        $maxDepth = $this->getMaxReorderDepth();

        $nestableElementViewName = $this->getNestableElementViewName();

        return view('crud::nestable.index', compact('replaceElementUrl', 'nestableElementViewName', 'modelInstance', 'createChildUrl', 'editUrl', 'rootUrl', 'parentUrl', 'reorderByUrl', 'elements', 'action', 'maxDepth'));
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
        // return "undici";
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

        return response()->json([
            'success' => true,
            'message' => 'Elemento ' . $item->getName() . ' spostato correttamente'
        ]);
    }

    public function _storeReplaceElement(Request $request, Model $model)
    {
        $request->validate([
            'target_id' => 'string'
        ]);

        $target = $this->getModelClass()::findOrFail($request->target_id);

        $model->replaceForeignRelationships($target);

        Ukn::s('Relazioni esterne riassegnate verso ' . $target->getName());

        foreach($model->children as $child)
            $child->associateParent($target);

        Ukn::s('Figli di ' . $model->getName() . ' riassegnati a ' . $target->getName());

        $model->delete();

        Ukn::s('Elemento ' . $model->getName() . ' cancellato');

        return redirect()->to(
            $this->getRouteUrlByType('reorder')
        );
    }

    public function _replaceElement(Request $request, Model $model)
    {
        $form = Form::createFromArray([
            'action' => $this->getRouteUrlByType('storeReplaceElement', [$model]),
            'method' => 'POST'
        ]);

        $form->setTitle(__('crud::nestableReplaceElementFormTitle'));
        $form->assignModel($model);

        $form->addFormField(
                FormField::createFromArray([
                    'label' => __('crud::nestableReplaceElementTargetLabel'),
                    'name' => 'target_id',
                    'type' => 'select',
                    'list' => $this->getReplacingElementsListArray(
                        $model
                    )
                ])
            );

        return view('form::uikit.form', compact('form'));
    }

}