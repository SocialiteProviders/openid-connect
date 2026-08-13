# OpenID Connect for Laravel Socialite

Connect Laravel Socialite to any OpenID Connect identity provider — Keycloak, Entra ID, Auth0, Okta, Google, Authentik, or anything else that serves a discovery document. Run as many issuers side by side as you need, each as its own Socialite driver.

```bash
composer require socialiteproviders/openid-connect
```

```php
// config/oidc.php
'connections' => [
    'keycloak' => [
        'provider'      => 'keycloak',
        'server_url'    => env('KEYCLOAK_SERVER_URL'),
        'realm'         => env('KEYCLOAK_REALM'),
        'client_id'     => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect'      => env('KEYCLOAK_REDIRECT_URI'),
    ],
],
```

```php
return Socialite::driver('oidc_keycloak')->redirect();

$user = Socialite::driver('oidc_keycloak')->user();
```

Endpoints are auto-discovered, keys are fetched and rotated automatically, and every token is fully validated (signature, `iss`, `aud`, `azp`, `exp`, `nonce`, `at_hash`) with PKCE on by default.

The initial implementation is under review — full documentation lands with it.
