<?php

namespace App\Enums;

enum FarmerApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Suspended = 'suspended';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800',
            self::Approved => 'bg-leaf-50 text-leaf-800',
            self::Suspended => 'bg-red-50 text-red-700',
        };
    }
}
