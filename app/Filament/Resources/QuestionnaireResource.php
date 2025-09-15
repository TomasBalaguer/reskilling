<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionnaireResource\Pages;
use App\Filament\Resources\QuestionnaireResource\RelationManagers;
use App\Models\Questionnaire;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionnaireResource extends Resource
{
    protected static ?string $model = Questionnaire::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Cuestionarios';
    
    protected static ?string $modelLabel = 'Cuestionario';
    
    protected static ?string $pluralModelLabel = 'Cuestionarios';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Cuestionario')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->helperText('Descripción detallada del propósito del cuestionario')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('intro')
                            ->label('Introducción')
                            ->helperText('Texto de introducción que se mostrará antes de comenzar el cuestionario')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'redo',
                                'undo',
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Select::make('scoring_type')
                            ->label('Tipo de Puntuación')
                            ->required()
                            ->options([
                                'REFLECTIVE_QUESTIONS' => 'Preguntas Reflexivas (Audio + IA)',
                                'PERSONALITY_ASSESSMENT' => 'Evaluación de Personalidad',
                                'LEADERSHIP_POTENTIAL' => 'Potencial de Liderazgo',
                                'COMMUNICATION_SKILLS' => 'Habilidades de Comunicación',
                                'TEAM_COLLABORATION' => 'Colaboración en Equipo',
                                'PROBLEM_SOLVING' => 'Resolución de Problemas',
                                'EMOTIONAL_INTELLIGENCE' => 'Inteligencia Emocional',
                            ])
                            ->searchable(),
                        Forms\Components\TextInput::make('max_duration_minutes')
                            ->label('Duración Máxima (minutos)')
                            ->required()
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(60)
                            ->helperText('Tiempo máximo por respuesta de audio'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Cuestionario Activo')
                            ->helperText('Solo cuestionarios activos aparecen en las campañas')
                            ->default(true),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Preguntas')
                    ->schema([
                        Forms\Components\Repeater::make('questions_list')
                            ->label('Lista de Preguntas')
                            ->schema([
                                Forms\Components\TextInput::make('id')
                                    ->label('ID')
                                    ->required()
                                    ->placeholder('q1, q2, q3...'),
                                Forms\Components\Textarea::make('text')
                                    ->label('Pregunta')
                                    ->required()
                                    ->rows(2),
                                Forms\Components\TextInput::make('order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(1),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Pregunta')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['text'] ?? 'Nueva pregunta')
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, $set) {
                                // Convert repeater data to questions array format
                                $questions = [];
                                foreach ($state ?? [] as $item) {
                                    if (isset($item['id']) && isset($item['text'])) {
                                        $questions[$item['id']] = $item['text'];
                                    }
                                }
                                $set('questions', $questions);
                            }),
                        Forms\Components\Hidden::make('questions'),
                    ]),
                    
                Forms\Components\Section::make('Configuración Avanzada')
                    ->schema([
                        Forms\Components\KeyValue::make('settings')
                            ->label('Configuraciones Adicionales')
                            ->helperText('Configuraciones específicas para este tipo de cuestionario')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->weight('medium')
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('scoring_type')
                    ->label('Tipo de Evaluación')
                    ->colors([
                        'primary' => 'REFLECTIVE_QUESTIONS',
                        'success' => 'PERSONALITY_ASSESSMENT', 
                        'info' => 'LEADERSHIP_POTENTIAL',
                        'warning' => 'COMMUNICATION_SKILLS',
                        'secondary' => 'TEAM_COLLABORATION',
                        'danger' => 'PROBLEM_SOLVING',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'REFLECTIVE_QUESTIONS' => 'Preguntas Reflexivas',
                            'PERSONALITY_ASSESSMENT' => 'Evaluación Personalidad',
                            'LEADERSHIP_POTENTIAL' => 'Potencial Liderazgo',
                            'COMMUNICATION_SKILLS' => 'Habilidades Comunicación',
                            'TEAM_COLLABORATION' => 'Colaboración Equipo',
                            'PROBLEM_SOLVING' => 'Resolución Problemas',
                            'EMOTIONAL_INTELLIGENCE' => 'Inteligencia Emocional',
                            default => $state
                        };
                    }),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Preguntas')
                    ->getStateUsing(fn ($record) => is_array($record->questions) ? count($record->questions) : 0)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('max_duration_minutes')
                    ->label('Duración Máx.')
                    ->numeric()
                    ->sortable()
                    ->suffix(' min')
                    ->color('secondary'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('campaigns_count')
                    ->label('Campañas')
                    ->counts('campaigns')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scoring_type')
                    ->label('Tipo de Evaluación')
                    ->options([
                        'REFLECTIVE_QUESTIONS' => 'Preguntas Reflexivas',
                        'PERSONALITY_ASSESSMENT' => 'Evaluación Personalidad',
                        'LEADERSHIP_POTENTIAL' => 'Potencial Liderazgo',
                        'COMMUNICATION_SKILLS' => 'Habilidades Comunicación',
                        'TEAM_COLLABORATION' => 'Colaboración Equipo',
                        'PROBLEM_SOLVING' => 'Resolución Problemas',
                        'EMOTIONAL_INTELLIGENCE' => 'Inteligencia Emocional',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))
                    ->successNotificationTitle(fn ($record) => $record->is_active ? 'Cuestionario activado' : 'Cuestionario desactivado'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
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
            'index' => Pages\ListQuestionnaires::route('/'),
            'create' => Pages\CreateQuestionnaire::route('/create'),
            'edit' => Pages\EditQuestionnaire::route('/{record}/edit'),
        ];
    }
}
