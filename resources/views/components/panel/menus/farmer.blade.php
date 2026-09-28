<x-panel.nav-link route-name="farmer.dashboard" icon="tabler:layout-dashboard">Dashboard</x-panel.nav-link>
<x-panel.nav-link route-name="farmer.orders.index" icon="tabler:clipboard-list">Pre-orders</x-panel.nav-link>
<x-panel.nav-link route-name="farmer.products.index" icon="tabler:carrot">My products</x-panel.nav-link>
<x-panel.nav-link route-name="farmer.pickup-windows.index" icon="tabler:clock-hour-4">Pickup slots</x-panel.nav-link>
<x-panel.nav-link route-name="farmer.reviews.index" icon="tabler:message-star">Reviews</x-panel.nav-link>
<x-panel.nav-link route-name="farmer.messages.index" icon="tabler:messages" :badge="auth()->user()->unreadMessageCount()">Messages</x-panel.nav-link>
<x-panel.nav-link route-name="community.index" icon="tabler:users-group">Community</x-panel.nav-link>
<x-panel.nav-link route-name="farmer.insights.index" icon="tabler:chart-bar">Sales insights</x-panel.nav-link>
<x-panel.nav-link route-name="farmer.stall.edit" icon="tabler:building-store">Stall profile</x-panel.nav-link>
