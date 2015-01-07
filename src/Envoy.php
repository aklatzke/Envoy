<?php

namespace AKL;

use AKL\Envoy\DataRepository as DataRepository;
use AKL\Envoy\SecurityRepository as SecurityRepository;
use AKL\Envoy\Security as SecurityObject;
use AKL\Envoy\SecurityService as SecurityService;

class Envoy
{
	public static $dataRepository;
	public static $securityRepository;
	/**
	 * 'Starts' the singleton
	 * @return boolean 			Returns success value
	 */
	public static function start()
	{
	    if ( ! isset( self::$dataRepository ) ) {
	        self::$dataRepository = new DataRepository();
	    }

	   if ( ! isset( self::$securityRepository ) ) {
	        self::$securityRepository = new SecurityRepository();

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
	public static function set( $key, $data, $group = 'default', $password = '', $heavyEncryption = false )
	{
		self::start();

		self::protectIfNecessary( $group, $password, $heavyEncryption );

		return self::$dataRepository->setValue( $key, $data, $group );
	}
	/**
	 * Updates the data object to contain all
	 * key/value pairs from $arr
	 * @param array $arr   			The hash values to be added
	 * @param string $group 		The target group
	 * @return array 			Returns the data provided in $arr
	 */
	public static function setValues( Array $arr, $group = 'default', $password = '', $heavyEncryption = false )
	{
		self::start();

		self::protectIfNecessary( $group, $password, $heavyEncryption );

		return self::$dataRepository->setValues( $arr, $group );
	}

	/**
	 * Returns ONLY the values specified in the $limiterArray from target $group
	 * @param  array $limiterArray 	List of key names present in $group
	 * @param  string $group        		Target group
	 * @return array  			Returns the array of values
	 */
	public static function only( Array $limiterArray, $groupName = 'default', $password = '' )
	{
		self::start();

		if( empty($limiterArray) ) return self::group($groupName, $password);

		return self::$dataRepository->getLimited( $limiterArray, $groupName, $password );
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
	 * @param  string $password 		Password for the group, will return as if it doesn't exist unless key matches
	 * @return  boolean			Does the group exist?
	 */
	public static function exists( $groupName )
	{
		return self::$dataRepository->hasGroup( $groupName );
	}
	/**
	 * Checks if group both exists and is public
	 * @param  string  $groupName   	Name of the searched group
	 * @return boolean
	 */
	public static function isAccessibleGroup( $groupName )
	{
		return self::exists( $groupName ) && ! self::$securityRepository->isPrivateGroup( $groupName );
	}
	/**
	 * Checks if the group is private
	 * @param  string  $groupName 	Name of the searched group
	 * @return boolean            		Is the group private?
	 */
	public static function isPrivateGroup( $groupName )
	{
		return self::$securityRepository->isPrivateGroup( $groupName );
	}
	/**
	 * Returns a single group based on name
	 * @param  string $name
	 * @return  array
	 */
	public static function group( $groupName, $key = '' )
	{
		self::start();

		if( ! self::isPrivateGroup( $groupName ) ) return self::$dataRepository->getGroup( $groupName );

		if( $key !== '' && self::$securityRepository->check(  $groupName, $key ) )
			return self::$dataRepository->getGroup( $groupName );
	}
	/**
	 * Protects the object and secures it behind a password
	 * @param  string $groupName 		Name of the secured group
	 * @param  string $password  			The password for the group
	 * @param  boolean $simple    			Flag for whether to use heavier encryption.
	 *                               				Setting this to 'true' will use bcrypt()
	 *                               				rather than uuid() for the password hashing.
	 *                               				This is inverted to make more sense as a
	 *                               				parameter since simple is default.
	 * @return  true
	 */
	public static function protectIfNecessary($groupName, $password, $heavyEncryption = false)
	{
		self::start();

		if( $password !== '' && ( self::isAccessibleGroup( $groupName ) || ! self::exists( $groupName ) ) )
		{
			self::$dataRepository->createGroupIfNotExists( $groupName );
			return self::$securityRepository->add( $groupName, $password, ! $heavyEncryption );
		}
		# you may not redeclare protection on an already protected group
		return false;
	}
	/**
	 * Fetches the correct groups from a list of names
	 * @param  array  $groupNames
	 * @return  array
	 */
	public static function groups( Array $groupNames )
	{
		return array_filter( array_map( function( $name ){
			if( self::exists($name) && ! self::isPrivateGroup($name) )
				return self::group($name);
			else
				return false;
		}, $groupNames ) );
	}
	/**
	 * Gets a single value based on key, from a specific group or default
	 * @param  string $key			The data item to retrieve
	 * @param  string $group 		The target group
	 * @return  mixed        			The requested data item
	 */
	public static function get( $key, $groupName = 'default', $password ='', $heavyEncryption = false )
	{
		self::start();

		if( ! self::isAccessibleGroup( $groupName ) ) return self::$dataRepository->getValue( $key, $groupName );

		if( $password !== '' && self::$securityRepository->check(  $groupName, $password ) )
			return self::$dataRepository->getValue( $groupName );
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