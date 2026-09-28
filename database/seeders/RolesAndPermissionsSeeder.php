<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// admin ke do level: Support Admin roz ka kaam (farmers, customers, moderation), Super Admin ke paas
// markets, categories, announcements, reports bhi. Dono UserRole::Admin hain, ye Spatie wali upar ki layer hai
class RolesAndPermissionsSeeder extends Seeder
{
    private const SUPPORT_ADMIN_PERMISSIONS = [
        'manage-farmers',
        'manage-customers',
        'moderate-content',
    ];

    // Super Admin = Support Admin ki sab permissions + baqi
    private const SUPER_ADMIN_ONLY_PERMISSIONS = [
        'manage-markets',
        'manage-categories',
        'manage-announcements',
        'view-reports',
        'moderate-community-posts',
    ];

    // bilkul alag chhota role - sirf community posts review karne ki permission
    private const COMMUNITY_MODERATOR_PERMISSIONS = [
        'moderate-community-posts',
    ];

    public function run(): void
    {
        // Spatie permissions cache karta hai - clear na karo to isi run mein bani permissions miss ho sakti hain
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = array_unique([
            ...self::SUPPORT_ADMIN_PERMISSIONS,
            ...self::SUPER_ADMIN_ONLY_PERMISSIONS,
            ...self::COMMUNITY_MODERATOR_PERMISSIONS,
        ]);

        foreach ($allPermissions as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        Role::findOrCreate('super-admin')->syncPermissions($allPermissions);
        Role::findOrCreate('support-admin')->syncPermissions(self::SUPPORT_ADMIN_PERMISSIONS);
        Role::findOrCreate('community-moderator')->syncPermissions(self::COMMUNITY_MODERATOR_PERMISSIONS);
    }
}
