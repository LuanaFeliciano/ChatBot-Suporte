<x-filament-widgets::widget>
    <x-filament::section :heading="__('analytics.knowledge_base.heading')">
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="fi-section-header-heading text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('analytics.knowledge_base.status_counts') }}
                </h3>

                <ul class="mt-2 flex flex-col gap-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($statusCounts as $label => $count)
                        <li class="flex items-center justify-between gap-2">
                            <span>{{ $label }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 flex items-center justify-between gap-2 text-sm">
                    <span class="text-gray-700 dark:text-gray-300">{{ __('analytics.knowledge_base.total_indexed') }}</span>
                    <span class="font-medium text-gray-950 dark:text-white">{{ $totalIndexed }}</span>
                </div>

                <div class="mt-1 flex items-center justify-between gap-2 text-sm">
                    <span class="text-gray-700 dark:text-gray-300">{{ __('analytics.knowledge_base.total_indexed_size') }}</span>
                    <span class="font-medium text-gray-950 dark:text-white">{{ \Illuminate\Support\Number::fileSize($totalIndexedSize) }}</span>
                </div>
            </div>

            <div>
                <h3 class="fi-section-header-heading text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('analytics.knowledge_base.error_documents') }}
                </h3>

                @if ($errorDocuments->isEmpty())
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('analytics.knowledge_base.no_errors') }}
                    </p>
                @else
                    <ul class="mt-2 flex flex-col gap-1 text-sm">
                        @foreach ($errorDocuments as $document)
                            <li>
                                <a href="{{ $this->documentUrl($document) }}" class="text-primary-600 hover:underline dark:text-primary-400">
                                    {{ $document->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <h3 class="fi-section-header-heading mt-6 text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('analytics.knowledge_base.recently_updated') }}
                </h3>

                @if ($recentlyUpdated->isEmpty())
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('analytics.knowledge_base.no_recent_updates') }}
                    </p>
                @else
                    <ul class="mt-2 flex flex-col gap-1 text-sm text-gray-700 dark:text-gray-300">
                        @foreach ($recentlyUpdated as $auditLog)
                            <li class="flex items-center justify-between gap-2">
                                <span>{{ $auditLog->payload['name'] ?? __('analytics.knowledge_base.heading') }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $auditLog->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
