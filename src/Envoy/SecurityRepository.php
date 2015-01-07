<?php

namespace AKL\Envoy;

use AKL\Envoy\SecurityObject as SecurityObject;

class SecurityRepository
{
	protected $repo;
	protected $protectedGroups = [  ];

	public function add(  $groupName, $password, $simple = true )
	{

		if( $this->isPrivateGroup( $groupName ) ) return false;

		$this->protectedGroups[$groupName] = new SecurityObject( $password, $simple );

		return true;
	}

	public function check( $groupName, $password )
	{
		return $this->protectedGroups[$groupName]->inspect($password);
	}

	public function isPrivateGroup( $groupName )
	{
		return array_search( $groupName, $this->protectedGroups ) !== false;
	}
}