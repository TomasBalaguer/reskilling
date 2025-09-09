<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    @if (app()->isLocal())
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <h3 class="text-sm font-semibold text-green-800 mb-2">🏢 Demo Company Credentials (Local Only)</h3>
            <div class="text-xs text-green-600">
                <strong>Company Admin:</strong><br>
                Email: company@demo.com<br>
                Password: company123
            </div>
        </div>
    @endif
</x-filament-panels::page.simple>