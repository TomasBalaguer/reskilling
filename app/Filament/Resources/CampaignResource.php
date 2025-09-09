<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Filament\Resources\CampaignResource\RelationManagers;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $navigationLabel = 'Campañas';
    
    protected static ?string $modelLabel = 'Campaña';
    
    protected static ?string $pluralModelLabel = 'Campañas';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Empresa')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Campaña')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->helperText('Descripción interna de la campaña')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Configuración de Acceso')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código de Campaña')
                            ->helperText('Se genera automáticamente si se deja vacío')
                            ->maxLength(20),
                        Forms\Components\Toggle::make('public_link_enabled')
                            ->label('Link Público Habilitado')
                            ->helperText('Permite acceso sin autenticación')
                            ->default(true),
                        Forms\Components\TextInput::make('public_link_code')
                            ->label('Código Adicional de Seguridad')
                            ->helperText('Código extra opcional para mayor seguridad')
                            ->maxLength(50),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Límites y Fechas')
                    ->schema([
                        Forms\Components\TextInput::make('max_responses')
                            ->label('Máximo de Respuestas')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5000)
                            ->default(100),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'active' => 'Activa',
                                'paused' => 'Pausada',
                                'completed' => 'Completada',
                                'archived' => 'Archivada',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\DateTimePicker::make('active_from')
                            ->label('Activa Desde')
                            ->required()
                            ->default(now()),
                        Forms\Components\DateTimePicker::make('active_until')
                            ->label('Activa Hasta')
                            ->required()
                            ->default(now()->addDays(30)),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Cuestionarios')
                    ->schema([
                        Forms\Components\CheckboxList::make('questionnaires')
                            ->label('Cuestionarios a Incluir')
                            ->relationship('questionnaires', 'name')
                            ->helperText('Selecciona los cuestionarios que formarán parte de esta campaña')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Configuración Avanzada')
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->label('Configuraciones Adicionales')
                            ->helperText('Configuraciones específicas para esta campaña')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Campaña')
                    ->searchable()
                    ->weight('medium')
                    ->limit(30),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                        'warning' => 'paused',
                        'primary' => 'completed',
                        'danger' => 'archived',
                    ])
                    ->icons([
                        'heroicon-o-pencil' => 'draft',
                        'heroicon-o-play' => 'active',
                        'heroicon-o-pause' => 'paused',
                        'heroicon-o-check' => 'completed',
                        'heroicon-o-archive-box' => 'archived',
                    ]),
                Tables\Columns\TextColumn::make('responses_count')
                    ->label('Respuestas')
                    ->badge()
                    ->color('success')
                    ->suffix(fn ($record) => " / {$record->max_responses}"),
                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Progreso')
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('public_url')
                    ->label('Link Público')
                    ->limit(30)
                    ->copyable()
                    ->copyMessage('Link copiado')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('active_from')
                    ->label('Desde')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('active_until')
                    ->label('Hasta')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state < now() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('questionnaires_count')
                    ->label('Cuestionarios')
                    ->counts('questionnaires')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Empresa')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'active' => 'Activa',
                        'paused' => 'Pausada',
                        'completed' => 'Completada',
                        'archived' => 'Archivada',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Está Activa')
                    ->placeholder('Todas')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('copy_link')
                    ->label('Copiar Link')
                    ->icon('heroicon-o-link')
                    ->action(fn () => null)
                    ->url(fn (Campaign $record) => $record->public_url)
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('view_responses')
                    ->label('Ver Respuestas')
                    ->icon('heroicon-o-users')
                    ->url(fn (Campaign $record) => "/admin/campaigns/{$record->id}/responses")
                    ->color('info'),
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
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
