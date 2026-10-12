<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parents = [
            'LAB1' => ['name' => 'Lab TKJ 1', 'description' => 'Laboratorium TKJ 1'],
            'LAB2' => ['name' => 'Lab TKJ 2', 'description' => 'Laboratorium TKJ 2'],
            'SRV' => ['name' => 'Ruang Server', 'description' => 'Ruang server dan perangkat jaringan utama'],
        ];

        foreach ($parents as $code => $attributes) {
            Location::firstOrCreate(
                ['code' => $code],
                [...$attributes, 'is_active' => true]
            );
        }

        $children = [
            ['code' => 'LAB1-LEM-001', 'name' => 'Lemari 001', 'parent_code' => 'LAB1'],
            ['code' => 'LAB1-LEM-002', 'name' => 'Lemari 002', 'parent_code' => 'LAB1'],
            ['code' => 'LAB2-LEM-001', 'name' => 'Lemari 001', 'parent_code' => 'LAB2'],
            ['code' => 'SRV-RACK-001', 'name' => 'Rack Server 001', 'parent_code' => 'SRV'],
        ];

        foreach ($children as $child) {
            $parent = Location::where('code', $child['parent_code'])->firstOrFail();

            Location::updateOrCreate(
                ['code' => $child['code']],
                [
                    'name' => $child['name'],
                    'parent_id' => $parent->id,
                    'description' => 'Sub-lokasi demo di '.$parent->name,
                    'is_active' => true,
                ]
            );
        }

        $categories = [
            'PC' => Category::firstOrCreate(
                ['asset_prefix' => 'PC'],
                ['code' => 'CAT-PC', 'name' => 'PC Desktop', 'is_active' => true]
            ),
            'SW' => Category::firstOrCreate(
                ['asset_prefix' => 'SW'],
                ['code' => 'CAT-SW', 'name' => 'Switch', 'is_active' => true]
            ),
            'SRV' => Category::firstOrCreate(
                ['asset_prefix' => 'SRV'],
                ['code' => 'CAT-SRV', 'name' => 'Server', 'is_active' => true]
            ),
            'UPS' => Category::firstOrCreate(
                ['asset_prefix' => 'UPS'],
                ['code' => 'CAT-UPS', 'name' => 'UPS', 'is_active' => true]
            ),
        ];

        $brands = [];

        foreach (['HP', 'Cisco', 'Dell', 'APC'] as $brandName) {
            $brands[$brandName] = Brand::firstOrCreate(
                ['name' => $brandName],
                ['is_active' => true]
            );
        }

        $assets = [
            [
                'asset_code' => 'GT-DEMO-SUB-001',
                'name' => 'PC Praktikum Lemari 001',
                'category' => 'PC',
                'brand' => 'HP',
                'model' => 'ProDesk',
                'serial_number' => 'DEMO-SUB-PC-001',
                'location_code' => 'LAB1-LEM-001',
                'purchase_price' => 5500000,
            ],
            [
                'asset_code' => 'GT-DEMO-SUB-002',
                'name' => 'Switch Cadangan Lemari 002',
                'category' => 'SW',
                'brand' => 'Cisco',
                'model' => 'Catalyst 2960',
                'serial_number' => 'DEMO-SUB-SW-001',
                'location_code' => 'LAB1-LEM-002',
                'purchase_price' => 2500000,
            ],
            [
                'asset_code' => 'GT-DEMO-SUB-003',
                'name' => 'PC Praktikum Lab TKJ 2',
                'category' => 'PC',
                'brand' => 'Dell',
                'model' => 'OptiPlex',
                'serial_number' => 'DEMO-SUB-PC-002',
                'location_code' => 'LAB2-LEM-001',
                'purchase_price' => 5200000,
            ],
            [
                'asset_code' => 'GT-DEMO-SUB-004',
                'name' => 'Server Virtualisasi Rack 001',
                'category' => 'SRV',
                'brand' => 'Dell',
                'model' => 'PowerEdge',
                'serial_number' => 'DEMO-SUB-SRV-001',
                'location_code' => 'SRV-RACK-001',
                'purchase_price' => 15000000,
            ],
            [
                'asset_code' => 'GT-DEMO-SUB-005',
                'name' => 'UPS Rack Server 001',
                'category' => 'UPS',
                'brand' => 'APC',
                'model' => 'Back-UPS',
                'serial_number' => 'DEMO-SUB-UPS-001',
                'location_code' => 'SRV-RACK-001',
                'purchase_price' => 2000000,
            ],
        ];

        foreach ($assets as $data) {
            $asset = Asset::firstOrNew(['asset_code' => $data['asset_code']]);

            if (! $asset->exists) {
                $asset->qr_token = (string) Str::uuid();
            }

            $asset->fill([
                'name' => $data['name'],
                'category_id' => $categories[$data['category']]->id,
                'brand_id' => $brands[$data['brand']]->id,
                'model' => $data['model'],
                'serial_number' => $data['serial_number'],
                'location_id' => Location::where('code', $data['location_code'])->value('id'),
                'condition' => 'good',
                'status' => 'available',
                'acquisition_date' => now()->subYear()->toDateString(),
                'funding_source' => 'Data Demonstrasi',
                'purchase_price' => $data['purchase_price'],
                'notes' => 'Data demonstrasi aset di sub-lokasi.',
            ]);

            $asset->save();
        }
    }
}
