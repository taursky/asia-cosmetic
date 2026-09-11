<?php

namespace App\Filament\Support;

use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductImportExportActions
{
    public static function actions(): array
    {
        return [
            Action::make('importProducts')
                ->label('Импорт')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->schema([
                    FileUpload::make('file')
                        ->label('Файл товаров')
                        // Import files are temporary/private. Product images are saved to public separately.
                        ->disk('local')
                        ->directory('imports/products')
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'text/tab-separated-values',
                        ])
                        ->required(),
                ])
                ->modalHeading('Импорт товаров')
                ->modalDescription('XLS, XLSX, CSV/TSV. Изображения из файла скачиваются на диск public в catalog/products.')
                ->action(function (array $data): void {
                    $relativePath = $data['file'];
                    $path = Storage::disk('local')->path($relativePath);
                    $import = new ProductsImport(locale: app()->getLocale(), source: 'insales');

                    try {
                        Excel::import($import, $path);
                        $stats = $import->stats();

                        $body = sprintf(
                            'Строк: %d; товаров: +%d / ~%d; SKU: +%d / ~%d; атрибутов: +%d; значений атрибутов: +%d; опций: +%d; значений опций: +%d; изображений: %d.',
                            $stats['rows'],
                            $stats['products_created'],
                            $stats['products_updated'],
                            $stats['variants_created'],
                            $stats['variants_updated'],
                            $stats['attributes_created'],
                            $stats['attribute_values_created'],
                            $stats['options_created'],
                            $stats['option_values_created'],
                            $stats['images_written'],
                        );

                        if ($stats['warnings'] !== []) {
                            $body .= ' Предупреждений: ' . count($stats['warnings']) . '.';
                        }

                        Notification::make()->title('Импорт завершён')->body($body)->success()->send();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->title('Ошибка импорта')->body($e->getMessage())->danger()->persistent()->send();
                    } finally {
                        Storage::disk('local')->delete($relativePath);
                    }
                }),

            Action::make('exportProducts')
                ->label('Экспорт XLSX')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(
                    new ProductsExport(app()->getLocale()),
                    'products-' . now()->format('Y-m-d_H-i') . '.xlsx',
                    ExcelFormat::XLSX,
                )),
        ];
    }
}
