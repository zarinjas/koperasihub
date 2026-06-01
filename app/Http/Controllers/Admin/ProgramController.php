<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceMethod;
use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use App\Enums\RsvpResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\Program;
use App\Models\ProgramRsvp;
use App\Services\AuditLogService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();
        $programType = $request->string('program_type')->toString();

        $programs = Program::query()
            ->forCooperative($this->activeCooperative()?->id)
            ->withCount([
                'rsvps',
                'rsvps as rsvps_hadir_count' => fn ($q) => $q->where('response', RsvpResponse::Hadir->value),
                'rsvps as rsvps_checked_in_count' => fn ($q) => $q->whereNotNull('checked_in_at'),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ProgramStatus::values(), true), fn ($q) => $q->where('status', $status))
            ->when(in_array($category, $this->categoryOptions(), true), fn ($q) => $q->where('category', $category))
            ->when(in_array($programType, ProgramType::values(), true), fn ($q) => $q->where('program_type', $programType))
            ->orderBy('start_date', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Program $program) => $this->serializeList($program));

        return Inertia::render('Admin/Pages/Programs/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
                'program_type' => $programType,
            ],
            'programs' => $programs,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'categoryOptions' => $this->categoryOptions(includeAll: true),
            'programTypeOptions' => $this->programTypeOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_PROGRAMS) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_PROGRAMS) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_PROGRAMS) ?? false,
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_PROGRAMS) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Programs/Form', [
            'mode' => 'create',
            'program' => null,
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'programTypeOptions' => $this->programTypeOptions(),
        ]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'cooperative_id' => $this->activeCooperative()?->id,
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? Str::slug($validated['title']),
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'program_type' => $validated['program_type'],
            'location' => $validated['location'] ?? null,
            'online_url' => $validated['online_url'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'registration_deadline' => $validated['registration_deadline'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('cover_image')) {
            $data['cover_image_path'] = $request->file('cover_image')->store('programs', 'public');
        }

        $program = Program::query()->create($data);
        $this->auditLogs->record('program.created', $program, [], $this->programAuditSnapshot($program));

        return redirect()
            ->route('admin.programs.edit', $program)
            ->with('status', 'Program berjaya dicipta.');
    }

    public function edit(Program $program): Response
    {
        $this->ensureSameCooperative($program);

        return Inertia::render('Admin/Pages/Programs/Form', [
            'mode' => 'edit',
            'program' => $this->serializeForm($program),
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'programTypeOptions' => $this->programTypeOptions(),
        ]);
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $this->ensureSameCooperative($program);
        $validated = $request->validated();

        $data = [
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? Str::slug($validated['title']),
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'program_type' => $validated['program_type'],
            'location' => $validated['location'] ?? null,
            'online_url' => $validated['online_url'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'registration_deadline' => $validated['registration_deadline'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('cover_image')) {
            if ($program->cover_image_path) {
                Storage::disk('public')->delete($program->cover_image_path);
            }
            $data['cover_image_path'] = $request->file('cover_image')->store('programs', 'public');
        }

        $program->update($data);
        $this->auditLogs->record('program.updated', $program, [], $this->programAuditSnapshot($program->fresh()));

        return back()->with('status', 'Program berjaya dikemas kini.');
    }

    public function show(Request $request, Program $program): Response
    {
        $this->ensureSameCooperative($program);

        $rsvps = $program->rsvps()
            ->with(['member', 'checkedInBy'])
            ->orderBy('responded_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ProgramRsvp $rsvp) => [
                'id' => $rsvp->id,
                'member_id' => $rsvp->member_id,
                'member_no' => $rsvp->member->member_no,
                'member_name' => $rsvp->member->full_name,
                'response' => $rsvp->response,
                'responded_at' => $rsvp->responded_at?->format('d/m/Y H:i'),
                'checked_in_at' => $rsvp->checked_in_at?->format('d/m/Y H:i'),
                'attendance_method' => $rsvp->attendance_method,
                'checked_in_by_name' => $rsvp->checkedInBy?->name,
                'notes' => $rsvp->notes,
            ]);

        $stats = $this->attendanceStats($program);

        return Inertia::render('Admin/Pages/Programs/Show', [
            'program' => $this->serializeDetail($program),
            'rsvps' => $rsvps,
            'stats' => $stats,
            'canScanAttendance' => $request->user()?->can(AccessControl::PERMISSION_SCAN_ATTENDANCE) ?? false,
            'canViewReports' => $request->user()?->can(AccessControl::PERMISSION_VIEW_ATTENDANCE_REPORTS) ?? false,
        ]);
    }

    public function publish(Program $program): RedirectResponse
    {
        $this->ensureSameCooperative($program);
        $oldStatus = $program->status;
        $program->update(['status' => ProgramStatus::Published->value]);
        $this->auditLogs->record('program.published', $program, ['status' => $oldStatus], ['status' => $program->status]);

        return back()->with('status', 'Program berjaya diterbitkan.');
    }

    public function cancel(Program $program): RedirectResponse
    {
        $this->ensureSameCooperative($program);
        $oldStatus = $program->status;
        $program->update(['status' => ProgramStatus::Cancelled->value]);
        $this->auditLogs->record('program.cancelled', $program, ['status' => $oldStatus], ['status' => $program->status]);

        return back()->with('status', 'Program telah dibatalkan.');
    }

    public function complete(Program $program): RedirectResponse
    {
        $this->ensureSameCooperative($program);
        $oldStatus = $program->status;
        $program->update(['status' => ProgramStatus::Completed->value]);
        $this->auditLogs->record('program.completed', $program, ['status' => $oldStatus], ['status' => $program->status]);

        return back()->with('status', 'Program ditandakan sebagai selesai.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->ensureSameCooperative($program);
        $snapshot = $this->programAuditSnapshot($program);

        if ($program->cover_image_path) {
            Storage::disk('public')->delete($program->cover_image_path);
        }

        $program->delete();
        $this->auditLogs->record('program.deleted', $program, $snapshot, ['deleted_at' => $program->deleted_at?->toISOString()]);

        return redirect()
            ->route('admin.programs.index')
            ->with('status', 'Program berjaya dipadam.');
    }

    public function attendance(Program $program, Request $request): Response
    {
        $this->ensureSameCooperative($program);

        $search = trim((string) $request->string('search'));
        $responseFilter = $request->string('response')->toString();
        $checkedInFilter = $request->string('checked_in')->toString();

        $rsvps = $program->rsvps()
            ->with('member')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('member', function ($q) use ($search): void {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%");
                });
            })
            ->when(in_array($responseFilter, RsvpResponse::values(), true), fn ($q) => $q->where('response', $responseFilter))
            ->when($checkedInFilter === 'yes', fn ($q) => $q->whereNotNull('checked_in_at'))
            ->when($checkedInFilter === 'no', fn ($q) => $q->whereNull('checked_in_at'))
            ->orderBy('responded_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ProgramRsvp $rsvp) => [
                'id' => $rsvp->id,
                'member_id' => $rsvp->member_id,
                'member_no' => $rsvp->member->member_no,
                'member_name' => $rsvp->member->full_name,
                'response' => $rsvp->response,
                'responded_at' => $rsvp->responded_at?->format('d/m/Y H:i'),
                'checked_in_at' => $rsvp->checked_in_at?->format('d/m/Y H:i'),
                'attendance_method' => $rsvp->attendance_method,
                'notes' => $rsvp->notes,
            ]);

        $stats = $this->attendanceStats($program);

        return Inertia::render('Admin/Pages/Programs/Attendance/Index', [
            'program' => $this->serializeDetail($program),
            'rsvps' => $rsvps,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'response' => $responseFilter,
                'checked_in' => $checkedInFilter,
            ],
            'responseOptions' => $this->rsvpResponseOptions(includeAll: true),
            'memberSearchUrl' => route('admin.members.search'),
        ]);
    }

    public function scanMember(Request $request, Program $program): RedirectResponse
    {
        $this->ensureSameCooperative($program);
        $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $member = Member::query()->findOrFail($request->integer('member_id'));
        $this->ensureSameCooperativeMember($member);

        $rsvp = ProgramRsvp::query()->firstOrCreate(
            [
                'program_id' => $program->id,
                'member_id' => $member->id,
            ],
            [
                'cooperative_id' => $this->activeCooperative()?->id,
                'response' => RsvpResponse::Hadir->value,
                'responded_at' => now(),
            ],
        );

        $rsvp->update([
            'checked_in_at' => now(),
            'checked_in_by' => $request->user()?->id,
            'attendance_method' => AttendanceMethod::AdminScanMemberQr->value,
            'notes' => $request->input('notes', $rsvp->notes),
        ]);

        $this->auditLogs->record('attendance.recorded', $rsvp, [], [
            'program_id' => $program->id,
            'member_id' => $member->id,
            'method' => AttendanceMethod::AdminScanMemberQr->value,
        ]);

        return back()->with('status', "Kehadiran {$member->full_name} berjaya direkodkan.");
    }

    public function manualAttendance(StoreAttendanceRequest $request, Program $program): RedirectResponse
    {
        $this->ensureSameCooperative($program);
        $validated = $request->validated();

        $member = Member::query()->findOrFail($validated['member_id']);
        $this->ensureSameCooperativeMember($member);

        $rsvp = ProgramRsvp::query()->firstOrCreate(
            [
                'program_id' => $program->id,
                'member_id' => $member->id,
            ],
            [
                'cooperative_id' => $this->activeCooperative()?->id,
                'response' => RsvpResponse::Hadir->value,
                'responded_at' => now(),
            ],
        );

        $rsvp->update([
            'checked_in_at' => now(),
            'checked_in_by' => $request->user()?->id,
            'attendance_method' => AttendanceMethod::ManualEntry->value,
            'notes' => $validated['notes'] ?? $rsvp->notes,
        ]);

        $this->auditLogs->record('attendance.recorded', $rsvp, [], [
            'program_id' => $program->id,
            'member_id' => $member->id,
            'method' => AttendanceMethod::ManualEntry->value,
        ]);

        return back()->with('status', "Kehadiran {$member->full_name} berjaya direkodkan.");
    }

    public function eventQr(Program $program): Response
    {
        $this->ensureSameCooperative($program);

        $checkInUrl = route('member.programs.check-in', $program);

        return Inertia::render('Admin/Pages/Programs/Attendance/EventQr', [
            'program' => $this->serializeDetail($program),
            'checkInUrl' => $checkInUrl,
        ]);
    }

    private function attendanceStats(Program $program): array
    {
        $totalRsvps = $program->rsvps()->count();
        $hadir = $program->rsvps()->where('response', RsvpResponse::Hadir->value)->count();
        $tidakHadir = $program->rsvps()->where('response', RsvpResponse::TidakHadir->value)->count();
        $mungkin = $program->rsvps()->where('response', RsvpResponse::Mungkin->value)->count();
        $checkedIn = $program->rsvps()->whereNotNull('checked_in_at')->count();

        return [
            'total_rsvps' => $totalRsvps,
            'hadir' => $hadir,
            'tidak_hadir' => $tidakHadir,
            'mungkin' => $mungkin,
            'checked_in' => $checkedIn,
            'attendance_percentage' => $hadir > 0 ? round(($checkedIn / $hadir) * 100, 1) : 0,
            'capacity' => $program->capacity,
            'capacity_percentage' => $program->capacity > 0 ? round(($checkedIn / $program->capacity) * 100, 1) : 0,
        ];
    }

    private function serializeList(Program $program): array
    {
        return [
            'id' => $program->id,
            'title' => $program->title,
            'slug' => $program->slug,
            'category' => $program->category,
            'program_type' => $program->program_type,
            'location' => $program->location,
            'start_date' => $program->start_date?->format('Y-m-d\TH:i'),
            'start_date_human' => $program->start_date?->format('d/m/Y H:i'),
            'end_date_human' => $program->end_date?->format('d/m/Y H:i'),
            'status' => $program->status,
            'is_featured' => $program->is_featured,
            'rsvps_count' => $program->rsvps_count ?? $program->rsvps()->count(),
            'rsvps_hadir_count' => $program->rsvps_hadir_count ?? 0,
            'rsvps_checked_in_count' => $program->rsvps_checked_in_count ?? 0,
            'capacity' => $program->capacity,
            'cover_image_url' => $program->cover_image_path ? Storage::disk('public')->url($program->cover_image_path) : null,
        ];
    }

    private function serializeForm(Program $program): array
    {
        return [
            'id' => $program->id,
            'title' => $program->title,
            'slug' => $program->slug,
            'description' => $program->description,
            'category' => $program->category,
            'program_type' => $program->program_type,
            'location' => $program->location,
            'online_url' => $program->online_url,
            'capacity' => $program->capacity,
            'start_date' => $program->start_date?->format('Y-m-d\TH:i'),
            'end_date' => $program->end_date?->format('Y-m-d\TH:i'),
            'registration_deadline' => $program->registration_deadline?->format('Y-m-d\TH:i'),
            'cover_image_path' => $program->cover_image_path,
            'cover_image_url' => $program->cover_image_path ? Storage::disk('public')->url($program->cover_image_path) : null,
            'status' => $program->status,
            'is_featured' => $program->is_featured,
        ];
    }

    private function serializeDetail(Program $program): array
    {
        return [
            'id' => $program->id,
            'title' => $program->title,
            'slug' => $program->slug,
            'description' => $program->description,
            'category' => $program->category,
            'program_type' => $program->program_type,
            'location' => $program->location,
            'online_url' => $program->online_url,
            'capacity' => $program->capacity,
            'start_date' => $program->start_date?->format('Y-m-d\TH:i'),
            'start_date_human' => $program->start_date?->format('d/m/Y H:i'),
            'end_date' => $program->end_date?->format('Y-m-d\TH:i'),
            'end_date_human' => $program->end_date?->format('d/m/Y H:i'),
            'registration_deadline' => $program->registration_deadline?->format('Y-m-d\TH:i'),
            'registration_deadline_human' => $program->registration_deadline?->format('d/m/Y H:i'),
            'cover_image_url' => $program->cover_image_path ? Storage::disk('public')->url($program->cover_image_path) : null,
            'status' => $program->status,
            'is_featured' => $program->is_featured,
        ];
    }

    private function programAuditSnapshot(Program $program): array
    {
        return [
            'title' => $program->title,
            'slug' => $program->slug,
            'status' => $program->status,
            'program_type' => $program->program_type,
            'start_date' => $program->start_date?->toISOString(),
        ];
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Program $program): void
    {
        abort_unless($program->cooperative_id === $this->activeCooperative()?->id, 404);
    }

    private function ensureSameCooperativeMember(Member $member): void
    {
        abort_unless($member->cooperative_id === $this->activeCooperative()?->id, 404);
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => ProgramStatus::Draft->value, 'label' => 'Draf'],
            ['value' => ProgramStatus::Published->value, 'label' => 'Diterbitkan'],
            ['value' => ProgramStatus::Cancelled->value, 'label' => 'Dibatalkan'],
            ['value' => ProgramStatus::Completed->value, 'label' => 'Selesai'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => 'agm', 'label' => 'AGM / Mesyuarat Agung'],
            ['value' => 'seminar', 'label' => 'Seminar'],
            ['value' => 'kursus', 'label' => 'Kursus / Latihan'],
            ['value' => 'webinar', 'label' => 'Webinar'],
            ['value' => 'community', 'label' => 'Program Komuniti'],
            ['value' => 'volunteer', 'label' => 'Program Sukarelawan'],
            ['value' => 'social', 'label' => 'Acara Sosial'],
            ['value' => 'other', 'label' => 'Lain-lain'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua kategori'], ...$options]
            : $options;
    }

    private function programTypeOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => ProgramType::Physical->value, 'label' => 'Fizikal'],
            ['value' => ProgramType::Online->value, 'label' => 'Atas Talian'],
            ['value' => ProgramType::Hybrid->value, 'label' => 'Hibrid'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua jenis'], ...$options]
            : $options;
    }

    private function rsvpResponseOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => RsvpResponse::Hadir->value, 'label' => 'Hadir'],
            ['value' => RsvpResponse::TidakHadir->value, 'label' => 'Tidak Hadir'],
            ['value' => RsvpResponse::Mungkin->value, 'label' => 'Mungkin'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua respon'], ...$options]
            : $options;
    }
}
