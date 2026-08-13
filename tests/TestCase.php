<?php

namespace SocialiteProviders\OpenIDConnect\Tests;

use Firebase\JWT\JWT;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            \SocialiteProviders\Manager\ServiceProvider::class,
            \SocialiteProviders\OpenIDConnect\OpenIDConnectServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // php-jwt's leeway is global mutable state the provider writes to.
        JWT::$leeway = 0;
    }

}
