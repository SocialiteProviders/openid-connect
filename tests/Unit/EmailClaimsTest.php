<?php

namespace SocialiteProviders\OpenIDConnect\Tests\Unit;

use InvalidArgumentException;
use ReflectionMethod;
use SocialiteProviders\OpenIDConnect\Providers\EntraProvider;
use SocialiteProviders\OpenIDConnect\Tests\Support\InteractsWithOidc;
use SocialiteProviders\OpenIDConnect\Tests\TestCase;

class EmailClaimsTest extends TestCase
{
    use InteractsWithOidc;

    public function test_the_email_claim_is_used_by_default(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $this->assertSame('user@example.com', $provider->user()->getEmail());
    }

    public function test_configured_claims_are_tried_in_order(): void
    {
        $claims = $this->idTokenClaims([
            'email'              => null,
            'preferred_username' => 'login@example.com',
        ]);

        $provider = $this->makeProvider(
            ['email_claims' => ['preferred_username', 'email']],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('login@example.com', $provider->user()->getEmail());
    }

    public function test_an_empty_first_claim_falls_through_to_the_next(): void
    {
        $claims = $this->idTokenClaims([
            'preferred_username' => '',
            'email'              => 'contact@example.com',
        ]);

        $provider = $this->makeProvider(
            ['email_claims' => ['preferred_username', 'email']],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('contact@example.com', $provider->user()->getEmail());
    }

    public function test_claims_can_be_configured_as_a_string(): void
    {
        $claims = $this->idTokenClaims([
            'email' => null,
            'upn'   => 'upn@example.com',
        ]);

        $provider = $this->makeProvider(
            ['email_claims' => 'upn, email'],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('upn@example.com', $provider->user()->getEmail());
    }

    public function test_an_alternate_claim_satisfies_require_email(): void
    {
        $claims = $this->idTokenClaims([
            'email'              => null,
            'preferred_username' => 'login@example.com',
        ]);

        $provider = $this->makeProvider(
            ['require_email' => true, 'email_claims' => ['preferred_username', 'email']],
            $this->happyPathResponses($claims),
        );

        $this->assertSame('login@example.com', $provider->user()->getEmail());
    }

    public function test_userinfo_is_not_consulted_when_an_alternate_claim_is_present(): void
    {
        $claims = $this->idTokenClaims([
            'email'              => null,
            'preferred_username' => 'login@example.com',
        ]);

        $provider = $this->makeProvider(
            ['email_claims' => ['preferred_username', 'email']],
            $this->happyPathResponses($claims),
        );
        $provider->user();

        $this->assertNotContains('GET /userinfo', $this->requestedPaths());
    }

    public function test_require_email_still_fails_when_no_configured_claim_resolves(): void
    {
        $claims = $this->idTokenClaims(['email' => null]);

        $provider = $this->makeProvider(
            ['require_email' => true, 'email_claims' => ['preferred_username', 'email']],
            [
                $this->jsonResponse($this->discoveryDocument()),
                $this->tokenEndpointResponse($this->encodeToken($claims)),
                $this->jsonResponse($this->jwksDocument()),
                $this->jsonResponse(['sub' => 'user-123', 'name' => 'No Email']),
            ],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no email');

        $provider->user();
    }

    private function entraResolveEmail(array $claims): ?string
    {
        $provider = $this->makeProvider([], [], providerClass: EntraProvider::class);

        return (new ReflectionMethod($provider, 'resolveEmail'))->invoke($provider, $claims);
    }

    public function test_entra_prefers_preferred_username_over_the_contact_email(): void
    {
        $this->assertSame('login@contoso.com', $this->entraResolveEmail([
            'preferred_username' => 'login@contoso.com',
            'email'              => 'unrelated-contact@gmail.com',
        ]));
    }

    public function test_entra_falls_back_to_the_email_claim(): void
    {
        $this->assertSame('contact@contoso.com', $this->entraResolveEmail([
            'email' => 'contact@contoso.com',
        ]));
    }

    public function test_entra_skips_a_phone_shaped_preferred_username(): void
    {
        // preferred_username has no fixed format and can be a phone number.
        $this->assertSame('contact@contoso.com', $this->entraResolveEmail([
            'preferred_username' => '+61400000000',
            'email'              => 'contact@contoso.com',
        ]));

        $this->assertNull($this->entraResolveEmail([
            'preferred_username' => '+61400000000',
        ]));
    }
}
