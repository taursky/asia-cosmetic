<?php

namespace App\Imports;

use App\Services\Catalog\ProductImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class ProductsImport implements ToCollection, WithChunkReading, WithCustomCsvSettings
{
    private ?array $headings = null;

    private ProductImportService $service;

    public function __construct(string $locale = 'ru', string $source = 'insales')
    {
        $this->service = new ProductImportService($locale, $source);
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $rawRow) {
            $values = $rawRow instanceof Collection ? $rawRow->all() : (array) $rawRow;

            if ($this->headings === null) {
                $this->headings = array_map(
                    static fn ($value): string => trim((string) $value),
                    $values,
                );

                continue;
            }

            $values = array_pad($values, count($this->headings), null);
            $row = array_combine($this->headings, array_slice($values, 0, count($this->headings)));

            if ($row !== false) {
                $this->service->importRow($row);
            }
        }
    }

    public function chunkSize(): int
    {
        return 250;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => "\t",
            'input_encoding' => 'UTF-16LE',
        ];
    }

    public function stats(): array
    {
        return $this->service->stats();
    }
}
