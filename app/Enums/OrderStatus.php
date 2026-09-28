<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Placed = 'placed';
    case Accepted = 'accepted';
    case ReadyForPickup = 'ready_for_pickup';
    case Completed = 'completed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Placed => 'Placed',
            self::Accepted => 'Accepted',
            self::ReadyForPickup => 'Ready for pickup',
            self::Completed => 'Completed',
            self::Declined => 'Declined',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Placed => 'bg-amber-50 text-amber-800',
            self::Accepted => 'bg-sky-50 text-sky-800',
            self::ReadyForPickup => 'bg-tomato-50 text-tomato-800',
            self::Completed => 'bg-leaf-50 text-leaf-800',
            self::Declined, self::Cancelled => 'bg-gray-100 text-gray-700',
        };
    }

    // jo orders abhi chal rahe hain, kisi ke action ka wait
    public static function openStatuses(): array
    {
        return [self::Placed, self::Accepted, self::ReadyForPickup];
    }

    // khatam - ab kisi ko kuch nahi karna
    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Declined, self::Cancelled], true);
    }
}
