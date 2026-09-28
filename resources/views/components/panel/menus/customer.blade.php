<x-panel.nav-link route-name="customer.dashboard" icon="tabler:layout-dashboard">Dashboard</x-panel.nav-link>
<x-panel.nav-link route-name="customer.markets.index" icon="tabler:map-pin">Markets near me</x-panel.nav-link>
<x-panel.nav-link route-name="customer.farmers.index" icon="tabler:tractor">Farmers</x-panel.nav-link>
<x-panel.nav-link route-name="customer.products.index" icon="tabler:basket">Browse products</x-panel.nav-link>
<x-panel.nav-link route-name="customer.cart.index" icon="tabler:shopping-bag">My basket</x-panel.nav-link>
<x-panel.nav-link route-name="customer.orders.index" icon="tabler:receipt">My orders</x-panel.nav-link>
<x-panel.nav-link route-name="customer.favourites.index" icon="tabler:heart">Favourites</x-panel.nav-link>
<x-panel.nav-link route-name="customer.reviews.index" icon="tabler:star">My reviews</x-panel.nav-link>
<x-panel.nav-link route-name="customer.messages.index" icon="tabler:messages" :badge="auth()->user()->unreadMessageCount()">Messages</x-panel.nav-link>
<x-panel.nav-link route-name="community.index" icon="tabler:users-group">Community</x-panel.nav-link>
