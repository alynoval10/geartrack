<?php

namespace Tests\Feature;

use App\Filament\Pages\About;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_application_information(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guru']));

        $this->get(About::getUrl())
            ->assertOk()
            ->assertSee('Versi Aplikasi')
            ->assertSee('1.0.0')
            ->assertSee('Noval Aly, S.T');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(About::getUrl())
            ->assertRedirect(route('filament.admin.auth.login'));
    }
}
