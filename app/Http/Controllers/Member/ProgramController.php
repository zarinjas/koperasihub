<?php

namespace App\Http\Controllers\Member;

use App\Enums\AttendanceMethod;
use App\Enums\ProgramStatus;
use App\Enums\RsvpResponse;
use App\Models\Program;
use App\Models\ProgramRsvp;
use App\Models\User;
use App\Notifications\ProgramRsvpNotification;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends MemberPortalController
{
    public function index(Request $request): Response
    {
        $tab = $request->string('tab')->toString();
        $member = $this->currentMember($request);

        $cooperativeId = $this->activeCooperativeId($request);

        $programs = Program::query()
            ->forCooperative($cooperativeId)
            ->published()
            ->when($tab === 'past', fn ($q) => $q->past(), fn ($q) => $q->upcoming())
            ->withCount([
                'rsvps',
                'rsvps as rsvps_hadir_count' => fn ($q) => $q->where('response', RsvpResponse::Hadir->value),
            ])
            ->withExists([
                'rsvps as user_rsvp_exists' => fn ($q) => $q->where('member_id', $member?->id),
            ])
            ->orderBy('start_date')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Program $program) => $this->serializeMemberProgram($program, $member?->id));

        return Inertia::render('Member/Pages/Programs/Index', [
            'programs' => $programs,
            'tab' => $tab === 'past' ? 'past' : 'upcoming',
        ]);
    }

    public function show(Request $request, Program $program): Response
    {
        $member = $this->currentMember($request);
        $cooperativeId = $this->activeCooperativeId($request);

        abort_unless($program->cooperative_id === $cooperativeId, 404);
        abort_unless($program->status === ProgramStatus::Published->value, 404);

        $rsvp = null;
        if ($member) {
            $rsvp = ProgramRsvp::query()
                ->where('program_id', $program->id)
                ->where('member_id', $member->id)
                ->first();
        }

        return Inertia::render('Member/Pages/Programs/Show', [
            'program' => $this->serializeMemberProgram($program, $member?->id),
            'rsvp' => $rsvp ? [
                'id' => $rsvp->id,
                'response' => $rsvp->response,
                'responded_at' => $rsvp->responded_at?->format('d/m/Y H:i'),
                'checked_in_at' => $rsvp->checked_in_at?->format('d/m/Y H:i'),
                'checked_in' => $rsvp->checked_in_at !== null,
            ] : null,
            'rsvpOptions' => [
                ['value' => RsvpResponse::Hadir->value, 'label' => 'Hadir'],
                ['value' => RsvpResponse::TidakHadir->value, 'label' => 'Tidak Hadir'],
                ['value' => RsvpResponse::Mungkin->value, 'label' => 'Mungkin'],
            ],
        ]);
    }

    public function rsvp(Request $request, Program $program): RedirectResponse
    {
        $member = $this->currentMember($request);
        $cooperativeId = $this->activeCooperativeId($request);

        abort_unless($program->cooperative_id === $cooperativeId, 404);
        abort_unless($program->status === ProgramStatus::Published->value, 404);
        abort_unless($member, 403);

        $validated = $request->validate([
            'response' => ['required', Rule::in(RsvpResponse::values())],
        ]);

        $rsvp = ProgramRsvp::query()->updateOrCreate(
            [
                'program_id' => $program->id,
                'member_id' => $member->id,
            ],
            [
                'cooperative_id' => $cooperativeId,
                'response' => $validated['response'],
                'responded_at' => now(),
            ],
        );

        $admins = User::query()
            ->whereIn('role', AccessControl::adminRoles())
            ->where('status', 'active')
            ->get();

        Notification::send($admins, new ProgramRsvpNotification($rsvp, $member, $program));

        $labels = [
            RsvpResponse::Hadir->value => 'Hadir',
            RsvpResponse::TidakHadir->value => 'Tidak Hadir',
            RsvpResponse::Mungkin->value => 'Mungkin',
        ];

        return back()->with('status', 'Respon anda: ' . ($labels[$validated['response']] ?? $validated['response']));
    }

    public function checkIn(Request $request, Program $program): Response|RedirectResponse
    {
        $member = $this->currentMember($request);
        $cooperativeId = $this->activeCooperativeId($request);

        abort_unless($program->cooperative_id === $cooperativeId, 404);
        abort_unless($program->status === ProgramStatus::Published->value, 404);
        abort_unless($member, 403);

        if ($request->isMethod('post')) {
            $rsvp = ProgramRsvp::query()->firstOrCreate(
                [
                    'program_id' => $program->id,
                    'member_id' => $member->id,
                ],
                [
                    'cooperative_id' => $cooperativeId,
                    'response' => RsvpResponse::Hadir->value,
                    'responded_at' => now(),
                ],
            );

            if ($rsvp->checked_in_at === null) {
                $rsvp->update([
                    'checked_in_at' => now(),
                    'attendance_method' => AttendanceMethod::MemberScanEventQr->value,
                ]);
            }

            return redirect()
                ->route('member.programs.show', $program)
                ->with('status', 'Kehadiran anda telah direkodkan.');
        }

        return Inertia::render('Member/Pages/Programs/CheckIn', [
            'program' => $this->serializeMemberProgram($program, $member->id),
            'alreadyCheckedIn' => ProgramRsvp::query()
                ->where('program_id', $program->id)
                ->where('member_id', $member->id)
                ->whereNotNull('checked_in_at')
                ->exists(),
        ]);
    }

    public function attendanceHistory(Request $request): Response
    {
        $member = $this->currentMember($request);
        abort_unless($member, 403);

        $records = ProgramRsvp::query()
            ->where('member_id', $member->id)
            ->with('program')
            ->whereHas('program', fn ($q) => $q->forCooperative($this->activeCooperativeId($request)))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ProgramRsvp $rsvp) => [
                'id' => $rsvp->id,
                'program_id' => $rsvp->program_id,
                'program_title' => $rsvp->program->title,
                'program_category' => $rsvp->program->category,
                'program_type' => $rsvp->program->program_type,
                'program_start_date' => $rsvp->program->start_date?->format('d/m/Y H:i'),
                'response' => $rsvp->response,
                'responded_at' => $rsvp->responded_at?->format('d/m/Y H:i'),
                'checked_in_at' => $rsvp->checked_in_at?->format('d/m/Y H:i'),
                'checked_in' => $rsvp->checked_in_at !== null,
                'attendance_method' => $rsvp->attendance_method,
                'program_status' => $rsvp->program->status,
            ]);

        $stats = [
            'total_programs' => ProgramRsvp::query()->where('member_id', $member->id)->count(),
            'total_attended' => ProgramRsvp::query()->where('member_id', $member->id)->whereNotNull('checked_in_at')->count(),
            'upcoming' => Program::query()
                ->forCooperative($this->activeCooperativeId($request))
                ->published()
                ->upcoming()
                ->whereHas('rsvps', fn ($q) => $q->where('member_id', $member->id))
                ->count(),
        ];

        return Inertia::render('Member/Pages/Attendance/Index', [
            'records' => $records,
            'stats' => $stats,
        ]);
    }

    private function serializeMemberProgram(Program $program, ?int $memberId): array
    {
        $rsvp = null;
        if ($memberId) {
            $userRsvp = $program->rsvps()->where('member_id', $memberId)->first();
            if ($userRsvp) {
                $rsvp = [
                    'response' => $userRsvp->response,
                    'checked_in' => $userRsvp->checked_in_at !== null,
                    'checked_in_at' => $userRsvp->checked_in_at?->format('d/m/Y H:i'),
                ];
            }
        }

        return [
            'id' => $program->id,
            'title' => $program->title,
            'description' => $program->description,
            'category' => $program->category,
            'program_type' => $program->program_type,
            'location' => $program->location,
            'online_url' => $program->online_url,
            'capacity' => $program->capacity,
            'start_date' => $program->start_date?->format('Y-m-d\TH:i'),
            'start_date_human' => $program->start_date?->format('d/m/Y H:i'),
            'start_date_formatted' => $program->start_date?->format('j F Y'),
            'start_time' => $program->start_date?->format('H:i'),
            'end_date' => $program->end_date?->format('Y-m-d\TH:i'),
            'end_date_human' => $program->end_date?->format('d/m/Y H:i'),
            'end_date_formatted' => $program->end_date?->format('j F Y'),
            'end_time' => $program->end_date?->format('H:i'),
            'registration_deadline_human' => $program->registration_deadline?->format('d/m/Y H:i'),
            'cover_image_url' => $program->cover_image_path ? Storage::disk('public')->url($program->cover_image_path) : null,
            'status' => $program->status,
            'is_upcoming' => $program->start_date?->isFuture(),
            'is_ongoing' => $program->start_date?->isPast() && ($program->end_date?->isFuture() ?? true),
            'is_past' => $program->end_date?->isPast() ?? $program->start_date?->isPast(),
            'registration_open' => $program->registration_deadline === null || $program->registration_deadline->isFuture(),
            'rsvps_count' => $program->rsvps_count ?? $program->rsvps()->count(),
            'rsvps_hadir_count' => $program->rsvps_hadir_count ?? 0,
            'user_rsvp' => $rsvp,
        ];
    }
}