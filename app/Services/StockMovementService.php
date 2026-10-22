<?php

namespace App\Services;

use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function record(
        StockItem $stockItem,
        User $user,
        string $type,
        string $quantity,
        ?string $notes = null,
        ?string $unitPrice = null,
    ): StockMovement {
        if (! in_array($type, ['in', 'out'], true)) {
            throw ValidationException::withMessages(['type' => 'Jenis transaksi stok tidak valid.']);
        }

        Validator::make([
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'notes' => $notes,
        ], [
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999', 'decimal:0,3'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($stockItem, $user, $type, $quantity, $notes, $unitPrice): StockMovement {
            $lockedItem = StockItem::query()->lockForUpdate()->findOrFail($stockItem->getKey());
            if (! $lockedItem->is_active) {
                throw ValidationException::withMessages(['quantity' => 'Barang nonaktif tidak dapat menerima transaksi stok.']);
            }
            $quantityUnits = $this->toThousandths($quantity);
            $stockBeforeUnits = $this->toThousandths((string) $lockedItem->current_stock);
            $stockAfterUnits = $type === 'in'
                ? $stockBeforeUnits + $quantityUnits
                : $stockBeforeUnits - $quantityUnits;

            if ($stockAfterUnits > 999_999_999_999) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah stok melebihi kapasitas pencatatan.']);
            }

            if ($stockAfterUnits < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tidak mencukupi. Stok tersedia: '.$lockedItem->current_stock.' '.$lockedItem->unit.'.',
                ]);
            }

            $lockedItem->current_stock = $this->fromThousandths($stockAfterUnits);
            if ($type === 'in' && filled($unitPrice)) {
                $lockedItem->unit_price = $unitPrice;
            }
            $lockedItem->save();

            return $lockedItem->movements()->create([
                'user_id' => $user->getKey(),
                'type' => $type,
                'quantity' => $this->fromThousandths($quantityUnits),
                'stock_before' => $this->fromThousandths($stockBeforeUnits),
                'stock_after' => $this->fromThousandths($stockAfterUnits),
                'unit_price' => $type === 'in' ? $unitPrice : null,
                'notes' => filled($notes) ? trim($notes) : null,
            ]);
        });
    }

    private function toThousandths(string $amount): int
    {
        return (int) round((float) $amount * 1000);
    }

    private function fromThousandths(int $amount): string
    {
        return number_format($amount / 1000, 3, '.', '');
    }
}
