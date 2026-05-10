<?php

namespace IlBronza\CRUD\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveScope implements Scope
{
    public const NAME = 'active';

    /**
     * True while resolving shouldApplyActiveScope(). Avoids recursion when Auth::user()
     * resolves the authenticated model with a nested query subject to ActiveScope again.
     */
    protected static bool $resolvingApplyDecision = false;

    /**
     * @var string[]
     */
    protected $extensions = ['WithInactive', 'OnlyInactive'];

    public function apply(Builder $builder, Model $model): void
    {
        if (! static::mustApplyConstraint($model)) {
            return;
        }

        $builder->where(static::qualifiedActiveColumn($model), true);
    }

    protected static function mustApplyConstraint(Model $model): bool
    {
        $class = $model::class;

        if (! method_exists($class, 'shouldApplyActiveScope')) {
            return true;
        }

        if (static::$resolvingApplyDecision) {
            return true;
        }

        static::$resolvingApplyDecision = true;

        try {
            return forward_static_call([$class, 'shouldApplyActiveScope']);
        } finally {
            static::$resolvingApplyDecision = false;
        }
    }

    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    public static function qualifiedActiveColumn(Model $model): string
    {
        if (method_exists($model, 'getQualifiedActiveColumn')) {
            return $model->getQualifiedActiveColumn();
        }

        return $model->qualifyColumn('active');
    }

    protected function addWithInactive(Builder $builder): void
    {
        $name = static::NAME;

        $builder->macro('withInactive', function (Builder $builder) use ($name) {
            return $builder->withoutGlobalScope($name);
        });
    }

    protected function addOnlyInactive(Builder $builder): void
    {
        $name = static::NAME;

        $builder->macro('onlyInactive', function (Builder $builder) use ($name) {
            $column = ActiveScope::qualifiedActiveColumn($builder->getModel());

            return $builder->withoutGlobalScope($name)->where($column, false);
        });
    }
}
