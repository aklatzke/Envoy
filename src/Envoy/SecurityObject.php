<?php

namespace AKL\Envoy;

class SecurityObject
{
	private $salt;
	private $password;

	public function __construct( $password, $simple = true )
	{
		if( ! $password ) return false;

		$crypt = '';

		if( $simple ) $crypt = md5(uniqid() . uniqid());
			else $crypt = base64_encode( mcrypt_create_iv( ceil( 0.75 * 26 ), MCRYPT_DEV_URANDOM ) );

		$this->salt = $crypt;
    		$this->password =  password_hash( $password, PASSWORD_BCRYPT, [ 'salt' => $crypt ] );

		return true;
	}

	public function inspect( $password )
	{
		if( ! $password ) return false;

		if(  $this->password === password_verify($password, password_hash( $password, PASSWORD_BCRYPT, [ 'salt' => $this->salt ] ) ) ) return true;

		return false;
	}
}