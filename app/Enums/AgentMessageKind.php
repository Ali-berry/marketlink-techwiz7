<?php

namespace App\Enums;

// normal = user ne poocha, proactive = agent ne khud bheja (inbox feature)
enum AgentMessageKind: string
{
    case Normal = 'normal';
    case Proactive = 'proactive';
}
