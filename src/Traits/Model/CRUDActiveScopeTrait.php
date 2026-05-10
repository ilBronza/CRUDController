<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Scopes\ActiveScope;

trait CRUDActiveScopeTrait
{
    public static function bootCRUDActiveScopeTrait(): void
    {
        static::addGlobalScope(ActiveScope::NAME, new ActiveScope);
    }

    /**
     * When false is returned the global ActiveScope does not constrain the query.
     *
     * Nested queries during resolution (e.g. Auth resolving the authenticated user model)
     * do not recurse into this hook: ActiveScope applies the constraint for those reads.
     *
     * @return bool Override on specific models when needed (e.g. bypass for admins).
     */
    public static function shouldApplyActiveScope(): bool
    {
        return true;
    }

    /**
     * Get the name of the "active" column.
     */
    public function getActiveColumn(): string
    {
        return defined('static::ACTIVE') ? static::ACTIVE : 'active';
    }

    /**
     * Get the fully qualified "active" column.
     */
    public function getQualifiedActiveColumn(): string
    {
        return $this->qualifyColumn($this->getActiveColumn());
    }

    public function scopeWithInactive($query)
    {
        return $query->withoutGlobalScope(ActiveScope::NAME);
    }

    public function scopeOnlyInactive($query)
    {
        $model = $query->getModel();

        return $query->withoutGlobalScope(ActiveScope::NAME)
            ->where($model->getQualifiedActiveColumn(), false);
    }
}
