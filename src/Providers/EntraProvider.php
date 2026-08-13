<?php

namespace SocialiteProviders\OpenIDConnect\Providers;

use SocialiteProviders\OpenIDConnect\IssuerValidators\EntraIssuerValidator;
use SocialiteProviders\OpenIDConnect\Provider;

/**
 * Entra ID (Azure AD). `tenant` is a tenant id, a verified domain, or one of
 * the multi-tenant pseudo-tenants (common / organizations / consumers).
 */
class EntraProvider extends Provider
{
    public static function additionalConfigKeys(): array
    {
        return array_merge(parent::additionalConfigKeys(), ['tenant']);
    }

    protected function configDefaults(array $config): array
    {
        return [
            'base_url'         => 'https://login.microsoftonline.com/'.($config['tenant'] ?? 'common').'/v2.0',
            'issuer_validator' => EntraIssuerValidator::class,
        ];
    }
}
