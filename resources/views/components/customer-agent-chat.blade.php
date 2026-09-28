@include('components.agent-chat._widget', [
    'chatRoute' => route('customer.agent.chat'),
    'historyRoute' => route('customer.agent.history'),
    'unreadCountRoute' => route('customer.agent.unread-count'),
    'markReadRoute' => route('customer.agent.mark-read'),
    'label' => 'Ask MarketLink',
])
