<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;
use Carbon\Carbon;
use IlBronza\CRUD\Scopes\ArchivingScope;

trait CRUDArchiverTrait
{
    public function setArchivedAtAttribute($value)
    {
        if($value instanceof Carbon)
            return $this->attributes['archived_at'] = $value;

        if($value == 0)
            return $this->attributes['archived_at'] = null;

        if($value == 1)
            return $this->attributes['archived_at'] = Carbon::now();

        $this->attributes['archived_at'] = $value;
    }

    // public function setArchivedAtAttribute($value)
    // {
    //     if(! is_bool($value))
    //         return $this->attributes['archived_at'] = $value;

    //     if($value)
    //         return $this->attributes['archived_at'] = Carbon::now();

    //     $this->attributes['archived_at'] = null;
    // }

    public static function bootCRUDArchiverTrait()
    {
        static::addGlobalScope(new ArchivingScope);
    }

    public function scopeArchived($query)
    {
        return $query->withoutGlobalScope(ArchivingScope::class)->whereNotNull('archived_at');
    }

    public function archive(string $archiveName = null)
    {
        $this->archived_at = Carbon::now();

        if(array_key_exists('archived_by', $this->attributes))
            $this->archived_by = Auth::id();

        if($archiveName)
            $this->archive = $archiveName;

        $this->save();
    }

    /** 
     * get resource archive
     *
     * @return string
     */
    public function getArchiveUrl(array $data = [])
    {
        return $this->getKeyedRoute('archive', $data);
    }

    /**
     * Get the name of the "archived at" column.
     *
     * @return string
     */
    public function getArchivedAtColumn()
    {
        return defined('static::ARCHIVED_AT') ? static::ARCHIVED_AT : 'archived_at';
    }

    /**
     * Get the fully qualified "archived at" column.
     *
     * @return string
     */
    public function getQualifiedArchivedAtColumn()
    {
        return $this->qualifyColumn($this->getArchivedAtColumn());
    }
}