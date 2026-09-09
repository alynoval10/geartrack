<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Category;

class AssetCodeGenerator
{
    public static function generate(int $categoryId): string
    {
        $category = Category::findOrFail($categoryId);

        $prefix = 'GT-' . strtoupper($category->code) . '-';

        $lastAsset = Asset::where('asset_code', 'like', $prefix . '%')
            ->orderByDesc('asset_code')
            ->first();

        $lastNumber = 0;

        if ($lastAsset) {
            $lastNumber = (int) substr($lastAsset->asset_code, strlen($prefix));
        }

        $nextNumber = $lastNumber + 1;

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}