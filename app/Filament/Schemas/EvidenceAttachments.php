<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\ViewEntry;

class EvidenceAttachments
{
    /** Build the shared evidence upload field used by operational forms. */
    public static function field(string $directory, string $name = 'attachments'): FileUpload
    {
        return FileUpload::make($name)
            ->label('Lampiran Foto / Dokumen')
            ->disk('public')
            ->directory($directory)
            ->multiple()
            ->acceptedFileTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/pdf',
            ])
            ->maxSize(10240)
            ->maxFiles(10)
            ->reorderable()
            ->openable()
            ->downloadable()
            ->helperText('Maksimal 10 berkas. Format JPG, PNG, WebP, atau PDF; maksimal 10 MB per berkas.')
            ->columnSpanFull();
    }

    /** Build the shared evidence viewer used by record detail pages. */
    public static function entry(string $name = 'attachments'): ViewEntry
    {
        return ViewEntry::make($name)
            ->label('Lampiran Foto / Dokumen')
            ->view('filament.infolists.evidence-attachments')
            ->columnSpanFull();
    }
}
