<?php

declare(strict_types=1);

namespace gcgov\framework\services\authmsfront\tests\Unit;

use PHPUnit\Framework\TestCase;
use gcgov\framework\services\authmsfront\msAuthConfig;

final class MsAuthConfigTest extends TestCase {

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

	public function testRolesAssignedOnlyWhenUnblocking(): void {
		$config = msAuthConfig::getInstance();
		$config->setBlockNewUsers( true, [ 'should-not-stick' ] );
		$this->assertSame( [], $config->getDefaultNewUserRoles() );

		$config->setBlockNewUsers( false, [ 'User.Read' ] );
		$this->assertFalse( $config->isBlockNewUsers() );
		$this->assertSame( [ 'User.Read' ], $config->getDefaultNewUserRoles() );

		$config->setBlockNewUsers( true );
	}

}
