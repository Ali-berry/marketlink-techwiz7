{{-- dashboard koi bhi general admin permission se khulta hai (User::GENERAL_ADMIN_PERMISSIONS).
     Community Moderator ke paas koi nahi, us ka sidebar Community Moderation se shuru --}}
@canany(\App\Models\User::GENERAL_ADMIN_PERMISSIONS)
    <x-panel.nav-link route-name="admin.dashboard" icon="tabler:layout-dashboard">Dashboard</x-panel.nav-link>
@endcanany

@can('manage-farmers')
    <x-panel.nav-link route-name="admin.farmers.index" icon="tabler:tractor">Farmers</x-panel.nav-link>
@endcan
@can('manage-customers')
    <x-panel.nav-link route-name="admin.customers.index" icon="tabler:users">Customers</x-panel.nav-link>
@endcan

{{-- Support Admin ko sirf ye teen - Markets / Categories / Announcements / Reports sirf Super Admin --}}
@can('manage-markets')
    <x-panel.nav-link route-name="admin.markets.index" icon="tabler:map-2">Markets</x-panel.nav-link>
@endcan
@can('manage-categories')
    <x-panel.nav-link route-name="admin.categories.index" icon="tabler:category">Categories</x-panel.nav-link>
@endcan

@can('moderate-content')
    <x-panel.nav-link route-name="admin.moderation.index" icon="tabler:shield-check">Moderation</x-panel.nav-link>
@endcan

@can('view-reports')
    <x-panel.nav-link route-name="admin.reports.index" icon="tabler:report-analytics">Reports</x-panel.nav-link>
@endcan
@can('manage-announcements')
    <x-panel.nav-link route-name="admin.announcements.index" icon="tabler:speakerphone">Announcements</x-panel.nav-link>
@endcan

{{-- Community Moderator ki ek hi permission - us role ka poora sidebar yahi hai --}}
@can('moderate-community-posts')
    <x-panel.nav-link route-name="admin.community.index" icon="tabler:message-check">Community Moderation</x-panel.nav-link>
@endcan
