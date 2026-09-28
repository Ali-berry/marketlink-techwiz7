<?php

namespace App\Services;

use App\Enums\FarmerApprovalStatus;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;
use App\Notifications\FarmerApprovalStatusChanged;
use App\Services\Agent\AgentProactiveMessenger;

// Farmer ko approve / suspend karna - admin pages aur admin AI dono yahi use karte hain
class FarmerApprovalService
{
    public function __construct(private readonly AgentProactiveMessenger $proactiveMessenger)
    {
    }

    // naya farmer register hua, pending hai - manage-farmers wale sab admins ko batao
    public function notifyAdminsOfNewSignup(FarmerProfile $farmer): void
    {
        $city = $farmer->homeMarket()?->city ?? $farmer->address;

        foreach (User::adminsWithPermission('manage-farmers') as $admin) {
            $this->proactiveMessenger->send(
                recipient: $admin,
                userType: UserRole::Admin,
                content: "New farmer {$farmer->stall_name} signed up ({$city}). Review and approve?",
                contextType: 'farmer',
                contextId: $farmer->id,
                contextLabel: $farmer->stall_name,
                actions: ['Approve', 'Decline', 'Details'],
            );
        }
    }

    // suspended stall ko dobara approve bhi yahi karta hai
    public function approve(FarmerProfile $farmer): void
    {
        $farmer->update([
            'approval_status' => FarmerApprovalStatus::Approved,
            'approved_at' => now(),
            'suspension_reason' => null,
        ]);

        $farmer->user->notify(new FarmerApprovalStatusChanged($farmer));
    }

    // reason farmer ko dikhta hai, caller check kare ke khali na ho
    public function suspend(FarmerProfile $farmer, string $reason): void
    {
        $farmer->update([
            'approval_status' => FarmerApprovalStatus::Suspended,
            'suspension_reason' => $reason,
        ]);

        $farmer->user->notify(new FarmerApprovalStatusChanged($farmer));
    }
}
