<?php

namespace App\Enums;

enum LocationType: string
{
    case HeadOffice = 'head_office';
    case BranchOffice = 'branch_office';
    case Mine = 'mine';

    public function label(): string
    {
        return match ($this) {
            self::HeadOffice => 'Kantor Pusat',
            self::BranchOffice => 'Kantor Cabang',
            self::Mine => 'Tambang',
        };
    }
}
