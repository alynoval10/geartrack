<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class PublicUrlTest extends TestCase
{
    public function test_scanner_assets_and_navigation_use_public_https_origin_behind_http_proxy(): void
    {
        config(['app.public_url' => 'https://geartrack.example/']);
        (new AppServiceProvider($this->app))->boot();

        $response = $this->get('http://172.16.20.251/scan');

        $response->assertOk()
            ->assertSee('src="https://geartrack.example/build/assets/scanner-', false)
            ->assertSee('href="https://geartrack.example"', false)
            ->assertDontSee('http://172.16.20.251', false);
    }

    public function test_login_redirect_uses_public_https_origin(): void
    {
        config(['app.public_url' => 'https://geartrack.example']);
        (new AppServiceProvider($this->app))->boot();

        $this->get('http://172.16.20.251/')
            ->assertRedirect('https://geartrack.example/login');
    }

    public function test_scanner_keeps_local_urls_when_public_origin_is_unset(): void
    {
        config(['app.public_url' => null]);
        (new AppServiceProvider($this->app))->boot();

        $this->get('http://172.16.20.251/scan')->assertOk()
            ->assertSee('src="http://172.16.20.251/build/assets/scanner-', false)
            ->assertSee('href="http://172.16.20.251"', false);
    }
}
