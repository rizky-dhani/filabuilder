<?php

namespace Filabuilder\Resources;

use Filabuilder\Enums\PageStatus;
use Filabuilder\Models\FilaBuilderPage;
use Filabuilder\Resources\FilaBuilderPageResource\Pages\CreateFilaBuilderPage;
use Filabuilder\Resources\FilaBuilderPageResource\Pages\EditFilaBuilderPage;
use Filabuilder\Resources\FilaBuilderPageResource\Pages\ListFilaBuilderPages;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use RalphJSmit\Filament\SEO\SEO;

class FilaBuilderPageResource extends Resource
{
    protected static ?string $model = FilaBuilderPage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $slug = 'filabuilder-pages';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'edit') {
                            return;
                        }
                        $set('slug', Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->required()
                    ->unique(FilaBuilderPage::class, 'slug', ignoreRecord: true),

                Select::make('status')
                    ->options([
                        PageStatus::Draft->value => 'Draft',
                        PageStatus::Published->value => 'Publish',
                        PageStatus::Scheduled->value => 'Schedule',
                    ])
                    ->default(PageStatus::Draft->value)
                    ->live(),

                DateTimePicker::make('published_at')
                    ->label('Publish At')
                    ->visible(fn ($get) => $get('status') === PageStatus::Scheduled->value)
                    ->required(fn ($get) => $get('status') === PageStatus::Scheduled->value)
                    ->native(false),

                \Filabuilder\Forms\Components\GrapesJsField::make('content')
                    ->label('Page Content')
                    ->loadDefaultBlocks(config('filabuilder.blocks.default_blocks', true))
                    ->minHeight('70vh')
                    ->columnSpanFull(),

                SEO::make()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'scheduled' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'scheduled' => 'Scheduled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFilaBuilderPages::route('/'),
            'create' => CreateFilaBuilderPage::route('/create'),
            'edit' => EditFilaBuilderPage::route('/{record}/edit'),
        ];
    }
}
