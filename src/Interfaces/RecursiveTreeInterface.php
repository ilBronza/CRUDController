<?php

namespace IlBronza\CRUD\Interfaces;

use Illuminate\Support\Collection;

interface RecursiveTreeInterface
{
    public function getContentElements() : Collection;
    public function getRecursiveChildren() : Collection;
    public function getName() : ? string;
}