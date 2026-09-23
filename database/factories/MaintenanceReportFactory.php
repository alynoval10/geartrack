<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\MaintenanceReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceReportFactory extends Factory
{
    protected $model = MaintenanceReport::class;

    public function definition(): array
    {
        return [
            'asset_id' => AssetFactory::new(),
            'asset_code' => fn (array $attributes): string => Asset::findOrFail($attributes['asset_id'])->asset_code,
            'asset_name' => fn (array $attributes): string => Asset::findOrFail($attributes['asset_id'])->name,
            'title' => 'Perangkat tidak menyala',
            'type' => 'damage',
            'description' => 'Tidak merespons setelah tombol daya ditekan.',
            'reported_condition' => 'minor_damage',
            'status' => 'open',
            'reported_by' => UserFactory::new(),
        ];
    }
}
