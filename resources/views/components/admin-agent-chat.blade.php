@include('components.agent-chat._widget', [
    'chatRoute' => route('admin.agent.chat'),
    'historyRoute' => route('admin.agent.history'),
    'unreadCountRoute' => route('admin.agent.unread-count'),
    'markReadRoute' => route('admin.agent.mark-read'),
    'label' => 'Ask MarketLink',
])
