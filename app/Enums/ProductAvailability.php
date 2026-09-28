<?php

namespace App\Enums;

enum ProductAvailability: string
{
    case Available = 'available';
    case SoldOut = 'sold_out';
    case TemporarilyUnavailable = 'temporarily_unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::SoldOut => 'Sold out',
            self::TemporarilyUnavailable => 'Not this week',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Available => 'bg-leaf-50 text-leaf-800',
            self::SoldOut => 'bg-gray-100 text-gray-700',
            self::TemporarilyUnavailable => 'bg-amber-50 text-amber-800',
        };
    }
}
