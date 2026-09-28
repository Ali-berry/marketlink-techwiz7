<?php

namespace App\Enums;

// Groq API har message pe yahi "role" values maangti hai
enum AgentMessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
