<?php

namespace AKL;

class Envoy
{
	public static $provider;

	public static function start()
	{
	    if (!isset(self::$provider)) {
	        self::$provider = new DataProvider();
	    }
	}

	public static function set( $key, $data, $group = 'default' )
	{
		self::start();
		return self::$provider->setValue( $key, $data, $group );
	}

	public static function setValues( $arr, $group = 'default' )
	{
		self::start();
		return self::$provider->setValues( $arr, $group );
	}

	public static function only( $limiterArray = [], $group = 'default' )
	{
		self::start();
		if( empty($limiterArray) ) return self::group($group);

		return self::$provider->getLimited( $limiterArray, $group );
	}

	public static function group($name)
	{
		self::start();
		return self::$provider->getGroup($name);
	}

	public static function get( $key, $group = 'default' )
	{
		self::start();
		return self::$provider->getValue( $key, $group );
	}

	public static function provide( $includePath, $data )
	{
		extract($data);

		include $includePath;
	}
}