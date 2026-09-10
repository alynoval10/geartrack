<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'PC Desktop',     'asset_prefix' => 'PC'],
            ['name' => 'Monitor',        'asset_prefix' => 'MON'],
            ['name' => 'Laptop',         'asset_prefix' => 'LAP'],
            ['name' => 'Server',         'asset_prefix' => 'SRV'],
            ['name' => 'Router',         'asset_prefix' => 'RT'],
            ['name' => 'Switch',         'asset_prefix' => 'SW'],
            ['name' => 'Access Point',   'asset_prefix' => 'AP'],
            ['name' => 'Printer',        'asset_prefix' => 'PRT'],
            ['name' => 'UPS',            'asset_prefix' => 'UPS'],
            ['name' => 'Rack',           'asset_prefix' => 'RACK'],
            ['name' => 'Network Tools',  'asset_prefix' => 'TOOL'],
            ['name' => 'CCTV',           'asset_prefix' => 'CCTV'],
            ['name' => 'Lainnya',        'asset_prefix' => 'OTH'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'asset_prefix' => $category['asset_prefix'],
                ],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                ]
            );
        }

        $brands = [
            'MikroTik',
            'Cisco',
            'TP-Link',
            'Ubiquiti',
            'Tenda',
            'D-Link',
            'Acer',
            'ASUS',
            'Lenovo',
            'Dell',
            'HP',
            'LG',
            'Samsung',
            'Epson',
            'Canon',
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['name' => $brand],
                ['is_active' => true]
            );
        }
    }
}