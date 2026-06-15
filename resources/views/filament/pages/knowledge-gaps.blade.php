<x-filament-panels::page>
    @php($feedbackCounts = $this->feedbackCounts())
    @php($indexingErrorCount = $this->indexingErrorCount())

    @if ($indexingErrorCount > 0)
        <x-filament::section>
            <a href="{{ \App\Filament\Resources\Documents\DocumentResource::getUrl('index') }}" class="text-sm font-medium text-danger-600 hover:underline dark:text-danger-400">
                ⚠️ {{ __('knowledge_gaps.indexing_errors', ['count' => $indexingErrorCount]) }}
            </a>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">
            {{ __('knowledge_gaps.feedback.heading') }}
        </x-slot>

        <div class="flex flex-wrap gap-6 text-sm">
            <span>👍 {{ $feedbackCounts['positive'] }} {{ __('knowledge_gaps.feedback.positive') }}</span>
            <span>👎 {{ $feedbackCounts['negative'] }} {{ __('knowledge_gaps.feedback.negative') }}</span>
            <span>— {{ $feedbackCounts['unrated'] }} {{ __('knowledge_gaps.feedback.unrated') }}</span>
        </div>
    </x-filament::section>

    <x-filament::tabs label="{{ __('knowledge_gaps.navigation_label') }}">
        @foreach ($this->getTabs() as $key => $label)
            <x-filament::tabs.item
                :active="$this->activeTab === $key"
                wire:click="setActiveTab('{{ $key }}')"
            >
                {{ $label }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    @if ($this->activeTab === 'recurring' && $this->selectedQuestionNormalized !== null)
        <div>
            <x-filament::button color="gray" wire:click="backToGroups" icon="heroicon-o-arrow-left">
                {{ __('knowledge_gaps.actions.back_to_groups') }}
            </x-filament::button>
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
