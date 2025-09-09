<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationLabel = 'Empresas';
    
    protected static ?string $modelLabel = 'Empresa';
    
    protected static ?string $pluralModelLabel = 'Empresas';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subdomain')
                            ->label('Subdominio')
                            ->helperText('Opcional: para acceso personalizado')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email de Contacto')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('logo_url')
                            ->label('Logo')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Máximo 2MB. Formatos: JPG, PNG'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Configuración de Límites')
                    ->schema([
                        Forms\Components\TextInput::make('max_campaigns')
                            ->label('Máximo de Campañas')
                            ->required()
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Cantidad máxima de campañas simultáneas'),
                        Forms\Components\TextInput::make('max_responses_per_campaign')
                            ->label('Máximo de Respuestas por Campaña')
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->minValue(1)
                            ->maxValue(5000)
                            ->helperText('Límite de empleados que pueden responder cada campaña'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Empresa Activa')
                            ->helperText('Solo empresas activas pueden crear campañas')
                            ->default(true),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Configuración Avanzada')
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->label('Configuraciones Adicionales')
                            ->helperText('Configuraciones específicas en formato clave-valor')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl('/images/default-company.png'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('campaigns_count')
                    ->label('Campañas')
                    ->counts('campaigns')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_responses')
                    ->label('Respuestas')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('max_campaigns')
                    ->label('Límite Campañas')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('max_responses_per_campaign')
                    ->label('Límite Respuestas')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas las empresas')
                    ->trueLabel('Solo empresas activas')
                    ->falseLabel('Solo empresas inactivas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Company $record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (Company $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Company $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Company $record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
