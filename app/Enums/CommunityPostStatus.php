<?php

namespace App\Enums;

enum CommunityPostStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-800',
            self::Approved => 'bg-leaf-50 text-leaf-800',
            self::Rejected => 'bg-red-50 text-red-700',
        };
    }
}
