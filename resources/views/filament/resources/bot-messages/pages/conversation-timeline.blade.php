<x-filament-panels::page>
    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
            {{ str($messages->last()?->user_name ?: $channelUser)->substr(0, 1)->upper() }}
        </div>
        <div class="flex flex-col">
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ $messages->last()?->user_name ?: $channelUser }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('conversations.timeline.channel') }}: {{ $channel->name }}
                &middot;
                {{ __('conversations.timeline.user') }}: {{ $channelUser }}
            </span>
        </div>
    </div>

    @if ($messages->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
            {{ __('conversations.timeline.empty') }}
        </div>
    @else
        <div class="flex flex-col gap-1.5">
            @php $previousDate = null; @endphp

            @foreach ($messages as $index => $message)
                @php $currentDate = $message->created_at->toDateString(); @endphp

                @if ($currentDate !== $previousDate)
                    <div data-date-divider class="flex items-center gap-3 py-2 first:pt-0">
                        <div class="h-px flex-1 bg-gray-200 dark:bg-white/10"></div>
                        <span class="shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">
                            @if ($message->created_at->isToday())
                                {{ __('conversations.timeline.today') }}
                            @elseif ($message->created_at->isYesterday())
                                {{ __('conversations.timeline.yesterday') }}
                            @else
                                {{ $message->created_at->format('d/m/Y') }}
                            @endif
                        </span>
                        <div class="h-px flex-1 bg-gray-200 dark:bg-white/10"></div>
                    </div>
                    @php $previousDate = $currentDate; @endphp
                @endif

                @if ($this->hasOlderHistory() && $index === $this->getRecentWindowStartIndex())
                    <div data-conversation-divider class="flex items-center gap-3 py-1">
                        <div class="h-px flex-1 bg-gray-200 dark:bg-white/10"></div>
                        <span class="shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ __('conversations.timeline.older_history_hint') }}
                        </span>
                        <div class="h-px flex-1 bg-gray-200 dark:bg-white/10"></div>
                    </div>
                @endif

                <div
                    data-conversation-exchange
                    data-conversation-window="{{ $index >= $this->getRecentWindowStartIndex() ? 'recent' : 'older' }}"
                    class="flex flex-col gap-1.5 py-1.5 {{ $index >= $this->getRecentWindowStartIndex() ? '' : 'opacity-60' }}"
                >
                    <div data-message-role="question" class="flex flex-col items-start gap-1">
                        <div class="max-w-[80%] rounded-2xl rounded-bl-md bg-gray-100 px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap wrap-break-word text-gray-950 dark:bg-white/5 dark:text-white">{{ trim($message->question) }}</div>
                        <span class="px-1 text-[11px] text-gray-400 dark:text-gray-500">
                            {{ $message->created_at->format('H:i') }}
                        </span>
                    </div>

                    <div data-message-role="answer" class="flex flex-col items-end gap-1">
                        <div class="max-w-[80%] rounded-2xl rounded-br-md bg-primary-600 px-4 py-2.5 text-sm leading-relaxed whitespace-pre-wrap wrap-break-word text-white">{{ trim($message->answer) }}</div>

                        <div class="flex items-center gap-1.5 px-1 text-[11px] text-gray-400 dark:text-gray-500">
                            <span>{{ $message->created_at->format('H:i') }}</span>

                            @if ($message->response_ms !== null)
                                <span>&middot; {{ $message->formatResponseMs() }}</span>
                            @endif

                            @if ($message->isEscalated())
                                <span class="rounded-full bg-danger-50 px-2 py-0.5 font-medium text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                                    {{ __('conversations.fields.escalated') }}
                                </span>
                            @endif

                            @if ($message->was_helpful !== null)
                                <span>{{ $message->was_helpful ? '👍' : '👎' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
