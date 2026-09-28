<?php

namespace App\Helpers;

// site pe har price yahin se format hota hai - symbol config('marketlink.currency_symbol') se, "$4.50"
class MoneyFormatter
{
    public static function format(float|string|null $amount): string
    {
        return config('marketlink.currency_symbol').number_format((float) $amount, 2);
    }
}
