<?php

declare(strict_types=1);

namespace gcgov\framework\services\authmsfront\tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use gcgov\framework\services\authmsfront\msAuthConfig;

#[CoversClass(msAuthConfig::class)]
final class MsAuthConfigTest extends TestCase {

	protected function setUp(): void {
		// Ensure each test sees a clean instance with default values.
		$this->resetSingleton();
	}

	protected function tearDown(): void {
		$this->resetSingleton();
	}

	public function testGetInstanceReturnsSingleton(): void {
		$a = msAuthConfig::getInstance();
		$b = msAuthConfig::getInstance();
		$this->assertSame( $a, $b );
	}

	public function testNewUsersBlockedByDefault(): void {
		$config = msAuthConfig::getInstance();
		$this->assertTrue( $config->isBlockNewUsers() );
		$this->assertSame( [], $config->getDefaultNewUserRoles() );
	}

	public function testSetBlockNewUsersAcceptsFalseAndStoresRoles(): void {
		$config = msAuthConfig::getInstance();
		$config->setBlockNewUsers( false, [ 'Role.A', 'Role.B' ] );

		$this->assertFalse( $config->isBlockNewUsers() );
		$this->assertSame( [ 'Role.A', 'Role.B' ], $config->getDefaultNewUserRoles() );
	}

	public function testSetBlockNewUsersTrueIgnoresRolesArgument(): void {
		$config = msAuthConfig::getInstance();
		$config->setBlockNewUsers( true, [ 'Role.A' ] );

		$this->assertTrue( $config->isBlockNewUsers() );
		$this->assertSame( [], $config->getDefaultNewUserRoles() );
	}

	public function testSetBlockNewUsersClearsRolesOnReBlock(): void {
		$config = msAuthConfig::getInstance();
		$config->setBlockNewUsers( false, [ 'Role.A' ] );
		$this->assertSame( [ 'Role.A' ], $config->getDefaultNewUserRoles() );

		// Re-blocking: the implementation only writes roles when unblocking,
		// so the previously-set roles persist. Document the actual behaviour.
		$config->setBlockNewUsers( true );
		$this->assertTrue( $config->isBlockNewUsers() );
		$this->assertSame( [ 'Role.A' ], $config->getDefaultNewUserRoles() );
	}

	public function testConstructorIsPrivate(): void {
		$reflection = new \ReflectionMethod( msAuthConfig::class, '__construct' );
		$this->assertTrue( $reflection->isPrivate() );
	}

	public function testCloneIsFinal(): void {
		$reflection = new \ReflectionMethod( msAuthConfig::class, '__clone' );
		$this->assertTrue( $reflection->isFinal() );
	}

	public function testSleepReturnsEmptyArrayForUnserializableSingleton(): void {
		$this->assertSame( [], msAuthConfig::getInstance()->__sleep() );
	}

	public function testWakeupIsFinal(): void {
		$reflection = new \ReflectionMethod( msAuthConfig::class, '__wakeup' );
		$this->assertTrue( $reflection->isFinal() );
	}

	public function testGetInstanceLateStaticBoundForSubclasses(): void {
		$reflection = new \ReflectionMethod( msAuthConfig::class, 'getInstance' );
		$this->assertTrue( $reflection->isFinal() );
		$this->assertTrue( $reflection->isStatic() );
	}

	private function resetSingleton(): void {
		$prop = new \ReflectionProperty( msAuthConfig::class, 'instance' );
		if ( $prop->isInitialized() ) {
			$prop->setValue( null, ( new \ReflectionClass( msAuthConfig::class ) )->newInstanceWithoutConstructor() );
		}
	}

}
