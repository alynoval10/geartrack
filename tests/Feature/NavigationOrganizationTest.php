<?php

namespace Tests\Feature;

use App\Filament\Pages\Backups;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ImportAssets;
use App\Filament\Pages\Reports;
use App\Filament\Pages\SchoolSettings;
use App\Filament\Resources\AssetDisposals\AssetDisposalResource;
use App\Filament\Resources\AssetHistories\AssetHistoryResource;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Loans\LoanResource;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Resources\Users\UserResource;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Tests\TestCase;

class NavigationOrganizationTest extends TestCase
{
    public function test_panel_registers_navigation_groups_in_operational_order(): void
    {
        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

        $this->assertSame([
            'Beranda',
            'Inventaris',
            'Data Pendukung',
            'Operasional',
            'Administrasi',
            'Sistem',
            'Akun',
        ], $panel->getNavigationGroups());

        $scanItem = collect($panel->getNavigationItems())
            ->first(fn ($item): bool => $item->getLabel() === 'Scan QR');

        $this->assertNotNull($scanItem);
        $this->assertSame('Beranda', $scanItem->getGroup());
        $this->assertSame(route('qr.scan'), $scanItem->getUrl());
    }

    public function test_pages_and_resources_use_the_intended_navigation_groups(): void
    {
        $groups = [
            Dashboard::class => 'Beranda',
            AssetResource::class => 'Inventaris',
            CategoryResource::class => 'Data Pendukung',
            BrandResource::class => 'Data Pendukung',
            LocationResource::class => 'Data Pendukung',
            LoanResource::class => 'Operasional',
            AssetDisposalResource::class => 'Administrasi',
            Reports::class => 'Administrasi',
            AssetHistoryResource::class => 'Administrasi',
            ImportAssets::class => 'Administrasi',
            UserResource::class => 'Sistem',
            SchoolSettings::class => 'Sistem',
            Backups::class => 'Sistem',
        ];

        foreach ($groups as $pageOrResource => $expectedGroup) {
            $this->assertSame($expectedGroup, $pageOrResource::getNavigationGroup());
        }
    }
}
