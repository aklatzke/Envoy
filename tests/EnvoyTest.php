<?php

namespace AKL\Tests;

use AKL\Envoy;

/**
 * Test the static methods provided by the Envoy interface.
 */
class EnvoyTest extends \PHPUnit_Framework_TestCase
{
	public function testStart(  )
	{
		$this->assertTrue( Envoy::start(), 'Envoy should return true on the first start.' );

		$this->assertFalse( Envoy::start(), 'Envoy should return false on a second start.' );
	}

	public function testProviderIsSet(  )
	{
		$this->assertInstanceOf('AKL\DataProvider', Envoy::$provider, 'Envoy failed to instantiate the correct object type.');
	}

	public function testSetDefault(  )
	{
		$this->assertSame(Envoy::set('default_var', 1), 1, 'Envoy::set() did not return data equivalent to input.');
		$this->assertSame(Envoy::set('default_flag', true, 'default'), true, 'Envoy::set() did not return data equivalent to input.');
	}

	public function testSetGroup(  )
	{
		$this->assertSame(Envoy::set('testCaseGroup_flag', 'one', 'TestCaseGroup'), 'one', 'Envoy::set() did not return data equivalent to input.');
	}

	public function testSetValuesDefault(  )
	{
		$values = Envoy::setValues([ "a" => "one", "b" => "two", 'defaultCase_flag' => 'default']);
		$this->assertArrayHasKey( 'defaultCase_flag', $values, 'Envoy::setGroup() did not return data equivalent to input.');
		$this->assertTrue( $values['defaultCase_flag'] === "default", 'Envoy::setGroup() did not return data equivalent to input.' );
	}

	public function testSetValuesGroup(  )
	{
		$values = Envoy::setValues([ "c" => "three", "b" => "two", 'anotherTestCaseGroup_flag' => 'two' ], 'AnotherTestCaseGroup');
		$this->assertArrayHasKey( 'c', $values, 'Envoy::setGroup() did not return data equivalent to input.');
		$this->assertTrue( $values['c'] === "three", 'Envoy::setGroup() did not return data equivalent to input.' );
	}

	public function testGetDefault(  )
	{
		$foundVar = Envoy::get('default_var');
		$notFoundVar = Envoy::get('var_that_shouldnt_exist_in_default_group');
		# reference the default_var key that we set in testSetDefault()
		# should be equal to int(1) in default group
		$this->assertTrue( $foundVar === 1, "Envoy::get() failed to pull the default_var from the default group.");
		# should return null if key does not exist
		$this->assertNull( $notFoundVar, 'Envoy::get() did not return null on unfound variable' );
	}

	public function testGetGroup(  )
	{
		$foundVar = Envoy::get('testCaseGroup_flag', 'TestCaseGroup');
		$notFoundVar = Envoy::get('var_that_shouldnt_exist_in_test_case_group', 'TestCaseGroup');
		# should be equal to string('one') in TestCaseGroup
		$this->assertTrue( $foundVar === 'one', "Envoy::get() failed to pull the default_var from the TestCaseGroup.");
		# should return null if key does not exist
		$this->assertNull( $notFoundVar, 'Envoy::get() did not return null on unfound variable' );
	}

	public function testGroup(  )
	{
		$group = Envoy::group( 'AnotherTestCaseGroup' );

		$this->assertArrayHasKey('anotherTestCaseGroup_flag', $group, 'Envoy::group() did not return the appropriate group or appropriate variables in the group');
		$this->assertArrayNotHasKey('testCaseGroup_flag', $group, 'Envoy::group() returned variables present in another group.');
	}

	public function testExists(  )
	{
		$this->assertTrue( Envoy::exists( 'AnotherTestCaseGroup' ) );
	}

	public function testNotExists()
	{
		$this->assertFalse( Envoy::exists( 'ThisGroupDoesntExist' ) );
	}

	public function testOnlyDefault(  )
	{
		$intactGroup = Envoy::group( 'default' );
		$filteredGroup = Envoy::only( ['default_var'], 'default' );

		$this->assertNotSame($intactGroup, $filteredGroup, 'Envoy::only() returning non-specified keys.');
		$this->assertArrayHasKey('default_flag', $intactGroup, 'Envoy::group() not returning correct key groups');
		$this->assertArrayNotHasKey('default_flag', $filteredGroup, 'Envoy::only() did not sort test flag out of group.');
	}

	public function testOnlyFallback(  )
	{
		$this->assertSame( Envoy::only(['a']), Envoy::only(['a'], 'default'), 'Envoy::only() is not defaulting to default group.' );
	}

	public function testOnlyGroup(  )
	{
		$intactGroup = Envoy::group('AnotherTestCaseGroup');
		$filteredGroup = Envoy::only( ['a'], 'AnotherTestCaseGroup' );

		$this->assertNotSame($intactGroup, $filteredGroup, 'Envoy::only() returning non-specified keys.');
		$this->assertArrayHasKey('anotherTestCaseGroup_flag', $intactGroup, 'Envoy::group() not returning correct key groups');
		$this->assertArrayNotHasKey('anotherTestCaseGroup_flag', $filteredGroup, 'Envoy::only() did not sort test flag out of group.');
	}

	public function testZipWithoutLimiters(  )
	{
		$groups = ['AnotherTestCaseGroup', 'TestCaseGroup'];

		$zipped = Envoy::zip($groups);

		# should have the flags from both arrays
		$this->assertArrayHasKey('anotherTestCaseGroup_flag', $zipped, 'Envoy::zip() does not contain all keys from arrays.');
		$this->assertArrayHasKey('testCaseGroup_flag', $zipped, 'Envoy::zip() does not contain all keys from arrays.');
	}

	public function testZipWithLimiters(  )
	{
		$groups = ['AnotherTestCaseGroup', 'TestCaseGroup'];
		$limiter = ['testCaseGroup_flag'];

		$zipped = Envoy::zip($groups, $limiter);
		# should NOT contain the unmentioned flag
		$this->assertArrayNotHasKey('anotherTestCaseGroup_flag', $zipped, 'Envoy::zip() result contains an excluded key.');
		$this->assertArrayHasKey('testCaseGroup_flag', $zipped, 'Envoy::zip() result does not contain an included variable.');
	}

	public function testShared(  )
	{
		$data = ["shared_key" => "first", "unshared_key" => "foo"];
		$secondData = ["shared_key" => "second", "another_unshared_key" => "bar"];

		Envoy::setValues($data, 'SharedGroupOne');
		Envoy::setValues($secondData, 'SharedGroupTwo');

		$intersected = Envoy::shared(['SharedGroupOne', 'SharedGroupTwo']);

		$this->assertArrayHasKey( 'shared_key', $intersected, 'Envoy::shared() did not keep shared keys.' );
		$this->assertArrayNotHasKey( 'unshared_key' , $intersected, 'Envoy::shared() did not filter out non-shared keys.' );
	}

	public function testGroups()
	{
		$data = ["AnotherTestCaseGroup", "TestCaseGroup"];

		$res = Envoy::groups( $data );

		$this->assertEquals( count($res), 2, 'Envoy::groups() is not converting the correct number of objects.' );
	}

	public function testGroupingShouldOnlyReturnDefinedGroups(  )
	{
		$data = ["AnotherTestCaseGroup", "ThisGroupDoesntExist"];

		$res = Envoy::groups( $data );

		$this->assertEquals( count($res), 1, 'Envoy::groups() is returning NULL valued groups.' );

	}
}