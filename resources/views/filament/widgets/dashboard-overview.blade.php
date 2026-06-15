<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($cards as $card)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        @if (count($actions) > 0)
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($actions as $action)
                    <x-filament::button tag="a" :href="$action['url']" :icon="$action['icon']" color="gray" outlined>
                        {{ $action['label'] }}
                    </x-filament::button>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
