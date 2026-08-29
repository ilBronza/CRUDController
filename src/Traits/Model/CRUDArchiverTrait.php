<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;
use Carbon\Carbon;
use IlBronza\AccountManager\Models\User;
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

	public function scopeWithArchived($query)
	{
		$query->withoutGlobalScope('ArchivingScope');
	}

	public static function bootCRUDArchiverTrait()
	{
		static::addGlobalScope(new ArchivingScope);
	}

	public function scopeArchived($query)
	{
		return $query->withoutGlobalScope(ArchivingScope::class)->whereNotNull('archived_at');
	}

	public function userCanArchive(User $user = null)
	{
		return $this->userCanDelete($user);
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

	public function unarchive()
	{
		$this->archived_at = null;

		if(array_key_exists('archived_by', $this->attributes))
			$this->archived_by = null;

		$this->save();
	}

	public function getCrudBroadcastChannelsForArchived() : array
	{
		return [$this->getCrudBroadcastChannel()];
	}

	public function getCrudBroadcastChannelsForUnarchived() : array
	{
		return [$this->getCrudBroadcastChannel()];
	}

	public function getCrudBroadcastTextForArchived() : ? string
	{
		return $this->getCrudBroadcastTranslation('archived');
	}

	public function getCrudBroadcastTextForUnarchived() : ? string
	{
		return $this->getCrudBroadcastTranslation('unarchived');
	}

	/**
	 * get resource archive url
	 */
	public function getArchiveUrl(array $data = []) : string
	{
		return route('crud.archive', array_merge([
			'model' => $this->getMorphClass(),
			'key' => $this->getKey()
		], $data));
	}

	/**
	 * get resource unarchive url
	 */
	public function getUnarchiveUrl(array $data = []) : string
	{
		return route('crud.unarchive', array_merge([
			'model' => $this->getMorphClass(),
			'key' => $this->getKey()
		], $data));
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
