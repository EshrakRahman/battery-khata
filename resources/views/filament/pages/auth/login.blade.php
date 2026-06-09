<x-filament-panels::page.simple>
    {{-- Developer Helper Quick Login Panel --}}
    <div class="p-4 mb-6 rounded-xl border border-dashed border-amber-500/30 bg-amber-500/5 text-center">
        <div class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 mb-2.5">
            ⚡ Quick Login Helper (Testing)
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:justify-center sm:gap-2">
            <x-filament::button
                type="button"
                color="warning"
                size="sm"
                icon="heroicon-m-shield-check"
                wire:click="fillCredentials('admin@test.com', 'password')"
                class="flex-1"
            >
                Admin (Owner)
            </x-filament::button>

            <x-filament::button
                type="button"
                color="info"
                size="sm"
                icon="heroicon-m-user-group"
                wire:click="fillCredentials('manager@test.com', 'password')"
                class="flex-1"
            >
                Manager
            </x-filament::button>

            <x-filament::button
                type="button"
                color="success"
                size="sm"
                icon="heroicon-m-user"
                wire:click="fillCredentials('counterboy@test.com', 'password')"
                class="flex-1"
            >
                Counter Boy
            </x-filament::button>
        </div>
    </div>

    {{ $this->content }}
</x-filament-panels::page.simple>
