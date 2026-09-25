<?php

namespace App\Services;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Throwable;

class SpreadsheetService
{
    /** @param array<string, iterable<list<string|int|float|null>>> $sheets */
    public function write(array $sheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'geartrack-xlsx-');
        if ($path === false) {
            throw new RuntimeException('File sementara tidak dapat dibuat.');
        }

        $writer = new Writer;
        try {
            $writer->openToFile($path);
            $first = true;
            foreach ($sheets as $name => $rows) {
                $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $first = false;
                $sheet->setName($name);
                $header = true;
                foreach ($rows as $values) {
                    $cells = array_map(fn ($value): Cell => is_string($value)
                        ? new StringCell($value, null) : Cell::fromValue($value), $values);
                    $style = $header ? (new Style)->setFontBold()->setBackgroundColor('DBEAFE') : null;
                    $writer->addRow(new Row($cells, $style));
                    $header = false;
                }
            }
            $writer->close();
        } catch (Throwable $exception) {
            $writer->close();
            @unlink($path);
            throw $exception;
        }

        return $path;
    }
}
