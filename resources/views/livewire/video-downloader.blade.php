<div class="mx-auto w-full max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
    <div class="space-y-4 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-5 shadow-lg shadow-black/20 sm:p-6">
        <flux:field>
            <flux:label for="video-url">YouTube URL</flux:label>
            <flux:input
                id="video-url"
                type="url"
                wire:model.blur="url"
                placeholder="https://www.youtube.com/watch?v=..."
            />
            <flux:error name="url" />
        </flux:field>

        @if ($error)
            <flux:callout variant="danger">
                <flux:callout.heading>Unable to fetch metadata</flux:callout.heading>
                <flux:callout.text>{{ $error }}</flux:callout.text>
            </flux:callout>
        @endif

        @if ($downloadNotice)
            <flux:callout variant="success" class="border border-rose-500/60 bg-rose-950/40 text-rose-100 ring-1 ring-rose-500/40">
                <flux:callout.heading class="text-base font-semibold text-rose-100">
                    Download Ready
                </flux:callout.heading>
                <flux:callout.text class="text-sm text-rose-100/90">{{ $downloadNotice }}</flux:callout.text>
            </flux:callout>
        @endif

        @if ($downloadError)
            <flux:callout variant="danger">
                <flux:callout.heading>Download blocked</flux:callout.heading>
                <flux:callout.text>{{ $downloadError }}</flux:callout.text>
            </flux:callout>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <flux:button
                variant="primary"
                wire:click="fetchInfo"
                wire:loading.attr="disabled"
                wire:target="fetchInfo"
                class="min-h-[44px]"
            >
                Get Video Info
            </flux:button>
            <div wire:loading wire:target="fetchInfo" class="text-sm text-gray-500">Loading metadata…</div>
        </div>
    </div>

    @if (!empty($metadata))
        <div class="space-y-4 rounded-2xl border border-zinc-800 bg-zinc-900/80 p-5 shadow-lg shadow-black/20">
            <div class="flex flex-col gap-4 md:flex-row">
                @if (!empty($metadata['thumbnail']))
                    <img
                        src="{{ $metadata['thumbnail'] }}"
                        alt="{{ $metadata['title'] ?? 'Video thumbnail' }}"
                        class="h-40 w-full rounded-md object-cover md:w-64"
                    />
                @endif
                <div class="space-y-2">
                    <h2 class="text-lg font-semibold">{{ $metadata['title'] ?? 'Untitled video' }}</h2>
                    <p class="text-sm text-zinc-300">Duration: {{ $metadata['duration_formatted'] ?? 'N/A' }}</p>
                    <p class="text-sm text-zinc-300">
                        Estimated size: {{ $metadata['estimated_filesize'] ?? 'N/A' }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <flux:field variant="inline">
                    <flux:checkbox wire:model.live="downloadSubtitles" />
                    <flux:label>Download Subtitles</flux:label>
                </flux:field>

                @if ($downloadSubtitles)
                    <flux:field>
                        <flux:label for="subtitle-language">Subtitle Language</flux:label>
                        <flux:select id="subtitle-language" wire:model.live="selectedLanguage">
                            <option value="">Select language</option>
                            @foreach ($metadata['subtitles'] ?? [] as $language)
                                <option value="{{ $language }}">{{ $language }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="selectedLanguage" />
                    </flux:field>
                @endif
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <flux:button
                    variant="primary"
                    wire:click="startDownload"
                    wire:loading.attr="disabled"
                    wire:target="startDownload"
                    class="min-h-[44px]"
                >
                    Start Download
                </flux:button>
                <div wire:loading wire:target="startDownload" class="text-sm text-gray-500">
                    Preparing download…
                </div>
            </div>
        </div>
    @endif
</div>
