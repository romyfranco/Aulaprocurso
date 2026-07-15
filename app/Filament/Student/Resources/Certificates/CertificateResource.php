<?php

namespace App\Filament\Student\Resources\Certificates;

use App\Filament\Resources\Certificates\Schemas\CertificateInfolist;
use App\Filament\Student\Resources\Certificates\Pages\CreateCertificate;
use App\Filament\Student\Resources\Certificates\Pages\EditCertificate;
use App\Filament\Student\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Student\Resources\Certificates\Pages\ViewCertificate;
use App\Filament\Student\Resources\Certificates\Schemas\CertificateForm;
use App\Filament\Student\Resources\Certificates\Tables\CertificatesTable;
use App\Models\Certificate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Mis certificados';

    protected static ?string $modelLabel = 'certificado';

    protected static ?string $recordTitleAttribute = 'certificate_code';

    public static function form(Schema $schema): Schema
    {
        return CertificateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CertificateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CertificatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('student_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'create' => CreateCertificate::route('/create'),
            'view' => ViewCertificate::route('/{record}'),
            'edit' => EditCertificate::route('/{record}/edit'),
        ];
    }
}
