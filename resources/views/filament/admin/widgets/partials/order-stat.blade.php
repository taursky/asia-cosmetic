<div class="rounded-xl border bg-white p-5 shadow-sm dark:bg-gray-800">
    <div class="flex items-start justify-between gap-3 p-4">
        <div>
            <div class="text-sm text-gray-500">
                {{ $title }}
            </div>
            <div class=" mt-2 text-2xl font-semibold">
                {{ $value }}
            </div>
        </div>
        <x-filament::icon :icon="$icon" class="h-6 w-6 text-primary-500"/>
    </div>
</div>
