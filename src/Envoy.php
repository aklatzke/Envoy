<?php

namespace AKL;

class Envoy
{
	public static $provider;
	/**
	 * 'Starts' the singleton
	 * @return boolean 			Returns success value
	 */
	public static function start()
	{
	    if (!isset(self::$provider)) {
	        self::$provider = new DataProvider();

	        return true;
	    }

	    return false;
	}
	/**
	 * Sets a value on a data object
	 * @param string $key   		Key for the data in $group group
	 * @param mixed $data  		Data to be stored
	 * @param string $group 		Group to add the data to ('default' if unprovided)
	 */
	public static function set( $key, $data, $group = 'default' )
	{
		self::start();
		return self::$provider->setValue( $key, $data, $group );
	}
	/**
	 * Updates the data object to contain all
	 * key/value pairs from $arr
	 * @param array $arr   			The hash values to be added
	 * @param string $group 		The target group
	 * @return array 			Returns the data provided in $arr
	 */
	public static function setValues(Array $arr, $group = 'default' )
	{
		self::start();
		return self::$provider->setValues( $arr, $group );
	}
	/**
	 * Returns ONLY the values specified in the $limiterArray from target $group
	 * @param  array $limiterArray 	List of key names present in $group
	 * @param  string $group        		Target group
	 * @return array  			Returns the array of values
	 */
	public static function only( Array $limiterArray = [], $group = 'default' )
	{
		self::start();
		if( empty($limiterArray) ) return self::group($group);

		return self::$provider->getLimited( $limiterArray, $group );
	}
	/**
	 * Combines two arrays, with the limiter option to only get certain keys
	 * @param  array  $groupNames	List of group names (strings)
	 * @param  array $keys       		List of keys to limit zip to (strings)
	 * @return  array             		'Zipped' final values, limited if provided
	 */
	public static function zip( Array $groupNames, $keys = [] )
	{
		$toZip = self::groups($groupNames);

		$zipped = call_user_func_array( 'array_merge_recursive', $toZip );

		if( ! empty($keys) )
		{
			$limited = [];

			foreach( $keys as $key ) $limited[$key] = $zipped[$key];

			return $limited;
		}

		return $zipped;
	}
	/**
	 * Returns an array of two groups' intersecting keys
	 * @param  array  $groupNames 	List of group names (strings)
	 * @return array             			Array of intersecting keys
	 */
	public static function shared( Array $groupNames )
	{
		$arrays = self::groups($groupNames);

		$shared = call_user_func_array( 'array_intersect_key', $arrays );

		return $shared;
	}
	/**
	 * Checks if a group exists/is defined
	 * @param  string $groupName 	Name of the searched group
	 * @return  boolean			Does the group exist?
	 */
	public static function exists( $groupName )
	{
		return self::$provider->hasGroup( $groupName );
	}
	/**
	 * Returns a single group based on name
	 * @param  string $name
	 * @return  array
	 */
	public static function group($name)
	{
		return self::$provider->getGroup($name);
	}
	/**
	 * Fetches the correct groups from a list of names
	 * @param  array  $groupNames
	 * @return  array
	 */
	public static function groups( Array $groupNames )
	{
		return array_filter(array_map( function( $name ){
			if( self::exists($name) )
				return self::group($name);
			else
				return false;
		}, $groupNames ));
	}
	/**
	 * Gets a single value based on key, from a specific group or default
	 * @param  string $key			The data item to retrieve
	 * @param  string $group 		The target group
	 * @return  mixed        			The requested data item
	 */
	public static function get( $key, $group = 'default' )
	{
		return self::$provider->getValue( $key, $group );
	}
	/**
	 * Provides the variables in $data to the included file
	 * @param  string $includePath 	Path to include file
	 * @param  array $data        		Should be a key/value hash
	 * @return  NULL
	 */
	public static function provide( $includePath, $data )
	{
		extract($data);

		include $includePath;
	}
}