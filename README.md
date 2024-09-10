# Microsoft Auth Service

## Service to extend gcgov/framework

### Primary purpose

* Enable the exchange of a Microsoft access token for an application access token. The service assumes the user's front
  end will handle the authentication flow to acquire and manage the expiration of the Microsoft access token. When the
  framework app's token expires, user must re-exchange a valid Microsoft access token for an updated app access token.
  There are no app refresh tokens or mechanisms.

### Impact to application
* Router:
  * Adds route `/.well-known/jwks.json` - provides endpoint to enable front end validation of tokens generated by the api
  * Adds route `/auth/microsoft` - exchanges a valid Microsoft authentication token for an app access
  * Adds route `/auth/fileToken` - create a short lived access token that can be used in the url for supported routes

## Installation:
* Require using Composer https://packagist.org/packages/gcgov/framework-service-auth-ms-front
* Add namespace `\gcgov\framework\services\authmsfront` to `\app\app->registerFrameworkServiceNamespaces()`

### Implementation
* Requests to `/auth/microsoft` must provide `Authorization` header with the valid Microsoft access token. Ex `Authorization: Bearer {microsoft_token}`
* Response body: `{ 'access_token':'-app_access_token-', 'expires_in':3600, 'token_type':'Bearer' }`

## Configuration

### Allowed Users
By default, users attempting to sign in who not already present in the user database collection will be prevented from
signing in. To enable sign in for any user who passes the third party Oauth provider authentication, set
config variable `blockNewUsers=false`. When `blockNewUsers=false`, any user successfully authenticated by the third
party Oauth provider will be automatically added to the database user config

```php
$msAuthConfig = msAuthConfig::getInstance();
$msAuthConfig->setBlockNewUsers( false );
```
### New User Default Roles
When `blockNewUsers=false`, new users will be automatically added to the user database collection. To set the default
roles that a new user should be assigned at creation, provide the roles to the `setBlockNewUsers` method.

```php
$msAuthConfig = msAuthConfig::getInstance();
$msAuthConfig->setBlockNewUsers( false, [ 'Role1.Read', 'Role2.Read', 'Role2.Write' ] );
```
