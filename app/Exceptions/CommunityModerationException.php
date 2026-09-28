<?php

namespace App\Exceptions;

use Exception;

// moderation action nahi ho sakta (jaise chautha pin) - message seedha moderator / AI ko dikhta hai
class CommunityModerationException extends Exception
{
}
