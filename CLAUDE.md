# CLAUDE.md — gcgov/framework-service-auth-ms-front

A **framework-service plugin** for `gcgov/framework`. Read the framework's own `CLAUDE.md` first for the
plugin/router/lifecycle model — this file only covers what this plugin adds. See `README.md` for prose.

## Purpose
Lets a **front-end that already holds a valid Microsoft access token** exchange it for a framework **app access
token (JWT)**. The browser/SPA owns the full Microsoft auth flow and token refresh; this service simply
validates the incoming Microsoft token and mints a matching app JWT. **There are no app refresh tokens** — when
the app JWT expires, the client re-exchanges a fresh Microsoft token. It also installs the **global
authentication guard** over every app route marked `authentication: true`.

Namespace / PSR-4: `gcgov\framework\services\authmsfront\` → `src/`. Composer type: `framework-service`.
This is the lightweight alternative to `auth-oauth-server`; use **one** auth plugin, not both.

## Install & register
```php
// \app\app::registerFrameworkServiceNamespaces()
return [ '\gcgov\framework\services\authmsfront' ];
```
`composer require gcgov/framework-service-auth-ms-front`. Depends on `andrewsauder/microsoft-services`.

## Routes added (`src/router.php`, all prefixed with `environment.getBasePath()`)
| Method | Path | Controller method | Auth | Purpose |
|--------|------|-------------------|------|---------|
| GET | `/.well-known/jwks.json` | `auth::jwks` | no | Public keys for front-end JWT validation. |
| GET | `/auth/microsoft` | `auth::microsoft` | no | Exchange a Microsoft token for an app JWT. |
| GET | `/auth/fileToken` | `auth::fileToken` | yes | Mint a short-lived token usable as `?fileAccessToken=`. |

## Token exchange — `GET /auth/microsoft`
- Client sends the **Microsoft** access token: `Authorization: Bearer {microsoft_token}`.
- The service validates it (via `andrewsauder/microsoft-services`), resolves/creates the app user, and returns:
  ```json
  { "access_token": "-app_access_token-", "expires_in": 3600, "token_type": "Bearer" }
  ```
- The returned `access_token` is a framework JWT (from `\gcgov\framework\services\jwtAuth\jwtAuth`) that the
  client then sends as `Authorization: Bearer …` to all authenticated app routes.

## Authentication guard (`router::authentication()`)
Identical in shape to the oauth-server guard, and applies to **every** app route with `authentication: true`:
1. JWT from `Authorization: Bearer …`, or `?fileAccessToken=` when `allowShortLivedUrlTokens`; else `401`.
2. Validate via `jwtAuth::validateAccessToken()`; failure → `401`.
3. Populate `request::getAuthUser()->setFromJwtToken(...)`.
4. Enforce `requiredRoles`; missing role → `403`.
`\app\router::getRunFrameworkServiceRouteAuthentication()` can return `false` to skip this guard per-route.

## Configuration
- **Microsoft app** creds in `environment.json → microsoft` (`clientId`, `clientSecret`, `tenant`, …).
- **JWT** issuer/audience/redirects in `environment.json → jwtAuth`; signing keys under the framework's
  `srv/jwtCertificates`.
- **New users** — `msAuthConfig` singleton (`src/msAuthConfig.php`), tweak in `\app\app::_before()`:
  ```php
  $c = \gcgov\framework\services\authmsfront\msAuthConfig::getInstance();
  $c->setBlockNewUsers(false, ['Role1.Read']);   // auto-provision authenticated MS users + default roles
  ```
  `blockNewUsers` defaults **true** (only pre-existing DB users may sign in).

## User model
Resolved via `request::getUserClassFqdn()` (`\app\models\user` else `…\mongodb\models\auth\user`), implementing
`\gcgov\framework\interfaces\auth\user`.

## When editing this plugin
- Lowercase class/file names. New endpoints go in both `router::getRoutes()` and a method on
  `\gcgov\framework\services\authmsfront\controllers\auth` returning a `controllerResponse`.
- Keep the exchange response body (`access_token`/`expires_in`/`token_type`) stable — clients depend on it.
- `composer ci` before pushing.
