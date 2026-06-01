<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberService
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
        private readonly ReferralCommissionService $referralCommissions,
        private readonly SettingsService $settings,
    ) {
    }

    public function create(array $attributes, User $actor): Member
    {
        return DB::transaction(function () use ($attributes, $actor): Member {
            $this->assertNoDuplicateMember(
                cooperativeId: $attributes['cooperative_id'],
                memberNo: $attributes['member_no'] ?? null,
                identityNo: $attributes['identity_no'] ?? null,
                email: $attributes['email'] ?? null,
            );

            $member = Member::query()->create([
                ...$this->buildPayload($attributes, $actor),
                'member_no' => $attributes['member_no'] ?? $this->generateMemberNumber($attributes['cooperative_id']),
            ]);

            $member = $this->syncPortalAccount($member, $attributes, $actor);

            $this->auditLogs->record('member_created', $member, [], $this->auditSnapshot($member));

            if ($member->user_id) {
                $this->auditLogs->record('member_linked_to_user', $member, [], [
                    'user_id' => $member->user_id,
                ]);
            }

            return $member->refresh();
        });
    }

    public function update(Member $member, array $attributes, User $actor): Member
    {
        return DB::transaction(function () use ($member, $attributes, $actor): Member {
            $member = $this->lockMember($member);

            $this->assertNoDuplicateMember(
                cooperativeId: $member->cooperative_id,
                memberNo: $attributes['member_no'] ?? null,
                identityNo: $attributes['identity_no'] ?? null,
                email: $attributes['email'] ?? null,
                ignoreMemberId: $member->id,
            );

            $oldValues = $this->auditSnapshot($member);
            $oldUserId = $member->user_id;

            $member->update($this->buildPayload($attributes, $actor, $member));
            $member = $this->syncPortalAccount($member->refresh(), $attributes, $actor);

            $this->auditLogs->record('member_updated', $member, $oldValues, $this->auditSnapshot($member));

            if (($member->user_id !== $oldUserId) && $member->user_id) {
                $this->auditLogs->record('member_linked_to_user', $member, [
                    'user_id' => $oldUserId,
                ], [
                    'user_id' => $member->user_id,
                ]);
            }

            return $member->refresh();
        });
    }

    public function changeStatus(Member $member, string $status): Member
    {
        return DB::transaction(function () use ($member, $status): Member {
            $member = $this->lockMember($member);
            $oldValues = $this->auditSnapshot($member);

            $member->update([
                'membership_status' => $status,
            ]);

            $this->auditLogs->record('member_status_changed', $member, $oldValues, $this->auditSnapshot($member));

            return $member->refresh();
        });
    }

    public function createOrLinkFromApplication(MembershipApplication $application, User $reviewer): Member
    {
        return DB::transaction(function () use ($application, $reviewer): Member {
            if ($application->approved_member_id) {
                return Member::query()->findOrFail($application->approved_member_id);
            }

            $existingMember = $this->findDuplicateMember(
                cooperativeId: $application->cooperative_id,
                memberNo: null,
                identityNo: $application->identity_no,
                email: $application->email,
            );

            if ($existingMember) {
                $oldValues = $this->auditSnapshot($existingMember);

                $updates = [
                    'full_name' => $existingMember->full_name ?: $application->full_name,
                    'phone' => $existingMember->phone ?: $application->phone,
                    'address_line_1' => $existingMember->address_line_1 ?: $application->address_line_1,
                    'city' => $existingMember->city ?: $application->city,
                    'state' => $existingMember->state ?: $application->state,
                    'postcode' => $existingMember->postcode ?: $application->postcode,
                    'date_of_birth' => $existingMember->date_of_birth ?: $application->date_of_birth,
                    'gender' => $existingMember->gender ?: $application->gender,
                    'position' => $existingMember->position ?: $application->occupation,
                    'employer' => $existingMember->employer ?: $application->employer_name,
                    'joined_at' => $existingMember->joined_at ?: now(),
                    'approved_at' => $existingMember->approved_at ?: now(),
                    'approved_by' => $existingMember->approved_by ?: $reviewer->id,
                    'notes' => $existingMember->notes ?: ($application->metadata['notes'] ?? null),
                    'digital_signature' => $existingMember->digital_signature ?: ($application->metadata['digital_signature'] ?? null),
                ];

                $existingMember->update($updates);

                if (! $existingMember->referral_code) {
                    $this->referralCommissions->generateReferralCode($existingMember);
                }

                if ($existingMember->wasChanged()) {
                    $this->auditLogs->record('member_updated', $existingMember, $oldValues, $this->auditSnapshot($existingMember));
                }

                return $existingMember->refresh();
            }

            $member = Member::query()->create([
                'cooperative_id' => $application->cooperative_id,
                'user_id' => null,
                'member_no' => $this->generateMemberNumber($application->cooperative_id),
                'full_name' => $application->full_name,
                'identity_no' => $application->identity_no,
                'email' => $this->normalizeEmail($application->email),
                'phone' => $application->phone,
                'address_line_1' => $application->address_line_1,
                'address_line_2' => $application->address_line_2,
                'city' => $application->city,
                'state' => $application->state,
                'postcode' => $application->postcode,
                'country' => $application->country ?: 'Malaysia',
                'date_of_birth' => $application->date_of_birth,
                'gender' => $application->gender,
                'position' => $application->occupation,
                'employer' => $application->employer_name,
                'membership_status' => MemberStatus::Active->value,
                'joined_at' => now(),
                'approved_at' => now(),
                'approved_by' => $reviewer->id,
                'notes' => $application->metadata['notes'] ?? null,
                'digital_signature' => $application->metadata['digital_signature'] ?? null,
            ]);

            $this->referralCommissions->generateReferralCode($member);

            $this->auditLogs->record('member_created', $member, [], $this->auditSnapshot($member));

            return $member->refresh();
        });
    }

    public function generateMemberNumber(int $cooperativeId): string
    {
        $cooperative = Cooperative::query()->findOrFail($cooperativeId);

        $cooperative->increment('member_no_counter');
        $num = $cooperative->fresh()->member_no_counter;

        $memberSettings = $this->settings->group('membership', $cooperativeId);
        $prefix = $memberSettings['member_no_prefix'] ?? '';
        $digits = (int) ($memberSettings['member_no_digits'] ?? 4);

        $number = $prefix . str_pad((string) $num, max($digits, 1), '0', STR_PAD_LEFT);

        return $number;
    }

    public function findDuplicateMember(int $cooperativeId, ?string $memberNo, ?string $identityNo, ?string $email, ?int $ignoreMemberId = null): ?Member
    {
        $memberNo = $this->normalizeText($memberNo);
        $identityNo = $this->normalizeText($identityNo);
        $email = $this->normalizeEmail($email);

        if (! $memberNo && ! $identityNo && ! $email) {
            return null;
        }

        return Member::query()
            ->where('cooperative_id', $cooperativeId)
            ->when($ignoreMemberId, fn (Builder $query) => $query->whereKeyNot($ignoreMemberId))
            ->where(function (Builder $query) use ($memberNo, $identityNo, $email): void {
                if ($memberNo) {
                    $query->orWhere('member_no', $memberNo);
                }

                if ($identityNo) {
                    $query->orWhere('identity_no', $identityNo);
                }

                if ($email) {
                    $query->orWhereRaw('lower(email) = ?', [$email]);
                }
            })
            ->first();
    }

    public function assertNoDuplicateMember(int $cooperativeId, ?string $memberNo, ?string $identityNo, ?string $email, ?int $ignoreMemberId = null): void
    {
        $existing = $this->findDuplicateMember($cooperativeId, $memberNo, $identityNo, $email, $ignoreMemberId);

        if (! $existing) {
            return;
        }

        throw ValidationException::withMessages([
            'identity_no' => 'Rekod ahli dengan nombor pengenalan atau e-mel ini sudah wujud.',
            'email' => 'Rekod ahli dengan nombor pengenalan atau e-mel ini sudah wujud.',
        ]);
    }

    private function buildPayload(array $attributes, User $actor, ?Member $member = null): array
    {
        $status = $attributes['membership_status']
            ?? $member?->membership_status?->value
            ?? MemberStatus::Active->value;

        return [
            'cooperative_id' => $attributes['cooperative_id'] ?? $member?->cooperative_id,
            'user_id' => $attributes['user_id'] ?: null,
            'member_no' => $this->normalizeText($attributes['member_no'] ?? null) ?: $member?->member_no,
            'full_name' => trim((string) $attributes['full_name']),
            'identity_no' => $this->normalizeText($attributes['identity_no'] ?? null),
            'email' => $this->normalizeEmail($attributes['email'] ?? null),
            'phone' => $this->normalizeText($attributes['phone'] ?? null),
            'address_line_1' => $this->normalizeText($attributes['address_line_1'] ?? $attributes['address'] ?? null),
            'address_line_2' => $this->normalizeText($attributes['address_line_2'] ?? null),
            'city' => $this->normalizeText($attributes['city'] ?? null),
            'state' => $this->normalizeText($attributes['state'] ?? null),
            'postcode' => $this->normalizeText($attributes['postcode'] ?? null),
            'country' => 'Malaysia',
            'date_of_birth' => $attributes['date_of_birth'] ?: null,
            'gender' => $attributes['gender'] ?: null,
            'position' => $this->normalizeText($attributes['position'] ?? null),
            'department' => $this->normalizeText($attributes['department'] ?? null),
            'employer' => $this->normalizeText($attributes['employer'] ?? null),
            'employment_no' => $this->normalizeText($attributes['employment_no'] ?? null),
            'salary' => $attributes['salary'] ?? null,
            'bank' => $this->normalizeText($attributes['bank'] ?? null),
            'bank_account' => $this->normalizeText($attributes['bank_account'] ?? null),
'next_of_kin_name' => $this->normalizeText($attributes['next_of_kin_name'] ?? null),
'next_of_kin_relation' => $this->normalizeText($attributes['next_of_kin_relation'] ?? null),
'next_of_kin_phone' => $this->normalizeText($attributes['next_of_kin_phone'] ?? null),
            'next_of_kin_address' => $this->normalizeText($attributes['next_of_kin_address'] ?? null),
            'spouse_name' => $this->normalizeText($attributes['spouse_name'] ?? null),
            'spouse_phone' => $this->normalizeText($attributes['spouse_phone'] ?? null),
            'spouse_address' => $this->normalizeText($attributes['spouse_address'] ?? null),
            'membership_status' => $status,
            'joined_at' => ($attributes['joined_at'] ?? null) ?: ($member?->joined_at ?? now()),
            'approved_at' => ($attributes['approved_at'] ?? null) ?: ($member?->approved_at ?? now()),
            'approved_by' => $attributes['approved_by'] ?? $member?->approved_by ?? $actor->id,
            'notes' => $this->normalizeText($attributes['notes'] ?? null),
            'monthly_fee' => $attributes['monthly_fee'] ?? null,
            'total_fee' => $attributes['total_fee'] ?? null,
            'special_savings' => $attributes['special_savings'] ?? null,
            'monthly_deduction' => $attributes['monthly_deduction'] ?? null,
            'total_debt' => $attributes['total_debt'] ?? null,
        ];
    }

    private function syncPortalAccount(Member $member, array $attributes, User $actor): Member
    {
        $password = $attributes['password'] ?? null;
        $requestedRole = $this->resolveManagedAccountRole($attributes, $actor);
        $shouldSyncRole = filled($attributes['account_role'] ?? null);

        if (! filled($password) && ! $shouldSyncRole) {
            return $member->refresh();
        }

        if ($member->user_id) {
            $user = User::query()->find($member->user_id);

            if (! $user) {
                throw ValidationException::withMessages([
                    'user_id' => 'Akaun pengguna yang dipautkan tidak ditemui.',
                ]);
            }

            if (! $this->isManagedPortalUser($user)) {
                throw ValidationException::withMessages([
                    'user_id' => 'Akaun pengguna yang dipautkan tidak boleh diurus melalui modul ahli.',
                ]);
            }

            if (! $actor->hasRole(AccessControl::ROLE_SUPER_ADMIN) && $user->role === AccessControl::ROLE_ADMIN) {
                throw ValidationException::withMessages([
                    'account_role' => 'Hanya super admin boleh mengurus akaun admin melalui modul ahli.',
                ]);
            }

            $originalRole = $user->role;

            $updates = [
                'status' => $user->status ?: 'active',
            ];

            if (filled($password)) {
                $updates['password'] = $password;
            }

            if ($requestedRole !== $originalRole) {
                $updates['role'] = $requestedRole;
                $updates['user_type'] = $requestedRole;
            }

            if ($updates !== ['status' => $user->status ?: 'active']) {
                $user->update($updates);
            }

            if ($requestedRole !== $originalRole) {
                $user->syncRoles([$requestedRole]);
            }

            if (! $member->portal_activated_at) {
                $member->update([
                    'portal_activated_at' => now(),
                ]);
            }

            $this->auditLogs->record('member_portal_password_updated', $member, [], [
                'user_id' => $user->id,
            ]);

            return $member->refresh();
        }

        $email = $this->normalizeEmail($member->email);

        if (! $email) {
            throw ValidationException::withMessages([
                'email' => 'E-mel diperlukan untuk cipta akses portal ahli dengan kata laluan manual.',
            ]);
        }

        $existingUser = User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => 'Alamat e-mel ini sudah digunakan oleh pengguna lain.',
            ]);
        }

        $user = User::query()->create([
            'cooperative_id' => $member->cooperative_id,
            'name' => $member->full_name,
            'email' => $email,
            'password' => $password,
            'role' => $requestedRole,
            'user_type' => $requestedRole,
            'status' => 'active',
            'phone' => $member->phone,
        ]);

        $user->assignRole($requestedRole);

        $member->update([
            'user_id' => $user->id,
            'portal_activated_at' => $member->portal_activated_at ?? now(),
        ]);

        $this->auditLogs->record('member_portal_account_created', $member, [], [
            'user_id' => $user->id,
        ]);

        return $member->refresh();
    }

    private function lockMember(Member $member): Member
    {
        return Member::query()->whereKey($member->getKey())->lockForUpdate()->firstOrFail();
    }

    private function auditSnapshot(Member $member): array
    {
        return [
            'user_id' => $member->user_id,
            'member_no' => $member->member_no,
            'full_name' => $member->full_name,
            'identity_no' => $member->identity_no,
            'email' => $member->email,
            'phone' => $member->phone,
            'membership_status' => $member->membership_status?->value,
            'joined_at' => $member->joined_at?->toAtomString(),
            'approved_at' => $member->approved_at?->toAtomString(),
            'approved_by' => $member->approved_by,
        ];
    }

    private function normalizeText(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    private function normalizeEmail(?string $value): ?string
    {
        $value = $this->normalizeText($value);

        return $value ? Str::lower($value) : null;
    }

    private function isManagedPortalUser(User $user): bool
    {
        return in_array($user->user_type, [AccessControl::ROLE_MEMBER, AccessControl::ROLE_ADMIN], true)
            || in_array($user->role, [AccessControl::ROLE_MEMBER, AccessControl::ROLE_ADMIN], true)
            || $user->hasRole(AccessControl::ROLE_MEMBER)
            || $user->hasRole(AccessControl::ROLE_ADMIN);
    }

    private function resolveManagedAccountRole(array $attributes, User $actor): string
    {
        $requestedRole = $attributes['account_role'] ?? null;

        if ($actor->hasRole(AccessControl::ROLE_SUPER_ADMIN) && in_array($requestedRole, [AccessControl::ROLE_ADMIN, AccessControl::ROLE_MEMBER], true)) {
            return $requestedRole;
        }

        return AccessControl::ROLE_MEMBER;
    }

    public function updateFinancials(Member $member, array $attributes, User $actor): Member
    {
        return DB::transaction(function () use ($member, $attributes, $actor): Member {
            $oldValues = [
                'monthly_fee' => $member->monthly_fee,
                'total_fee' => $member->total_fee,
                'special_savings' => $member->special_savings,
                'monthly_deduction' => $member->monthly_deduction,
                'total_debt' => $member->total_debt,
            ];

            $updates = [];
            foreach (['monthly_fee', 'total_fee', 'special_savings', 'monthly_deduction', 'total_debt'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $updates[$field] = $attributes[$field] !== null && $attributes[$field] !== '' ? (float) $attributes[$field] : null;
                }
            }

            if ($updates === []) {
                return $member;
            }

            $member->update($updates);
            $member->refresh();

            $newValues = [
                'monthly_fee' => $member->monthly_fee,
                'total_fee' => $member->total_fee,
                'special_savings' => $member->special_savings,
                'monthly_deduction' => $member->monthly_deduction,
                'total_debt' => $member->total_debt,
            ];

            $changed = [];
            foreach ($oldValues as $key => $old) {
                if ($old != $newValues[$key]) {
                    $changed[$key] = ['from' => $old, 'to' => $newValues[$key]];
                }
            }

            if ($changed !== []) {
                $this->auditLogs->record('member_financials_updated', $member, $oldValues, $newValues, [
                    'changed_fields' => $changed,
                    'updated_by' => $actor->id,
                    'updated_by_name' => $actor->name,
                    'updated_by_email' => $actor->email,
                ]);
            }

            return $member;
        });
    }
}
