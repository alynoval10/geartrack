<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    #[TestWith(['first.trycloudflare.com', '127.0.0.1'])]
    #[TestWith(['second.trycloudflare.com', '::1'])]
    public function test_scanner_uses_current_tunnel_host_and_https_from_trusted_proxy(string $host, string $proxy): void
    {
        config(['trustedproxy.proxies' => ['127.0.0.1', '::1']]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $proxy])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'untrusted.example'])
            ->get('http://'.$host.'/scan');

        $response->assertOk()
            ->assertSee('src="https://'.$host.'/build/assets/scanner-', false)
            ->assertSee('href="https://'.$host.'"', false)
            ->assertDontSee('untrusted.example', false);
    }

    public function test_login_redirect_stays_on_current_https_tunnel(): void
    {
        config(['trustedproxy.proxies' => ['127.0.0.1']]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://current.trycloudflare.com/')
            ->assertRedirect('https://current.trycloudflare.com/login');
    }

    #[TestWith([['127.0.0.1'], '192.0.2.10'])]
    #[TestWith([[], '127.0.0.1'])]
    public function test_ignores_forwarded_https_from_untrusted_clients(array $proxies, string $client): void
    {
        config(['trustedproxy.proxies' => $proxies]);

        $this->withServerVariables(['REMOTE_ADDR' => $client])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://172.16.20.251/scan')->assertOk()
            ->assertSee('src="http://172.16.20.251/build/assets/scanner-', false);
    }

    public function test_direct_local_access_keeps_http_with_proxy_support_enabled(): void
    {
        config(['trustedproxy.proxies' => ['127.0.0.1']]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('http://172.16.20.251/scan')->assertOk()
            ->assertSee('href="http://172.16.20.251"', false);
    }
}
