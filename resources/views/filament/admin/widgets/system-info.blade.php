<x-filament-widgets::widget>
    <x-filament::section heading="Информация о системе" icon="heroicon-o-information-circle">
        <div class="space-y-3">
            @foreach($this->getSystemInfo() as $info)
                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <x-filament::icon
                            :icon="$info['icon']"
                            class="h-5 w-5 text-gray-500"
                        />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $info['label'] }}
                        </span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">
                        {{ $info['value'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
