<?php

namespace SocialiteProviders\OpenIDConnect\Tests\Unit;

use InvalidArgumentException;
use SocialiteProviders\OpenIDConnect\Tests\Support\InteractsWithOidc;
use SocialiteProviders\OpenIDConnect\Tests\TestCase;

class AtHashTest extends TestCase
{
    use InteractsWithOidc;

    public function test_a_correct_at_hash_is_accepted(): void
    {
        $claims = $this->idTokenClaims([
            'at_hash' => $this->atHashFor('the-access-token'),
        ]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_an_at_hash_for_a_different_access_token_is_rejected(): void
    {
        $claims = $this->idTokenClaims([
            'at_hash' => $this->atHashFor('a-stolen-access-token'),
        ]);

        $provider = $this->makeProvider([], $this->happyPathResponses($claims));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at_hash mismatch');

        $provider->user();
    }

    public function test_a_token_without_at_hash_is_tolerated(): void
    {
        // at_hash is OPTIONAL for the code flow (OIDC Core 3.1.3.6).
        $provider = $this->makeProvider([], $this->happyPathResponses());

        $this->assertSame('user-123', $provider->user()->getId());
    }

    public function test_at_hash_is_validated_in_the_unverified_path_too(): void
    {
        $claims = $this->idTokenClaims([
            'at_hash' => $this->atHashFor('a-stolen-access-token'),
        ]);

        $provider = $this->makeProvider(['verify_jwt' => false], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->tokenEndpointResponse($this->unsignedToken($claims)),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at_hash mismatch');

        $provider->user();
    }
}
