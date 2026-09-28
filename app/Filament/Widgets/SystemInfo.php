<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class SystemInfo extends Widget
{
    protected string $view = 'filament.admin.widgets.system-info';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'lg' => 1,
    ];

    public function getSystemInfo(): array
    {
        $composerLockPath = base_path('composer.lock');
        $fVersion = null;
        if (file_exists($composerLockPath)) {
            $lockData = json_decode(file_get_contents($composerLockPath), true);
            foreach ($lockData['packages'] ?? [] as $pkg) {
                if ($pkg['name'] === 'filament/filament') {
                    $fVersion = $pkg['version']; // например, v3.2.1
                    break;
                }
            }
        }

        return [
            [
                'label' => 'PHP Version',
                'value' => PHP_VERSION,
                'icon' => 'heroicon-o-code-bracket',
            ],
            [
                'label' => 'Laravel Version',
                'value' => app()->version(),
                'icon' => 'heroicon-o-cog',
            ],
            [
                'label' => 'Filament Version',
                'value' => $fVersion,
                'icon' => 'heroicon-o-cog',
            ],
            [
                'label' => 'Server Time',
                'value' => Carbon::now()->format('M j, Y g:i A'),
                'icon' => 'heroicon-o-clock',
            ],
            [
                'label' => 'Environment',
                'value' => app()->environment(),
                'icon' => 'heroicon-o-server',
            ],
        ];
    }
}
