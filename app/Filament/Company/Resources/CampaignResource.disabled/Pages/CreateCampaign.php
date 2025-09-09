<?php

namespace App\Filament\Company\Resources\CampaignResource\Pages;

use App\Filament\Company\Resources\CampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::auth()->user()->company_id;
        
        // Generate code if not provided
        if (empty($data['code'])) {
            $data['code'] = strtoupper(uniqid('CAMP'));
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}