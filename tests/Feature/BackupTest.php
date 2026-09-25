<?php

namespace Tests\Feature;

use App\Filament\Pages\Backups;
use App\Models\Asset;
use App\Models\User;
use App\Services\BackupLock;
use App\Services\BackupService;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class BackupTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/backup-'.Str::uuid());
        File::ensureDirectoryExists($this->directory);
        touch($this->directory.'/database.sqlite');
        config([
            'app.env' => 'local',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->directory.'/database.sqlite',
            'database.connections.sqlite.url' => null,
            'backup.directory' => $this->directory.'/archives',
            'backup.admin_emails' => ['admin@example.test'],
            'session.driver' => 'database',
            'filesystems.disks.public.root' => $this->directory.'/photos',
        ]);
        DB::purge();
        Storage::forgetDisk('public');
        Artisan::call('migrate', ['--force' => true]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        DB::purge();
        Storage::forgetDisk('public');
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@example.test']);
    }

    private function service(): BackupService
    {
        return app(BackupService::class);
    }

    public function test_backup_contains_database_and_referenced_photos_but_no_configuration(): void
    {
        $admin = $this->admin();
        Asset::factory()->create(['photo' => 'assets/computer.jpg']);
        Storage::disk('public')->put('assets/computer.jpg', 'photo-content');

        $name = $this->service()->create($admin->email);

        $zip = new ZipArchive;
        $zip->open($this->service()->archivePath($name));
        $this->assertSame('photo-content', $zip->getFromName('uploads/assets/computer.jpg'));
        $this->assertNotFalse($zip->locateName('database.sqlite'));
        $this->assertFalse($zip->locateName('.env'));
        $this->assertNotFalse($zip->locateName('signature'));
        $zip->close();
        $this->assertCount(1, $this->service()->archives());
    }

    public function test_restore_recovers_asset_and_photo_preserves_current_copy_and_creates_safety_backup(): void
    {
        $admin = $this->admin();
        $asset = Asset::factory()->create(['name' => 'Original', 'photo' => 'assets/computer.jpg']);
        Storage::disk('public')->put('assets/computer.jpg', 'original-photo');
        $name = $this->service()->create($admin->email);
        $asset->update(['name' => 'Changed']);
        Storage::disk('public')->put('assets/computer.jpg', 'changed-photo');
        DB::table('sessions')->insert(['id' => 'old-session', 'payload' => 'old', 'last_activity' => time()]);
        DB::table('users')->where('id', $admin->id)->update(['remember_token' => 'old-token']);

        $safety = $this->service()->restore($this->service()->archivePath($name), $admin->email);

        $restored = $asset->fresh();
        $this->assertSame('Original', $restored->name);
        $this->assertSame('original-photo', Storage::disk('public')->get($restored->photo));
        $this->assertSame('changed-photo', Storage::disk('public')->get('assets/computer.jpg'));
        $this->assertFileExists($this->service()->archivePath($safety));
        $this->assertDatabaseCount('sessions', 0);
        $this->assertNull($admin->fresh()->remember_token);
    }

    public function test_tampered_backup_is_rejected_without_changing_data(): void
    {
        $admin = $this->admin();
        $asset = Asset::factory()->create(['name' => 'Keep me']);
        $name = $this->service()->create($admin->email);
        $path = $this->service()->archivePath($name);
        $zip = new ZipArchive;
        $zip->open($path);
        $zip->addFromString('database.sqlite', 'tampered');
        $zip->close();

        try {
            $this->service()->restore($path, $admin->email);
            $this->fail('Tampered backup was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('rusak', $exception->getMessage());
        }

        $this->assertSame('Keep me', $asset->fresh()->name);
        $this->assertCount(1, $this->service()->archives());
    }

    public function test_path_traversal_archive_is_rejected_before_extraction(): void
    {
        $admin = $this->admin();
        $path = $this->directory.'/malicious.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('../outside.txt', 'unsafe');
        $zip->close();

        try {
            $this->service()->restore($path, $admin->email);
            $this->fail('Unsafe archive was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('tidak diizinkan', $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->directory.'/outside.txt');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_backup_from_different_key_is_rejected(): void
    {
        $admin = $this->admin();
        $name = $this->service()->create($admin->email);
        config(['app.key' => 'different-key']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('APP_KEY harus sama');
        $this->service()->restore($this->service()->archivePath($name), $admin->email);
    }

    public function test_schema_mismatch_is_rejected_without_replacing_database(): void
    {
        $admin = $this->admin();
        $name = $this->service()->create($admin->email);
        DB::statement('CREATE TABLE later_feature (id INTEGER PRIMARY KEY)');

        try {
            $this->service()->restore($this->service()->archivePath($name), $admin->email);
            $this->fail('Schema mismatch was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Struktur database berbeda', $exception->getMessage());
        }

        $this->assertTrue(DB::getSchemaBuilder()->hasTable('later_feature'));
    }

    public function test_missing_photo_prevents_incomplete_backup(): void
    {
        $admin = $this->admin();
        Asset::factory()->create(['photo' => 'assets/missing.jpg']);

        try {
            $this->service()->create($admin->email);
            $this->fail('Incomplete backup was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Foto aset tidak ditemukan', $exception->getMessage());
        }

        $this->assertSame([], $this->service()->archives());
    }

    public function test_oversized_unpacked_archive_is_rejected(): void
    {
        $admin = $this->admin();
        $name = $this->service()->create($admin->email);
        config(['backup.max_unpacked_bytes' => 10]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Isi backup terlalu besar');
        $this->service()->restore($this->service()->archivePath($name), $admin->email);
    }

    public function test_guest_and_unlisted_user_cannot_manage_or_download_backups(): void
    {
        $this->post(route('backups.store'))->assertRedirect(route('filament.admin.auth.login'));
        $user = User::factory()->create(['email' => 'operator@example.test']);
        $this->actingAs($user)->post(route('backups.store'))->assertForbidden();
        $this->get(route('backups.download', ['name' => 'geartrack-test.zip']))->assertForbidden();
        $this->post(route('backups.restore'))->assertForbidden();
        $this->delete(route('backups.destroy', ['name' => 'geartrack-test.zip']))->assertForbidden();
        $this->get(Backups::getUrl())->assertSee('Akses backup belum aktif')
            ->assertDontSee('Buat Backup Sekarang');
    }

    public function test_backup_menu_is_visible_when_admin_access_is_not_configured(): void
    {
        config(['backup.admin_emails' => []]);
        $this->actingAs($this->admin());

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertSee('href="'.Backups::getUrl().'"', false);
        $this->get(Backups::getUrl())
            ->assertSee('Administrator backup belum ditentukan.')
            ->assertSee('admin@example.test')
            ->assertDontSee('Buat Backup Sekarang')
            ->assertDontSee('Pulihkan Data');
    }

    public function test_unlisted_user_cannot_see_archive_details_on_backup_page(): void
    {
        $admin = $this->admin();
        $name = $this->service()->create($admin->email);
        $this->actingAs(User::factory()->create(['email' => 'operator@example.test']));

        $this->get(Backups::getUrl())
            ->assertSee('Akun Anda belum memiliki izin')
            ->assertDontSee($name)
            ->assertDontSee('Buat Backup Sekarang')
            ->assertDontSee('Pulihkan Data');
    }

    public function test_guest_cannot_open_backup_page(): void
    {
        $this->get(Backups::getUrl())->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_admin_can_create_list_and_download_private_backup(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('backups.store'))->assertRedirect(Backups::getUrl());
        $this->assertSame(5000, session('filament.notifications')[0]['duration']);

        $name = $this->service()->archives()[0]['name'];
        $this->get(Backups::getUrl())->assertOk()->assertSee($name);
        $this->get(route('backups.download', ['name' => $name]))->assertDownload($name);
    }

    public function test_restore_requires_password_and_explicit_confirmation(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('backups.restore'), [
            'password' => 'wrong', 'confirmation' => 'yes',
            'backup' => UploadedFile::fake()->createWithContent('backup.zip', 'not-a-zip'),
        ])->assertSessionHasErrors(['password', 'confirmation']);
    }

    public function test_successful_restore_logs_operator_out(): void
    {
        $admin = $this->admin();
        $name = $this->service()->create($admin->email);
        $this->actingAs($admin)->withSession(['login_web_test' => $admin->id]);

        $this->post(route('backups.restore'), [
            'password' => 'password', 'confirmation' => 'RESTORE',
            'backup' => new UploadedFile($this->service()->archivePath($name), 'backup.zip', 'application/zip', null, true),
        ])->assertRedirect(route('filament.admin.auth.login'))
            ->assertSessionMissing('login_web_test');
    }

    public function test_active_restore_blocks_other_web_requests(): void
    {
        (new BackupLock)->run(function (): void {
            $this->get('/scan')->assertStatus(503);
        }, exclusive: true);
    }

    public function test_delete_requires_password_and_only_removes_selected_archive(): void
    {
        $admin = $this->admin();
        $name = $this->service()->create($admin->email);
        $path = $this->service()->archivePath($name);
        $this->actingAs($admin)->delete(route('backups.destroy', ['name' => $name]), ['password' => 'wrong'])
            ->assertRedirect(Backups::getUrl())
            ->assertSessionHasErrors('password');
        $this->assertFileExists($path);

        $this->delete(route('backups.destroy', ['name' => $name]), ['password' => 'password'])
            ->assertStatus(303)
            ->assertRedirect(Backups::getUrl());

        $this->get(Backups::getUrl())->assertOk()->assertDontSee($name);

        $this->assertFileDoesNotExist($path);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_restore_recovers_photo_even_when_current_photo_has_been_lost(): void
    {
        $admin = $this->admin();
        $asset = Asset::factory()->create(['photo' => 'assets/lost.jpg']);
        Storage::disk('public')->put('assets/lost.jpg', 'recover-me');
        $name = $this->service()->create($admin->email);
        Storage::disk('public')->delete('assets/lost.jpg');

        $safety = $this->service()->restore($this->service()->archivePath($name), $admin->email);

        $this->assertSame('recover-me', Storage::disk('public')->get($asset->fresh()->photo));
        $this->assertFileExists($this->service()->archivePath($safety));
    }

    public function test_admin_missing_from_backup_cannot_lock_themselves_out_by_restoring(): void
    {
        $admin = $this->admin();
        $name = $this->service()->create($admin->email);
        $laterAdmin = User::factory()->create(['email' => 'later@example.test']);

        try {
            $this->service()->restore($this->service()->archivePath($name), $laterAdmin->email);
            $this->fail('Restore without current admin was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Akun admin Anda tidak ada', $exception->getMessage());
        }

        $this->assertDatabaseHas('users', ['id' => $laterAdmin->id]);
    }

    public function test_unconfigured_admin_list_denies_backup_access(): void
    {
        config(['backup.admin_emails' => []]);
        $this->actingAs($this->admin());

        $this->post(route('backups.store'))->assertForbidden();
        $this->get(Backups::getUrl())->assertSee('Administrator backup belum ditentukan.');
    }

    public function test_failed_safety_backup_does_not_replace_current_data(): void
    {
        $admin = $this->admin();
        $asset = Asset::factory()->create(['name' => 'Original']);
        $name = $this->service()->create($admin->email);
        $asset->update(['name' => 'Keep current', 'photo' => 'unsupported.php']);

        try {
            $this->service()->restore($this->service()->archivePath($name), $admin->email);
            $this->fail('Restore without a safety backup was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Lokasi foto aset tidak didukung', $exception->getMessage());
        }

        $this->assertSame('Keep current', $asset->fresh()->name);
    }
}
