<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Panel berada di halaman utama, sehingga tamu harus masuk terlebih dahulu.
     */
    public function test_guest_is_redirected_to_login_from_the_home_page(): void
    {
        $this->get('/')
            ->assertRedirect(route('filament.admin.auth.login'));
    }
}
