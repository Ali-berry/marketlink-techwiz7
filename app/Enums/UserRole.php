<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Farmer = 'farmer';
    case Customer = 'customer';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    // community post pe naam ke saath role pill - farmer green, customer blue, admin orange
    public function pillClasses(): string
    {
        return match ($this) {
            self::Farmer => 'border-leaf-500/25 bg-leaf-500/10 text-leaf-700',
            self::Admin => 'border-tomato-500/25 bg-tomato-500/10 text-tomato-700',
            self::Customer => 'border-sky-500/25 bg-sky-500/10 text-sky-700',
        };
    }

    // login ke baad har role apne panel pe jata hai
    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Farmer => 'farmer.dashboard',
            self::Customer => 'customer.dashboard',
        };
    }
}
