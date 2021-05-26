<?php

namespace IlBronza\CRUD\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ArchivingScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var string[]
     */
    protected $extensions = ['Unarchive', 'WithArchived', 'WithoutArchived', 'OnlyArchived'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereNull($model->getQualifiedArchivedAtColumn());
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension)
            $this->{"add{$extension}"}($builder);

        $builder->onDelete(function (Builder $builder)
        {
            $column = $this->getDeletedAtColumn($builder);

            return $builder->update([
                $column => $builder->getModel()->freshTimestampString(),
            ]);
        });
    }

    /**
     * Get the "deleted at" column for the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return string
     */
    protected function getDeletedAtColumn(Builder $builder)
    {
        if (count((array) $builder->getQuery()->joins) > 0)
            return $builder->getModel()->getQualifiedArchivedAtColumn();

        return $builder->getModel()->getDeletedAtColumn();
    }

    /**
     * Add the unarchive extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addUnarchive(Builder $builder)
    {
        $builder->macro('unarchive', function (Builder $builder)
        {
            $builder->withArchived();

            return $builder->update([$builder->getModel()->getArchivedAtColumn() => null]);
        });
    }

    /**
     * Add the with-archived extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithArchived(Builder $builder)
    {
        $builder->macro('withArchived', function (Builder $builder, $withArchived = true)
        {
            if (! $withArchived)
                return $builder->withoutArchived();

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-archived extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithoutArchived(Builder $builder)
    {
        $builder->macro('withoutArchived', function (Builder $builder)
        {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNull(
                $model->getQualifiedArchivedAtColumn()
            );

            return $builder;
        });
    }

    /**
     * Add the only-archived extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlyArchived(Builder $builder)
    {
        $builder->macro('onlyArchived', function (Builder $builder)
        {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNotNull(
                $model->getQualifiedArchivedAtColumn()
            );

            return $builder;
        });
    }
}
