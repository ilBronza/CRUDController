<?php

namespace IlBronza\CRUD\Traits\Model;

trait UsesArchive
{
    public function initializeUsesArchive(): void
    {
        $this->casts[$this->getArchivedColumn()] = 'string';
    }

    public function getArchivedColumn(): string
    {
        return defined('static::ARCHIVED') ? static::ARCHIVED : 'archived';
    }

    public function getQualifiedArchivedColumn(): string
    {
        return $this->qualifyColumn($this->getArchivedColumn());
    }

    public function isArchived(): bool
    {
        return ! is_null($this->{$this->getArchivedColumn()});
    }

    public function archive(string $archive): bool
    {
        $this->{$this->getArchivedColumn()} = $archive;

        return $this->save();
    }

    public function scopeNotArchived($query)
    {
        $model = $query->getModel();

        return $query->whereNull($model->getQualifiedArchivedColumn());
    }

    public function scopeArchived($query)
    {
        $model = $query->getModel();

        return $query->whereNotNull($model->getQualifiedArchivedColumn());
    }

    public function scopeByArchive($query, string $archivio)
    {
        $model = $query->getModel();

        return $query->where($model->getQualifiedArchivedColumn(), $archivio);
    }
}
