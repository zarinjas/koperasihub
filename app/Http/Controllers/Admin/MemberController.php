<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Http\Requests\Admin\UpdateMemberStatusRequest;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Services\Files\MemberPhotoStorageService;
use App\Services\MemberService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MemberService $members,
        private readonly MemberPhotoStorageService $memberPhotos,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        $members = Member::query()
            ->forCooperative($this->activeCooperative()?->id)
            ->with('user')
            ->search($search)
            ->when(in_array($status, MemberStatus::values(), true), fn ($query) => $query->where('membership_status', $status))
            ->latest('joined_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Member $member) => $this->serializeSummary($member));

        return Inertia::render('Admin/Pages/Members/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'members' => $members,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_MEMBERS) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_MEMBERS) ?? false,
            'canSuspend' => $request->user()?->can(AccessControl::PERMISSION_SUSPEND_MEMBERS) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Members/Form', [
            'mode' => 'create',
            'member' => null,
            'statusOptions' => $this->statusOptions(),
            'genderOptions' => $this->genderOptions(),
            'userOptions' => $this->userOptions(),
        ]);
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $member = $this->members->create([
            ...$request->validated(),
            'cooperative_id' => $request->user()->cooperative_id,
        ], $request->user());

        return redirect()
            ->route('admin.members.show', $member)
            ->with('status', 'Rekod ahli berjaya dicipta.');
    }

    public function show(Request $request, Member $member): Response
    {
        $this->ensureSameCooperative($member);

        return Inertia::render('Admin/Pages/Members/Show', [
            'member' => $this->serializeDetail($member),
            'statusOptions' => $this->statusOptions(),
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_MEMBERS) ?? false,
            'canSuspend' => $request->user()?->can(AccessControl::PERMISSION_SUSPEND_MEMBERS) ?? false,
        ]);
    }

    public function edit(Member $member): Response
    {
        $this->ensureSameCooperative($member);

        return Inertia::render('Admin/Pages/Members/Form', [
            'mode' => 'edit',
            'member' => $this->serializeFormMember($member),
            'statusOptions' => $this->statusOptions(),
            'genderOptions' => $this->genderOptions(),
            'userOptions' => $this->userOptions($member),
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $this->ensureSameCooperative($member);

        $this->members->update($member, $request->validated(), $request->user());

        return redirect()
            ->route('admin.members.show', $member)
            ->with('status', 'Profil ahli berjaya dikemas kini.');
    }

    public function updateStatus(UpdateMemberStatusRequest $request, Member $member): RedirectResponse
    {
        $this->ensureSameCooperative($member);

        $this->members->changeStatus($member, $request->validated('membership_status'));

        return back()->with('status', 'Status ahli berjaya dikemas kini.');
    }

    private function serializeSummary(Member $member): array
    {
        return [
            'id' => $member->id,
            'member_no' => $member->member_no,
            'profile_photo_url' => $this->memberPhotos->url($member->profile_photo_path),
            'full_name' => $member->full_name,
            'identity_no' => $member->identity_no,
            'email' => $member->email,
            'phone' => $member->phone,
            'membership_status' => $member->membership_status->value,
            'joined_at' => $member->joined_at?->format('d/m/Y'),
            'user_name' => $member->user?->name,
            'show_url' => route('admin.members.show', $member),
            'edit_url' => route('admin.members.edit', $member),
        ];
    }

    private function serializeDetail(Member $member): array
    {
        $member->loadMissing(['user', 'approver']);

        $application = MembershipApplication::query()
            ->where('cooperative_id', $member->cooperative_id)
            ->where('approved_member_id', $member->id)
            ->latest('submitted_at')
            ->first();

        $supportingDocument = $application?->metadata['supporting_document'] ?? null;

        $documents = Document::query()
            ->where('cooperative_id', $member->cooperative_id)
            ->where('member_id', $member->id)
            ->latest('updated_at')
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'status' => $document->status->value,
                'visibility' => $document->visibility->value,
                'updated_at' => $document->updated_at?->format('d/m/Y H:i'),
                'edit_url' => route('admin.documents.edit', $document),
                'download_url' => route('admin.documents.download', $document),
            ])
            ->all();

        return [
            'id' => $member->id,
            'member_no' => $member->member_no,
            'profile_photo_url' => $this->memberPhotos->url($member->profile_photo_path),
            'user_id' => $member->user_id,
            'user_name' => $member->user?->name,
            'user_email' => $member->user?->email,
            'full_name' => $member->full_name,
            'identity_no' => $member->identity_no,
            'email' => $member->email,
            'phone' => $member->phone,
            'address' => $member->address_line_1,
            'date_of_birth' => $member->date_of_birth?->format('d/m/Y'),
            'gender' => $this->genderLabel($member->gender),
            'occupation' => $member->occupation,
            'employer_name' => $member->employer_name,
            'membership_status' => $member->membership_status->value,
            'joined_at' => $member->joined_at?->format('d/m/Y H:i'),
            'approved_at' => $member->approved_at?->format('d/m/Y H:i'),
            'approved_by_name' => $member->approver?->name,
            'notes' => $member->notes,
            'edit_url' => route('admin.members.edit', $member),
            'application' => $application ? [
                'id' => $application->id,
                'application_no' => $application->application_no,
                'status' => $application->status->value,
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'show_url' => route('admin.membership-applications.show', $application),
                'supporting_document' => $supportingDocument ? [
                    'name' => $supportingDocument['name'] ?? 'Dokumen sokongan',
                    'download_url' => route('admin.membership-applications.download-supporting-document', $application),
                ] : null,
            ] : null,
            'documents' => $documents,
        ];
    }

    private function serializeFormMember(Member $member): array
    {
        $member->loadMissing('user');

        return [
            'id' => $member->id,
            'user_id' => $member->user_id,
            'member_no' => $member->member_no,
            'full_name' => $member->full_name,
            'identity_no' => $member->identity_no,
            'email' => $member->email,
            'phone' => $member->phone,
            'address' => $member->address_line_1,
            'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
            'gender' => $member->gender,
            'occupation' => $member->occupation,
            'employer_name' => $member->employer_name,
            'membership_status' => $member->membership_status->value,
            'joined_at' => $member->joined_at?->format('Y-m-d'),
            'notes' => $member->notes,
        ];
    }

    private function userOptions(?Member $member = null): array
    {
        return User::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->where(function ($query) use ($member): void {
                $query->whereDoesntHave('member');

                if ($member?->user_id) {
                    $query->orWhereKey($member->user_id);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'value' => $user->id,
                'label' => "{$user->name} ({$user->email})",
            ])
            ->prepend(['value' => '', 'label' => 'Tidak dipautkan'])
            ->values()
            ->all();
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => MemberStatus::Active->value, 'label' => 'Aktif'],
            ['value' => MemberStatus::Inactive->value, 'label' => 'Tidak aktif'],
            ['value' => MemberStatus::Suspended->value, 'label' => 'Digantung'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }

    private function genderOptions(): array
    {
        return [
            ['value' => '', 'label' => 'Pilih jantina'],
            ['value' => 'male', 'label' => 'Lelaki'],
            ['value' => 'female', 'label' => 'Perempuan'],
            ['value' => 'other', 'label' => 'Lain-lain'],
        ];
    }

    private function genderLabel(?string $gender): ?string
    {
        return match ($gender) {
            'male' => 'Lelaki',
            'female' => 'Perempuan',
            'other' => 'Lain-lain',
            default => $gender,
        };
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Member $member): void
    {
        abort_unless(
            $member->cooperative_id && $member->cooperative_id === $this->activeCooperative()?->id,
            404
        );
    }
}
