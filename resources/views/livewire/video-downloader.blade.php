<div class="space-y-6">
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

    <div class="flex items-center gap-3">
        <flux:button variant="primary" wire:click="fetchInfo" wire:loading.attr="disabled">
            Fetch info
        </flux:button>
        <div wire:loading class="text-sm text-gray-500">Loading metadata…</div>
    </div>

    @if (!empty($metadata))
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4">
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
                    <p class="text-sm text-gray-600">Duration: {{ $metadata['duration_formatted'] ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-600">
                        Estimated size: {{ $metadata['estimated_filesize'] ?? 'N/A' }}
                    </p>
                </div>
            </div>

            <flux:field>
                <flux:label for="format">Format</flux:label>
                <flux:select id="format" wire:model="selectedFormat">
                    <option value="">Select a format</option>
                    @foreach ($metadata['formats'] ?? [] as $format)
                        <option value="{{ $format['format_id'] }}">
                            {{ strtoupper($format['ext'] ?? '') }}
                            @if (!empty($format['height']))
                                {{ $format['height'] }}p
                            @endif
                            @if (!empty($format['filesize']))
                                ({{ number_format($format['filesize'] / 1024 / 1024, 2) }} MB)
                            @endif
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="space-y-2">
                <flux:checkbox wire:model="downloadSubtitles">
                    Download Subtitles
                </flux:checkbox>

                @if ($downloadSubtitles)
                    <flux:field>
                        <flux:label for="subtitle-language">Subtitle Language</flux:label>
                        <flux:select id="subtitle-language" wire:model="selectedSubtitleLanguage">
                            <option value="">Select language</option>
                            @foreach ($metadata['subtitles'] ?? [] as $language)
                                <option value="{{ $language }}">{{ $language }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                @endif
            </div>
        </div>
    @endif
</div>
