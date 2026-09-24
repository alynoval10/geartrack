<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Filament\Schemas\AssetSelectionFields;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Peminjaman Perangkat')->description('Hanya perangkat berkondisi baik tanpa peminjaman atau laporan perawatan aktif yang dapat dipinjam.')
                ->schema([
                    TextInput::make('borrower_name')->label('Nama Peminjam')->required()->maxLength(150),
                    TextInput::make('borrower_contact')->label('Kelas / Kontak Peminjam')->maxLength(150),
                    TextInput::make('responsible_name')->label('Penanggung Jawab')->required()->maxLength(150),
                    DatePicker::make('due_date')->label('Batas Pengembalian')->default(today()->addDays(7))->minDate(today())->required(),
                    ...AssetSelectionFields::make(),
                    Textarea::make('purpose')->label('Keperluan')->required()->maxLength(5000)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
        ]);
    }
}
