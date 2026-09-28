<?php

namespace App\Enums;

enum EmailCheckResult: string
{
    // UNKNOWN ko bhi allow karte hain
    case Deliverable = 'deliverable';

    // format galat, UNDELIVERABLE, ya disposable email
    case Undeliverable = 'undeliverable';

    // key nahi, timeout, 429, 500 - phir Laravel ka apna check chalta hai
    case CouldNotCheck = 'could_not_check';
}
