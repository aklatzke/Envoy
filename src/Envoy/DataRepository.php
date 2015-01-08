<?php

namespace AKL\Envoy;

class DataRepository
{
	protected $groups = [
		'default' => []
	];

	public function getValue( $key, $name = 'default' )
	{
		if( ! $this->checkKeyExists( $key, $name ) )
		{
			return NULL;
		}

		return $this->groups[$name][$key];
	}

	public function getGroup( $group )
	{
		if( isset( $this->groups[$group] ) ) return $this->groups[$group];

		return $this->notFoundException();
	}

	public function getLimited( $limiters, $group )
	{
		$temp = [];

		foreach($limiters as $limiter)
		{
			if( ! $this->checkKeyExists($limiter, $group) )
			{
				$temp[$limiter] = NULL;
			}

			if( isset($this->groups[$group]) && isset($this->groups[$group][$limiter]) ) $temp[$limiter] = $this->groups[$group][$limiter];
		}

		return $temp;
	}

	public function hasGroup( $groupName )
	{
		return isset( $this->groups[$groupName] );
	}

	public function setValue( $key, $data, $group = 'default' )
	{
		$this->createGroupIfNotExists( $group );

		return $this->groups[$group][$key] = $data;
	}

	public function setValues( $arr, $group )
	{
		$this->createGroupIfNotExists( $group );

		return $this->groups[$group] = array_merge($this->groups[$group], $arr);
	}

	public function createGroupIfNotExists( $name )
	{
		if( ! isset( $this->groups[$name] ) ) $this->groups[$name] = [];

		return false;
	}

	private function notFoundException()
	{
		throw new \Exception('AKL\DataProvider ERROR: Key or group does not exist in selection');

		return false;
	}

	private function checkKeyExists( $key, $group )
	{
		if( isset( $this->groups[$group] ) &&  isset( $this->groups[$group][$key] ))
		{
			return true;
		}

		return false;
	}

}