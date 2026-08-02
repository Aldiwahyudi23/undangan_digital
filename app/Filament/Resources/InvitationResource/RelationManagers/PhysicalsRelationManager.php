<?php

namespace App\Filament\Resources\InvitationResource\RelationManagers;

use App\Models\InvitationBarcode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhysicalsRelationManager extends RelationManager
{
    protected static string $relationship = 'guests';

    protected static ?string $title = 'Tamu Physical';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nama Tamu')
                    ->maxLength(255),

                Forms\Components\TextInput::make('share_whatsapp')
                    ->label('No WhatsApp (Opsional)')
                    ->tel()
                    ->nullable(),

                Forms\Components\TextInput::make('group_name')
                    ->label('Group')
                    ->maxLength(100),

                Forms\Components\TextInput::make('location_tag')
                    ->label('Lokasi')
                    ->helperText('Ditampilkan di label barcode (opsional)')
                    ->maxLength(100),

                Forms\Components\Toggle::make('create_new_barcode')
                    ->label('Buat Barcode Baru')
                    ->helperText('Aktifkan untuk membuat barcode baru sekaligus membuat tamu ini')
                    ->default(false)
                    ->live(),

                Forms\Components\Select::make('invitation_barcode_id')
                    ->label('Barcode')
                    ->options(function () {
                        $invitationId = $this->getOwnerRecord()->id;

                        return InvitationBarcode::where('invitation_id', $invitationId)
                            ->whereNull('invitation_guest_id')
                            ->pluck('barcode_code', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih barcode (opsional)')
                    ->helperText('Pilih barcode yang belum terpakai')
                    ->nullable()
                    ->hidden(fn (Get $get) => $get('create_new_barcode')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('invitation_type', 'physical'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('group_name')
                    ->label('Group')
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('location_tag')
                    ->label('Lokasi')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('share_whatsapp')
                    ->label('WhatsApp')
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('barcode.barcode_code')
                    ->label('Barcode')
                    ->placeholder('Belum ada barcode')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('barcode.is_used')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Terpakai' : 'Belum')
                    ->color(fn ($state) => $state ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group_name')
                    ->label('Group')
                    ->options(function () {
                        return $this->getOwnerRecord()
                            ->guests()
                            ->where('invitation_type', 'physical')
                            ->whereNotNull('group_name')
                            ->where('group_name', '!=', '')
                            ->pluck('group_name', 'group_name')
                            ->toArray();
                    })
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Tamu Physical')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['invitation_type'] = 'physical';

                        return $data;
                    })
                    ->after(function ($record, $data) {
                        if (! empty($data['create_new_barcode'])) {
                            $this->createNewBarcodeForGuest($record);
                        } elseif (! empty($data['invitation_barcode_id'])) {
                            InvitationBarcode::where('id', $data['invitation_barcode_id'])
                                ->update([
                                    'invitation_guest_id' => $record->id,
                                    'is_used' => true,
                                ]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->after(function ($record, $data) {
                        if (! empty($data['create_new_barcode'])) {
                            $this->disconnectGuestBarcodes($record);
                            $this->createNewBarcodeForGuest($record);
                        } elseif (! empty($data['invitation_barcode_id'])) {
                            $this->disconnectGuestBarcodes($record);

                            InvitationBarcode::where('id', $data['invitation_barcode_id'])
                                ->update([
                                    'invitation_guest_id' => $record->id,
                                    'is_used' => true,
                                ]);
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->after(function ($record) {
                        InvitationBarcode::where('invitation_guest_id', $record->id)
                            ->update([
                                'invitation_guest_id' => null,
                                'is_used' => false,
                            ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function disconnectGuestBarcodes($guest): void
    {
        InvitationBarcode::where('invitation_guest_id', $guest->id)
            ->update([
                'invitation_guest_id' => null,
                'is_used' => false,
            ]);
    }

    protected function createNewBarcodeForGuest($guest): void
    {
        $invitationId = $guest->invitation_id;

        $code = InvitationBarcode::nextCode($invitationId);

        InvitationBarcode::create([
            'invitation_id' => $invitationId,
            'barcode_code' => $code,
            'invitation_guest_id' => $guest->id,
            'is_used' => true,
            'generated_at' => now(),
        ]);
    }
}
