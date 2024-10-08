<?php

namespace gcgov\framework\services\authmsfront\controllers;

use gcgov\framework\config;
use gcgov\framework\exceptions\controllerException;
use gcgov\framework\exceptions\modelException;
use gcgov\framework\interfaces\controller;
use gcgov\framework\models\controllerDataResponse;
use gcgov\framework\services\authmsfront\msAuthConfig;

class auth implements controller {

	public function __construct() {

	}


	//URL: /.well-known/jwks.json
	public function jwks(): controllerDataResponse {
		$jwtService = new \gcgov\framework\services\jwtAuth\jwtAuth();
		$jwksKeys   = $jwtService->getJwksKeys();

		$data = [
			'keys' => $jwksKeys
		];

		return new controllerDataResponse( $data );
	}


	/**
	 * @return \gcgov\framework\models\controllerDataResponse
	 * @throws \gcgov\framework\exceptions\controllerException
	 */
	public function microsoft(): controllerDataResponse {

		if( !isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] ) ) {
			new controllerException( 'Microsoft access token not provided in authorization header', 401 );
		}

		//authenticate user with Microsoft
		$microsoftConfig               = new \andrewsauder\microsoftServices\config();
		$microsoftConfig->clientId     = config::getEnvironmentConfig()->microsoft->clientId;
		$microsoftConfig->clientSecret = config::getEnvironmentConfig()->microsoft->clientSecret;
		$microsoftConfig->tenant       = config::getEnvironmentConfig()->microsoft->tenant;
		$microsoftConfig->fromAddress  = config::getEnvironmentConfig()->microsoft->fromAddress;
		$microsoftAuthService = new \andrewsauder\microsoftServices\auth( $microsoftConfig ); // \gcgov\framework\services\microsoft\auth();
		$tokenInfo            = $microsoftAuthService->verify();
		$user                 = $this->lookupUserMicrosoftTokenInfo( $tokenInfo );

		//convert \app\models\user to authUser singleton
		$authUser = \gcgov\framework\services\request::getAuthUser();
		$authUser->setFromUser( $user );

		//generate our custom jwt and return it to the user
		$jwtService  = new \gcgov\framework\services\jwtAuth\jwtAuth();
		$accessToken = $jwtService->createAccessToken( $authUser );

		//return data
		$data = [
			'accessToken' => $accessToken->toString()
		];

		return new controllerDataResponse( $data );

	}


	/**
	 * @OA\Get(
	 *     path="/auth/fileToken",
	 *     tags={"Auth"},
	 *     description="Use your app access token to get a very short lived access token that can be used as a URL parameter on specific routes that allow it"
	 * )
	 *
	 * @return \gcgov\framework\models\controllerDataResponse
	 */
	public function fileToken(): controllerDataResponse {
		$authUser = \gcgov\framework\services\request::getAuthUser();

		//generate our custom jwt and return it to the user
		$jwtService  = new \gcgov\framework\services\jwtAuth\jwtAuth();
		$accessToken = $jwtService->createAccessToken( $authUser, new \DateInterval( 'PT5S' ) );

		//return data
		$data = [
			'accessToken' => $accessToken->toString()
		];

		return new controllerDataResponse( $data );
	}


	/**
	 * Processed after lifecycle is complete with this instance
	 */
	public static function _after(): void {

	}


	/**
	 * Processed prior to __constructor() being called
	 */
	public static function _before(): void {

	}


	/**
	 * @throws \gcgov\framework\exceptions\controllerException
	 */
	private function lookupUserMicrosoftTokenInfo( \andrewsauder\microsoftServices\components\tokenInformation $tokenInfo ): \gcgov\framework\services\mongodb\models\auth\user {
		$userClassName = \gcgov\framework\services\request::getUserClassFqdn();

		//get user from database using Microsoft unique Id
		try {
			$msAuthConfig = msAuthConfig::getInstance();

			$user = $userClassName::getFromOauth(
				email:            $tokenInfo->email,
				externalId:       $tokenInfo->oid,
				externalProvider: 'MicrosoftGraph',
				firstName:        $tokenInfo->name,
				addIfNotExisting: !$msAuthConfig->isBlockNewUsers(),
				rolesForNewUser:  $msAuthConfig->getDefaultNewUserRoles() );
		}
		catch( modelException $e ) {
			throw new \gcgov\framework\exceptions\controllerException( 'The Microsoft user may need to be added to the user collection within the application. This Microsoft user could not be found in the app user list by external id and does not have a preferred username to lookup by email.', 404, $e );
		}

		try {
			$updateResult = $userClassName::save( $user );
		}
		catch( modelException $e ) {
			//failed to save external id - no problem, we will try again next sign in
		}

		return $user;
	}

}
