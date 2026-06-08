<?php

declare(strict_types=1);

namespace gcgov\framework\services\authmsfront\tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use gcgov\framework\services\authmsfront\router;
use gcgov\framework\models\environmentConfig;
use gcgov\framework\models\route;
use gcgov\framework\models\routeHandler;
use gcgov\framework\exceptions\routeException;

#[CoversClass(router::class)]
final class RouterTest extends TestCase {

	protected function setUp(): void {
		$envConfig = new environmentConfig();
		$envConfig->basePath = 'api';

		$prop = new \ReflectionProperty( \gcgov\framework\config::class, 'environmentConfig' );
		$prop->setValue( null, $envConfig );

		unset( $_SERVER[ 'HTTP_AUTHORIZATION' ], $_GET[ 'fileAccessToken' ] );
	}

	public function testRouterImplementsFrameworkRouterInterface(): void {
		$this->assertContains(
			\gcgov\framework\interfaces\router::class,
			class_implements( router::class ) ?: []
		);
	}

	public function testGetRoutesReturnsThreeAuthRoutes(): void {
		$routes = ( new router() )->getRoutes();
		$this->assertCount( 3, $routes );
		foreach ( $routes as $route ) {
			$this->assertInstanceOf( route::class, $route );
		}
	}

	public function testJwksRouteIsPublic(): void {
		$route = $this->routes()[0];
		$this->assertSame( 'GET', $route->httpMethod );
		$this->assertSame( '/api/.well-known/jwks.json', $route->route );
		$this->assertSame( 'jwks', $route->method );
		$this->assertFalse( $route->authentication );
	}

	public function testMicrosoftRouteIsPublic(): void {
		$route = $this->routes()[1];
		$this->assertSame( 'GET', $route->httpMethod );
		$this->assertSame( '/api/auth/microsoft', $route->route );
		$this->assertSame( 'microsoft', $route->method );
		$this->assertFalse( $route->authentication );
	}

	public function testFileTokenRouteRequiresAuthentication(): void {
		$route = $this->routes()[2];
		$this->assertSame( 'GET', $route->httpMethod );
		$this->assertSame( '/api/auth/fileToken', $route->route );
		$this->assertSame( 'fileToken', $route->method );
		$this->assertTrue( $route->authentication );
	}

	public function testAuthenticationWithoutAuthorizationHeaderThrows401(): void {
		$handler = new routeHandler( '\some\controller', 'method' );
		$handler->allowShortLivedUrlTokens = false;

		try {
			( new router() )->authentication( $handler );
			$this->fail( 'Expected routeException not thrown' );
		}
		catch ( routeException $e ) {
			$this->assertSame( 401, $e->getCode() );
			$this->assertSame( 'Missing Authorization', $e->getMessage() );
		}
	}

	public function testAuthenticationWithShortLivedUrlsButNoTokenThrows401(): void {
		$handler = new routeHandler( '\some\controller', 'method' );
		$handler->allowShortLivedUrlTokens = true;

		try {
			( new router() )->authentication( $handler );
			$this->fail( 'Expected routeException not thrown' );
		}
		catch ( routeException $e ) {
			$this->assertSame( 401, $e->getCode() );
		}
	}

	public function testAuthenticationRejectsMalformedToken(): void {
		$_SERVER[ 'HTTP_AUTHORIZATION' ] = 'not-a-valid-jwt';
		$handler = new routeHandler( '\some\controller', 'method' );

		$this->expectException( \Throwable::class );
		( new router() )->authentication( $handler );
	}

	public function testLifecycleHooksAreCallable(): void {
		router::_before();
		router::_after();
		$this->assertTrue( true );
	}

	/** @return list<route> */
	private function routes(): array {
		return ( new router() )->getRoutes();
	}

}
