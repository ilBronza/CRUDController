<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDModelsSequenceTrait
{
    public function scopeStartingElement($query)
    {
        return $query->whereNull('previous_id');
    }

    public function previous()
    {
        return $this->belongsTo(static::class, 'previous_id');
    }

    public function setPrevious($previous)
    {
        $this->setPreviousKey($previous);

        $this->save();
    }

    public function setPreviousKey($previous)
    {
        $this->previous_id = $previous->getKey();
    }

    public function setNext($next, bool $save = true)
    {
        $this->setNextKey($next);

        if($save)
            $this->save();
    }

    public function setNextKey($next)
    {
        $this->next_id = $next->getKey();
    }

    public function next()
    {
        return $this->hasMany(static::class, 'next_id');
    }

    public function recursiveNext()
    {
        return $this->next()->with('recursiveNext');
    }    

    public function recursivePrevious()
    {
        return $this->previous()->with('recursivePrevious');
    }

    public function getSequenceTree()
    {
        return cache()->remember(
            $this->cacheKey('sequenceTree'),
            3600,
            function()
            {
                $this->load('recursiveNext');

                return $this;
            });
    }

    static function staticGetSequenceTree(string $node = null)
    {
        $cacheKey = str_slug(static::class) . 'SequenceTree' . $node;

        return cache()->remember(
            $cacheKey,
            3600,
            function() use($node)
            {
                if(! $node)
                    return static::root()->with('recursiveNext')->get();

                return static::where('node', $node)->with('recursiveNext')->get();
            });
    }

    public function getElementsFlatSequenceTree(int $level = 0, string $name = null)
    {
        $result = collect();

        if($name)
            $name .= ' || ';

        $name = $name . $this->name;

        $result->push([
            'id' => $this->getKey(),
            'name' => $name
        ]);

        foreach($this->recursiveNext as $child)
            $result = $result->merge(
                $child->getElementsFlatSequenceTree($level + 1, $name)
            );

        return $result;
    }

    static function getFlatSequenceTree(string $node = null, bool $noCache = false)
    {
        $cacheKey = str_slug(static::class) . 'FlatSequenceTree' . $node;

        if($noCache)
            cache()->forget($cacheKey);

        return cache()->remember(
            $cacheKey,
            36000,
            function() use($node)
            {
                $elements = static::staticGetSequenceTree($node);
                $result = collect();

                foreach($elements as $element)
                {
                    $result = $result->merge(
                        $element->getElementsFlatSequenceTree()
                    );
                }

                return $result;
            });
    }

    private function collectRecursivePreviousNames()
    {
        $pieces = [$this->name];

        if($this->recursivePrevious)
            $pieces[] = $this->recursivePrevious->collectRecursivePreviousNames();

        return implode("|", $pieces);

    }

    public function getRecursivePreviousString()
    {
        $this->loadMissing('recursivePrevious');

        if($this->recursivePrevious)
            return $this->collectRecursivePreviousNames();
    }

    public function isNext()
    {
        return !! $this->previous_id;
    }

    public function isStartingElement()
    {
        return empty($this->previous_id);
    }
}