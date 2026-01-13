<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:header container class="bg-white border-b border-gray-100 dark:bg-zinc-900 dark:border-zinc-700">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />

    <flux:brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="Laravel" class="max-lg:hidden dark:hidden" />
    <flux:brand href="#" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="Laravel" class="max-lg:hidden hidden dark:flex" />

    <flux:navbar class="-mb-px max-lg:hidden">
        <flux:navbar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:navbar.item>
    </flux:navbar>

    <flux:spacer />

    <flux:dropdown position="top" align="end">
        <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />

        <flux:menu>
            <flux:menu.radio.group>
                <div class="p-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="font-medium text-gray-800 dark:text-gray-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="font-medium text-xs">{{ auth()->user()->email }}</div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator />

            <flux:menu.item icon="user" :href="route('profile')" wire:navigate>{{ __('Profile') }}</flux:menu.item>
            <flux:menu.item icon="arrow-right-start-on-rectangle" wire:click="logout">{{ __('Log Out') }}</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:header>
