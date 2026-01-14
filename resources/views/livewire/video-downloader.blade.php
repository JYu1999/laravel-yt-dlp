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

            @if ($taskId)
                <div class="space-y-2 rounded-xl border border-zinc-800 bg-zinc-950/40 p-4">
                    <div class="flex items-center gap-2 text-sm text-zinc-200">
                        @if (($progressStatus ?? 'pending') !== 'completed' && ($progressStatus ?? 'pending') !== 'failed')
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-rose-500 border-t-transparent"></span>
                            <span>Downloading…</span>
                        @elseif (($progressStatus ?? 'pending') === 'completed')
                            <span>Completed</span>
                        @else
                            <span>Failed</span>
                        @endif
                    </div>
                    @if ($downloadUrl)
                        <a
                            href="{{ $downloadUrl }}"
                            class="inline-flex items-center text-sm font-semibold text-rose-200 underline underline-offset-4"
                        >
                            Download video
                        </a>
                    @endif
                    @if ($subtitleUrls !== [])
                        <div class="flex flex-wrap gap-3 text-sm">
                            @foreach ($subtitleUrls as $subtitleUrl)
                                <a
                                    href="{{ $subtitleUrl }}"
                                    class="inline-flex items-center text-rose-200 underline underline-offset-4"
                                >
                                    Download subtitles
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>

@once
    <script>
        document.addEventListener('livewire:init', () => {
            let activeChannelName = null;
            let pollingTimer = null;
            let pollingTaskId = null;

            const stopPolling = () => {
                if (pollingTimer) {
                    clearInterval(pollingTimer);
                    pollingTimer = null;
                    pollingTaskId = null;
                }
            };

            const handlePollingPayload = (payload) => {
                if (!payload || !payload.status) {
                    return;
                }

                if (payload.status === 'completed') {
                    Livewire.dispatch('download-completed', { payload: payload });
                    stopPolling();
                    return;
                }

                if (payload.status === 'failed') {
                    Livewire.dispatch('download-failed', payload);
                    stopPolling();
                    return;
                }

                Livewire.dispatch('download-progress-updated', payload);
            };

            const fetchStatus = async (taskId) => {
                try {
                    const response = await fetch(`/api/downloads/${taskId}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    handlePollingPayload(payload.data);
                } catch (error) {
                    // Keep polling on transient failures.
                }
            };

            const startPolling = (taskId) => {
                if (!taskId) {
                    return;
                }

                if (pollingTaskId === taskId) {
                    return;
                }

                stopPolling();
                pollingTaskId = taskId;

                fetchStatus(taskId);
                pollingTimer = window.setInterval(() => fetchStatus(taskId), 2000);
            };

            const subscribeToTask = (taskId) => {
                if (!window.Echo || !taskId) {
                    startPolling(taskId);
                    return;
                }

                const channelName = `download.${taskId}`;

                if (activeChannelName === channelName) {
                    return;
                }

                if (activeChannelName) {
                    window.Echo.leave(activeChannelName);
                }

                const channel = window.Echo.channel(channelName);

                channel.listen('.download.progress.updated', (payload) => {
                    Livewire.dispatch('download-progress-updated', payload);
                });

                channel.listen('.download.completed', (payload) => {
                    Livewire.dispatch('download-completed', { payload: payload });
                });

                channel.listen('.download.failed', (payload) => {
                    Livewire.dispatch('download-failed', payload);
                });

                activeChannelName = channelName;

                const pusher = window.Echo.connector?.pusher;

                if (pusher) {
                    const fallback = () => startPolling(taskId);
                    pusher.connection.bind('error', fallback);
                    pusher.connection.bind('disconnected', fallback);
                    pusher.connection.bind('unavailable', fallback);
                }
            };

            Livewire.on('download-task-created', ({ id }) => {
                subscribeToTask(id);
            });
        });
    </script>
@endonce
