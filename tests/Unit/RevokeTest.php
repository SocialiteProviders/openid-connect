<?php

namespace SocialiteProviders\OpenIDConnect\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use SocialiteProviders\OpenIDConnect\Tests\Support\InteractsWithOidc;
use SocialiteProviders\OpenIDConnect\Tests\TestCase;

class RevokeTest extends TestCase
{
    use InteractsWithOidc;

    #[DataProvider('revocationStatuses')]
    public function test_revocation_result_follows_the_response_status(int $status, bool $expected): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            new Response($status),
        ]);

        $this->assertSame($expected, $provider->revoke('the-refresh-token'));
    }

    public static function revocationStatuses(): array
    {
        return [
            'ok'                  => [200, true],
            'no content'          => [204, true],
            'already revoked 400' => [400, false],
            'bad credentials 401' => [401, false],
            'server error 500'    => [500, false],
        ];
    }

    public function test_the_revocation_request_carries_token_and_hint(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            new Response(200),
        ]);

        $provider->revoke('some-token', 'access_token');

        parse_str((string) $this->lastRequestTo('/revoke')->getBody(), $body);

        $this->assertSame('some-token', $body['token']);
        $this->assertSame('access_token', $body['token_type_hint']);
        $this->assertSame(static::$opClientId, $body['client_id']);
    }

    public function test_revoke_without_an_advertised_endpoint_is_refused(): void
    {
        $doc = $this->discoveryDocument();
        unset($doc['revocation_endpoint']);

        $provider = $this->makeProvider([], [$this->jsonResponse($doc)]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('revocation_endpoint');

        $provider->revoke('the-refresh-token');
    }
}
