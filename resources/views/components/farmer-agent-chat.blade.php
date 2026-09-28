@include('components.agent-chat._widget', [
    'chatRoute' => route('farmer.agent.chat'),
    'historyRoute' => route('farmer.agent.history'),
    'unreadCountRoute' => route('farmer.agent.unread-count'),
    'markReadRoute' => route('farmer.agent.mark-read'),
    'label' => 'Ask MarketLink',
])
