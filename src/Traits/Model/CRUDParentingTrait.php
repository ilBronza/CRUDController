<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait CRUDParentingTrait
{
    public $parentingTrait = true;

    public function replaceForeignRelationships()
    {
        throw new \Exception(
            'Dichiara il metodo public replaceForeignRelationships() nel model ' . class_basename($this) . ' per far sì che quando sostituisci un elemento con un altro, tutti i riferimenti esterni necessari vengano puntati correttamente. Esempio i modelli azionamenti di sael devono spostare tutti gli azionamenti verso il nuovo modello'
        );
    }

    static function getParentKeyName()
    {
        return static::$parentKeyName ?? 'parent_id';
    }

    public function scopeRoot($query)
    {
        return $query->whereNull(static::getParentKeyName());
    }

    public function parent()
    {
        return $this->belongsTo(static::class, static::getParentKeyName());
    }

    public function getParent() : ? Model
    {
        return $this->parent;
    }

    public function associateParent(Model $parent)
    {
        $this->parent()->associate($parent);
        $this->save();
    }

    public function children()
    {
        return $this->hasMany(static::class, static::getParentKeyName());
    }

    public function getChildren() : Collection
    {
        return $this->children;
    }

    public function getRecursiveChildren() : Collection
    {
        return $this->recursiveChildren;
    }

    public function recursiveChildren()
    {
        return $this->children()->with('recursiveChildren');
    }

    public function recursiveParents()
    {
        return $this->parent()->with('recursiveParents');
    }

    public function getRoot() : ? static
    {
        if($this->isRoot())
            return $this;

        $this->recursiveParents()->get();

        $element = $this;

        while($element = $element->parent)
            if($element->isRoot())
                return $element;

        return null;
    }

    static public function getRoots() : Collection
    {
        return cache()->remember(
            static::staticCacheKey('getRoots'),
            3600,
            function()
            {
                return static::root()->get();                
            }
        );
    }

    public function getRootAncestor() : ? static
    {
        return $this->getRoot();
        // if($this->isRoot())
        //     return $this;

        // $this->recursiveParents()->get();

        // $element = $this;

        // while($element = $element->parent)
        //     if($element->isRoot())
        //         return $element;

        // return null;
    }

    public function getTree()
    {
        return cache()->remember(
            $this->cacheKey('tree'),
            3600,
            function()
            {
                $this->load('recursivechildren');

                return $this;
            });
    }

    static function getFullTreeWithRelatedElements(string $key, array $related) : static
    {
        $baseElement = static::findOrFail($key);

        $rootElement = $baseElement->getRootAncestor();

        return static::getChildTreeWithRelatedElements($rootElement->getKey(), $related);
    }

    static function getChildTreeWithRelatedElements(string $key, array $related) : static
    {
        return static::with($related)
                ->withRecursiveChildrenRelated($related)
                ->findOrFail($key);
    }

    public function scopeWithRecursiveChildrenRelated($query, array $relatedTypes)
    {
        $query->with(['recursiveChildren' => function($_query) use ($relatedTypes)
        {
            $_query->with($relatedTypes);

            $_query->getQuery()->withRecursiveChildrenRelated($relatedTypes);
        }]);
    }

    public function scopeWithRecursiveParentRelated($query, array $relatedTypes)
    {
        $query->with(['recursiveParents' => function($_query) use ($relatedTypes)
        {
            $_query->with($relatedTypes);

            $_query->getQuery()->withRecursiveParentRelated($relatedTypes);
        }]);
    }

    public function scopeWithRecursiveParents($query)
    {
        $query->with(['parent' => function($_query)
        {
            $_query->getQuery()->withRecursiveParents();
        }]);
    }

    static function staticGetTree(string $node = null)
    {
        $cacheKey = Str::slug(static::class) . 'Tree' . $node;

        return cache()->remember(
            $cacheKey,
            3600,
            function() use($node)
            {
                if(! $node)
                    return static::root()->with('recursivechildren')->get();

                return static::where('node', $node)->with('recursivechildren')->get();
            });
    }

    public function getElementsFlatTree(int $level = 0, string $name = null)
    {
        $result = collect();

        $result->push($this);

        foreach($this->recursiveChildren as $child)
            $result = $result->merge(
                $child->getElementsFlatTree($level + 1, $name)
            );

        return $result;



        // $result = collect();

        // if($name)
        //     $name .= ' || ';

        // $name = $name . $this->name;

        // $result->push([
        //     'id' => $this->getKey(),
        //     'name' => $name
        // ]);

        // foreach($this->recursiveChildren as $child)
        //     $result = $result->merge(
        //         $child->getElementsFlatTree($level + 1, $name)
        //     );

        // return $result;
    }

    static function getFlatTree(string $node = null, bool $noCache = false)
    {
        $cacheKey = Str::slug(static::class) . 'FlatTree' . $node;

        if($noCache)
            cache()->forget($cacheKey);

        return cache()->remember(
            $cacheKey,
            36000,
            function() use($node)
            {
                $elements = static::staticGetTree($node);
                $result = collect();

                foreach($elements as $element)
                {
                    $result = $result->merge(
                        $element->getElementsFlatTree()
                    );
                }

                return $result;
            });
    }

    private function collectRecursiveParentNames()
    {
        $pieces = [$this->name];

        if($this->recursiveParents)
            $pieces[] = $this->recursiveParents->collectRecursiveParentNames();

        return implode("|", $pieces);

    }

    public function getRecursiveParentsString()
    {
        $this->loadMissing('recursiveParents');

        if($this->recursiveParents)
            return $this->collectRecursiveParentNames();
    }

    public function isChild()
    {
        return !! $this->{static::getParentKeyName()};
    }

    public function isRoot()
    {
        return empty($this->{static::getParentKeyName()});
    }

    public function getParentPossibleValuesArray() : array
    {
        $result = static::select($this->getKeyName(), $this->getNameFieldName())->where($this->getKeyName(), '!=', $this->getKey())->get();

        return $result->pluck($this->getNameFieldName(), $this->getKeyName())->toArray();
    }

    public function getBrothers() : Collection
    {
        if(! $this->{static::getParentKeyName()})
            return collect();

        return static::where(static::getParentKeyName(), $this->{static::getParentKeyName()})
                ->where($this->getKeyName(), '!=', $this->getKey())
                ->get();
    }

    public function getBrothersByField(string $field) : Collection
    {
        return static::where($field, $this->{$field})
                ->where($this->getKeyName(), '!=', $this->getKey())
                ->get();        
    }

    public function getInheritedAttribute(string $attributeName)
    {
        if($this->{$attributeName} !== null)
            return $this->{$attributeName};

        if($this->parent)
            return $this->parent->getInheritedAttribute($attributeName);

        return null;
    }
}