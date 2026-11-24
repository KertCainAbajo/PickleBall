<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case GCash = 'gcash';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::GCash => 'GCash',
            self::Bank => 'Bank transfer',
        };
    }

    /** Cash is handed over in person; the others need a transaction/reference number. */
    public function needsReference(): bool
    {
        return $this !== self::Cash;
    }
}
