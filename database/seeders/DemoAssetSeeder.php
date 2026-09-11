<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoAssetSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = [
            'PC' => Category::firstOrCreate(
                ['asset_prefix' => 'PC'],
                [
                    'code' => 'CAT-PC',
                    'name' => 'Komputer',
                    'description' => 'Komputer desktop dan workstation',
                    'is_active' => true,
                ]
            ),

            'RTR' => Category::firstOrCreate(
                ['asset_prefix' => 'RTR'],
                [
                    'code' => 'CAT-RTR',
                    'name' => 'Router',
                    'description' => 'Router dan perangkat routing',
                    'is_active' => true,
                ]
            ),

            'SW' => Category::firstOrCreate(
                ['asset_prefix' => 'SW'],
                [
                    'code' => 'CAT-SW',
                    'name' => 'Switch',
                    'description' => 'Switch jaringan',
                    'is_active' => true,
                ]
            ),

            'AP' => Category::firstOrCreate(
                ['asset_prefix' => 'AP'],
                [
                    'code' => 'CAT-AP',
                    'name' => 'Access Point',
                    'description' => 'Perangkat jaringan wireless',
                    'is_active' => true,
                ]
            ),

            'SRV' => Category::firstOrCreate(
                ['asset_prefix' => 'SRV'],
                [
                    'code' => 'CAT-SRV',
                    'name' => 'Server',
                    'description' => 'Server fisik dan perangkat server',
                    'is_active' => true,
                ]
            ),

            'LTP' => Category::firstOrCreate(
                ['asset_prefix' => 'LTP'],
                [
                    'code' => 'CAT-LTP',
                    'name' => 'Laptop',
                    'description' => 'Laptop inventaris',
                    'is_active' => true,
                ]
            ),

            'CAM' => Category::firstOrCreate(
                ['asset_prefix' => 'CAM'],
                [
                    'code' => 'CAT-CAM',
                    'name' => 'IP Camera',
                    'description' => 'Kamera CCTV berbasis IP',
                    'is_active' => true,
                ]
            ),

            'NVR' => Category::firstOrCreate(
                ['asset_prefix' => 'NVR'],
                [
                    'code' => 'CAT-NVR',
                    'name' => 'NVR',
                    'description' => 'Network Video Recorder',
                    'is_active' => true,
                ]
            ),

            'UPS' => Category::firstOrCreate(
                ['asset_prefix' => 'UPS'],
                [
                    'code' => 'CAT-UPS',
                    'name' => 'UPS',
                    'description' => 'Uninterruptible Power Supply',
                    'is_active' => true,
                ]
            ),
            ];


        /*
        |--------------------------------------------------------------------------
        | Brands
        |--------------------------------------------------------------------------
        */

        $brandNames = [
            'MikroTik',
            'TP-Link',
            'Cisco',
            'HP',
            'Dell',
            'Lenovo',
            'Ubiquiti',
            'Hikvision',
            'APC',
        ];

        $brands = [];

        foreach ($brandNames as $brandName) {
            $brands[$brandName] = Brand::firstOrCreate(
                ['name' => $brandName],
                [
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Locations
        |--------------------------------------------------------------------------
        */

            $locations = [
            'LAB1' => Location::firstOrCreate(
                ['code' => 'LAB1'],
                [
                    'name' => 'Lab TKJ 1',
                    'description' => 'Laboratorium TKJ 1',
                    'is_active' => true,
                ]
            ),

            'LAB2' => Location::firstOrCreate(
                ['code' => 'LAB2'],
                [
                    'name' => 'Lab TKJ 2',
                    'description' => 'Laboratorium TKJ 2',
                    'is_active' => true,
                ]
            ),

            'SRV' => Location::firstOrCreate(
                ['code' => 'SRV'],
                [
                    'name' => 'Ruang Server',
                    'description' => 'Ruang server dan perangkat jaringan utama',
                    'is_active' => true,
                ]
            ),

            'OFFICE' => Location::firstOrCreate(
                ['code' => 'OFFICE'],
                [
                    'name' => 'Ruang Guru TKJ',
                    'description' => 'Ruang guru program keahlian TKJ',
                    'is_active' => true,
                ]
            ),
        ];


        /*
        |--------------------------------------------------------------------------
        | Demo Assets
        |--------------------------------------------------------------------------
        */

        $assets = [
            [
                'asset_code' => 'GT-PC-0001',
                'name' => 'PC Lab 01',
                'category' => 'PC',
                'brand' => 'HP',
                'model' => 'ProDesk',
                'serial_number' => 'DEMO-PC-001',
                'location' => 'LAB1',
                'condition' => 'good',
                'status' => 'available',
                'purchase_price' => 5500000,
            ],
            [
                'asset_code' => 'GT-PC-0002',
                'name' => 'PC Lab 02',
                'category' => 'PC',
                'brand' => 'Dell',
                'model' => 'OptiPlex',
                'serial_number' => 'DEMO-PC-002',
                'location' => 'LAB1',
                'condition' => 'good',
                'status' => 'available',
                'purchase_price' => 5250000,
            ],
            [
                'asset_code' => 'GT-PC-0003',
                'name' => 'PC Lab 03',
                'category' => 'PC',
                'brand' => 'Lenovo',
                'model' => 'ThinkCentre',
                'serial_number' => 'DEMO-PC-003',
                'location' => 'LAB2',
                'condition' => 'minor_damage',
                'status' => 'maintenance',
                'purchase_price' => 5000000,
            ],
            [
                'asset_code' => 'GT-RTR-0001',
                'name' => 'Router Gateway Lab',
                'category' => 'RTR',
                'brand' => 'MikroTik',
                'model' => 'RB750Gr3',
                'serial_number' => 'DEMO-RTR-001',
                'location' => 'SRV',
                'condition' => 'good',
                'status' => 'available',
                'purchase_price' => 900000,
            ],
            [
                'asset_code' => 'GT-SW-0001',
                'name' => 'Switch Utama Lab TKJ',
                'category' => 'SW',
                'brand' => 'Cisco',
                'model' => 'Catalyst',
                'serial_number' => 'DEMO-SW-001',
                'location' => 'SRV',
                'condition' => 'good',
                'status' => 'available',
                'purchase_price' => 3500000,
            ],
            [
                'asset_code' => 'GT-AP-0001',
                'name' => 'Access Point Lab TKJ 1',
                'category' => 'AP',
                'brand' => 'Ubiquiti',
                'model' => 'UniFi',
                'serial_number' => 'DEMO-AP-001',
                'location' => 'LAB1',
                'condition' => 'good',
                'status' => 'available',
                'purchase_price' => 1800000,
            ],
            [
                'asset_code' => 'GT-SRV-0001',
                'name' => 'Server Virtualisasi TKJ',
                'category' => 'SRV',
                'brand' => 'Dell',
                'model' => 'PowerEdge',
                'serial_number' => 'DEMO-SRV-001',
                'location' => 'SRV',
                'condition' => 'good',
                'status' => 'available',
                'purchase_price' => 12000000,
            ],
            [
                'asset_code' => 'GT-LTP-0001',
                'name' => 'Laptop Praktikum Jaringan',
                'category' => 'LTP',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad',
                'serial_number' => 'DEMO-LTP-001',
                'location' => 'OFFICE',
                'condition' => 'good',
                'status' => 'borrowed',
                'purchase_price' => 7000000,
            ],
            [
                'asset_code' => 'GT-CAM-0001',
                'name' => 'IP Camera Lab TKJ',
                'category' => 'CAM',
                'brand' => 'Hikvision',
                'model' => 'IP Camera',
                'serial_number' => 'DEMO-CAM-001',
                'location' => 'LAB1',
                'condition' => 'good',
                'status' => 'available',
                'purchase_price' => 850000,
            ],
            [
                'asset_code' => 'GT-NVR-0001',
                'name' => 'NVR CCTV TKJ',
                'category' => 'NVR',
                'brand' => 'Hikvision',
                'model' => 'NVR',
                'serial_number' => 'DEMO-NVR-001',
                'location' => 'SRV',
                'condition' => 'good',
                'status' => 'available',
                'purchase_price' => 1600000,
            ],
            [
                'asset_code' => 'GT-UPS-0001',
                'name' => 'UPS Server TKJ',
                'category' => 'UPS',
                'brand' => 'APC',
                'model' => 'Back-UPS',
                'serial_number' => 'DEMO-UPS-001',
                'location' => 'SRV',
                'condition' => 'major_damage',
                'status' => 'maintenance',
                'purchase_price' => 2000000,
            ],
        ];


        foreach ($assets as $data) {

            $asset = Asset::firstOrNew([
                'asset_code' => $data['asset_code'],
            ]);

            if (! $asset->exists) {
                $asset->qr_token = Str::uuid()->toString();
            }

            $asset->fill([
                'name' => $data['name'],

                'category_id' => $categories[$data['category']]->id,

                'brand_id' => $brands[$data['brand']]->id,

                'model' => $data['model'],

                'serial_number' => $data['serial_number'],

                'location_id' => $locations[$data['location']]->id,

                'condition' => $data['condition'],

                'status' => $data['status'],

                'acquisition_date' => now()
                    ->subMonths(random_int(3, 30))
                    ->toDateString(),

                'funding_source' => 'Dana Sekolah',

                'purchase_price' => $data['purchase_price'],

                'notes' => 'Data demonstrasi GearTrack',
            ]);

            $asset->save();
        }
    }
}