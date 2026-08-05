@php
    $columns = $this->getColumns();
    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
    $invitationCount = count($this->getInvitationOptions());
@endphp

<x-filament-widgets::widget class="fi-wi-stats-overview grid gap-y-4">
    @if ($hasHeading || $hasDescription || $invitationCount > 1)
        <div class="fi-wi-stats-overview-header grid gap-y-1">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="grid gap-y-1">
                    @if ($hasHeading)
                        <h3
                            class="fi-wi-stats-overview-header-heading col-span-full text-base font-semibold leading-6 text-gray-950 dark:text-white"
                        >
                            {{ $heading }}
                        </h3>
                    @endif

                    @if ($hasDescription)
                        <p
                            class="fi-wi-stats-overview-header-description overflow-hidden break-words text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ $description }}
                        </p>
                    @endif
                </div>

                @if ($invitationCount > 1)
                    <div class="w-full md:w-72">
                        {{ $this->form }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div
        @class([
            'fi-wi-stats-overview-stats-ctn grid gap-6',
            'md:grid-cols-1' => $columns === 1,
            'md:grid-cols-2' => $columns === 2,
            'md:grid-cols-3' => $columns === 3,
            'md:grid-cols-2 xl:grid-cols-4' => $columns === 4,
        ])
    >
        @foreach ($this->getCachedStats() as $stat)
            {{ $stat }}
        @endforeach
    </div>
</x-filament-widgets::widget>
