<?php

namespace IlBronza\CRUD\Traits\Model;

trait HasColorTrait
{
	static $backgroundHexField = 'hex_rgba';

	static function getBackgroundColorFieldName() : string
	{
		return static::$backgroundHexField;
	}

	public function getHexBackgroundColor() : ? string
	{
		return $this->{static::getBackgroundColorFieldName()};
	}

	public function getCssBackgroundColorValue() : ? string
	{
		if(! $value = $this->getHexBackgroundColor())
			return null;

		if(strpos($value, '#') !== false)
			return $value;

		return "#{$value}";
	}

	public function getCssTextColorValue() : ? string
	{
		if(! $backgroundColor = $this->getHexBackgroundColor())
			return null;

		$backgroundColor = str_replace('#', '', $backgroundColor);

		if(strlen($backgroundColor) == 6)
		{
			$r = hexdec(substr($backgroundColor, 0, 2));
			$g = hexdec(substr($backgroundColor, 2, 2));
			$b = hexdec(substr($backgroundColor, 4, 2));
		}
		elseif(strlen($backgroundColor) == 3)
		{
			$r = hexdec(substr($backgroundColor, 0, 1) . substr($backgroundColor, 0, 1));
			$g = hexdec(substr($backgroundColor, 1, 1) . substr($backgroundColor, 1, 1));
			$b = hexdec(substr($backgroundColor, 2, 1) . substr($backgroundColor, 2, 1));
		}
		else
			return null;

		$brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return ($brightness > 125) ? '#000000' : '#FFFFFF';
	}
}