<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Support\Collection;

trait CRUDParentingTrait
{
    public $parentingTrait = true;

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function recursiveChildren()
    {
        return $this->children()->with('recursiveChildren');
    }    

    public function recursiveParents()
    {
        return $this->parent()->with('recursiveParents');
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

    static function staticGetTree(string $node = null)
    {
        $cacheKey = str_slug(static::class) . 'Tree' . $node;

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

        if($name)
            $name .= ' || ';

        $name = $name . $this->name;

        $result->push([
            'id' => $this->getKey(),
            'name' => $name
        ]);

        foreach($this->recursiveChildren as $child)
            $result = $result->merge(
                $child->getElementsFlatTree($level + 1, $name)
            );

        return $result;
    }

    static function getFlatTree(string $node = null, bool $noCache = false)
    {
        $cacheKey = str_slug(static::class) . 'FlatTree' . $node;

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
        return !! $this->parent_id;
    }

    public function isRoot()
    {
        return empty($this->parent_id);
    }

    public function getParentPossibleValuesArray() : array
    {
        $result = static::select($this->getKeyName(), $this->getNameFieldName())->where($this->getKeyName(), '!=', $this->getKey())->get();

        return $result->pluck($this->getNameFieldName(), $this->getKeyName())->toArray();
    }

    public function getBrothers() : Collection
    {
        if(! $this->parent_id)
            return collect();

        return static::where('parent_id', $this->parent_id)
                ->where($this->getKeyName(), '!=', $this->getKey())
                ->get();
    }

    public function getBrothersByField(string $field) : Collection
    {
        return static::where($field, $this->{$field})
                ->where($this->getKeyName(), '!=', $this->getKey())
                ->get();        
    }
}