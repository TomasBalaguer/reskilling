<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    @if (app()->isLocal())
        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="text-sm font-semibold text-blue-800 mb-2">🔐 Demo Credentials (Local Only)</h3>
            <div class="text-xs text-blue-600">
                <strong>Super Admin:</strong><br>
                Email: admin@reskilling.com<br>
                Password: admin123
            </div>
        </div>
    @endif
</x-filament-panels::page.simple>