<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Services\Files\MemberPhotoStorageService;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Str;

class MemberCardService
{
    public function __construct(
        private readonly MemberPhotoStorageService $memberPhotos,
        private readonly SettingsService $settings,
    ) {
    }

    public function ensureToken(Member $member): Member
    {
        if ($member->card_public_token) {
            return $member;
        }

        do {
            $token = Str::random(48);
        } while (Member::query()->withTrashed()->where('card_public_token', $token)->exists());

        $member->forceFill([
            'card_public_token' => $token,
            'card_token_generated_at' => now(),
        ])->save();

        return $member->refresh();
    }

    public function verificationUrl(Member $member): string
    {
        $member = $this->ensureToken($member);

        return route('public.member-card.verify', $member->card_public_token);
    }

    public function readiness(Member $member): array
    {
        $member = $this->ensureToken($member);

        $hasPhoto = filled($member->profile_photo_path);
        $isActive = $member->membership_status === MemberStatus::Active;
        $isLimited = ! $isActive;
        $isReady = $hasPhoto;

        return [
            'has_profile_photo' => $hasPhoto,
            'has_token' => filled($member->card_public_token),
            'is_active' => $isActive,
            'is_limited' => $isLimited,
            'is_ready' => $isReady,
            'notice' => $hasPhoto
                ? null
                : 'Muat naik gambar profil untuk mengaktifkan Kad Keahlian Digital anda.',
        ];
    }

    public function memberPayload(Member $member): array
    {
        $member = $this->ensureToken($member);
        $readiness = $this->readiness($member);

        return [
            'id' => $member->id,
            'member_no' => $member->member_no,
            'full_name' => $member->full_name,
            'profile_photo_url' => $this->memberPhotos->url($member->profile_photo_path),
            'membership_status' => $member->membership_status->value,
            'membership_status_label' => $this->statusLabel($member->membership_status),
            'membership_type_label' => 'Ahli',
            'joined_at' => $member->joined_at?->format('d/m/Y'),
            'verification_url' => $this->verificationUrl($member),
            'card_public_token' => $member->card_public_token,
            'card_token_generated_at' => $member->card_token_generated_at?->format('d/m/Y H:i'),
            'readiness' => $readiness,
        ];
    }

    public function publicPayload(Member $member): array
    {
        $member = $this->ensureToken($member);
        $shared = $this->settings->shared();
        $cooperative = $shared['cooperative'] ?? [];

        return [
            'cooperative' => [
                'name' => $cooperative['short_name'] ?: ($cooperative['name'] ?? config('app.name')),
                'full_name' => $cooperative['name'] ?? config('app.name'),
                'logo_url' => $cooperative['logo_url'] ?? null,
            ],
            'member' => [
                'full_name' => $member->full_name,
                'member_no' => $member->member_no,
                'profile_photo_url' => $this->memberPhotos->url($member->profile_photo_path),
                'membership_status' => $member->membership_status->value,
                'membership_status_label' => $this->statusLabel($member->membership_status),
                'membership_type_label' => 'Ahli',
                'joined_at' => $member->joined_at?->format('d/m/Y'),
                'is_active' => $member->membership_status === MemberStatus::Active,
                'is_inactive' => $member->membership_status !== MemberStatus::Active,
            ],
        ];
    }

    private function statusLabel(MemberStatus $status): string
    {
        return match ($status) {
            MemberStatus::Active => 'Aktif',
            MemberStatus::Inactive => 'Tidak aktif',
            MemberStatus::Suspended => 'Digantung',
        };
    }
}
