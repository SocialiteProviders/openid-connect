<?php

namespace SocialiteProviders\OpenIDConnect\Tests\Unit;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use ReflectionMethod;
use ReflectionProperty;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\OpenIDConnect\Tests\TestCase;

class HttpClientTest extends TestCase
{
    private function clientOptions(array $config): array
    {
        $provider = new Provider(
            Request::create('https://app.test/login'),
            'the-client',
            'the-secret',
            'https://app.test/callback',
        );

        $provider->setConfig(new Config('the-client', 'the-secret', 'https://app.test/callback', $config));

        $client = (new ReflectionMethod($provider, 'getHttpClient'))->invoke($provider);
        $this->assertInstanceOf(Client::class, $client);

        return (new ReflectionProperty($client, 'config'))->getValue($client);
    }

    public function test_proxy_config_is_passed_to_the_http_client(): void
    {
        $options = $this->clientOptions([
            'base_url' => 'https://op.test',
            'proxy'    => 'http://proxy.corp.test:8080',
        ]);

        $this->assertSame('http://proxy.corp.test:8080', $options['proxy']);
    }

    public function test_no_proxy_is_set_by_default(): void
    {
        $options = $this->clientOptions(['base_url' => 'https://op.test']);

        $this->assertArrayNotHasKey('proxy', $options);
    }

    public function test_proxy_is_a_declared_config_key(): void
    {
        $this->assertContains('proxy', Provider::additionalConfigKeys());
    }
}
