<?php

namespace App\Exceptions;

use RuntimeException;

// status change allowed nahi - ya move hi nahi hai, ya ye user nahi kar sakta
class InvalidOrderTransitionException extends RuntimeException
{
}
