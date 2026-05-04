<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        return $this->renderPage($request);
    }

    public function show(Request $request, AuditLog $auditLog): Response
    {
        $this->ensureVisible($auditLog);

        return $this->renderPage($request, $auditLog);
    }

    private function renderPage(Request $request, ?AuditLog $selectedLog = null): Response
    {
        $search = trim((string) $request->string('search'));
        $actorId = $request->integer('actor');
        $action = trim((string) $request->string('action'));
        $subjectType = trim((string) $request->string('subject_type'));
        $dateFrom = trim((string) $request->string('date_from'));
        $dateTo = trim((string) $request->string('date_to'));
        $cooperativeId = $this->activeCooperative()?->id;

        $logs = AuditLog::query()
            ->with('actor')
            ->where('cooperative_id', $cooperativeId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('action', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('subject_id', 'like', "%{$search}%")
                        ->orWhereHas('actor', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($actorId > 0, fn (Builder $query) => $query->where('actor_id', $actorId))
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->when($subjectType !== '', fn (Builder $query) => $query->where('subject_type', $subjectType))
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AuditLog $log) => $this->serializeSummary($request, $log));

        return Inertia::render('Admin/Pages/AuditLogs/Index', [
            'filters' => [
                'search' => $search,
                'actor' => $actorId > 0 ? (string) $actorId : '',
                'action' => $action,
                'subject_type' => $subjectType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'auditLogs' => $logs,
            'actorOptions' => $this->actorOptions($cooperativeId),
            'actionOptions' => $this->actionOptions($cooperativeId),
            'subjectTypeOptions' => $this->subjectTypeOptions($cooperativeId),
            'selectedLog' => $selectedLog ? $this->serializeDetail($request, $selectedLog->loadMissing('actor')) : null,
        ]);
    }

    private function actorOptions(?int $cooperativeId): array
    {
        $actorIds = AuditLog::query()
            ->where('cooperative_id', $cooperativeId)
            ->whereNotNull('actor_id')
            ->distinct()
            ->pluck('actor_id');

        $options = User::query()
            ->whereIn('id', $actorIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'value' => (string) $user->id,
                'label' => $user->name,
            ])
            ->all();

        return [['value' => '', 'label' => 'Semua pelaku'], ...$options];
    }

    private function actionOptions(?int $cooperativeId): array
    {
        $options = AuditLog::query()
            ->where('cooperative_id', $cooperativeId)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => $this->actionLabel($value),
            ])
            ->all();

        return [['value' => '', 'label' => 'Semua tindakan'], ...$options];
    }

    private function subjectTypeOptions(?int $cooperativeId): array
    {
        $options = AuditLog::query()
            ->where('cooperative_id', $cooperativeId)
            ->whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => $this->subjectTypeLabel($value),
            ])
            ->all();

        return [['value' => '', 'label' => 'Semua modul'], ...$options];
    }

    private function serializeSummary(Request $request, AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'action_label' => $this->actionLabel($log->action),
            'module_label' => $this->subjectTypeLabel($log->subject_type),
            'subject_label' => $this->subjectSummary($log),
            'actor_name' => $log->actor?->name ?? 'Sistem / Pelawat',
            'actor_email' => $log->actor?->email,
            'created_at' => $log->created_at?->format('d/m/Y H:i:s'),
            'show_url' => route('admin.audit-logs.show', [
                ...$request->query(),
                'auditLog' => $log,
            ]),
        ];
    }

    private function serializeDetail(Request $request, AuditLog $log): array
    {
        return [
            ...$this->serializeSummary($request, $log),
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'old_values' => $this->formatPayload($log->old_values),
            'new_values' => $this->formatPayload($log->new_values),
            'metadata' => $this->formatPayload($log->metadata),
            'index_url' => route('admin.audit-logs.index', $request->query()),
        ];
    }

    private function formatPayload(?array $payload): array
    {
        if (! is_array($payload) || $payload === []) {
            return [];
        }

        return collect($payload)
            ->mapWithKeys(fn (mixed $value, string|int $key) => [
                (string) $key => $this->formatPayloadValue($value),
            ])
            ->all();
    }

    private function formatPayloadValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        }

        return (string) $value;
    }

    private function actionLabel(string $action): string
    {
        return [
            'settings_updated' => 'Tetapan dikemas kini',
            'page_created' => 'Halaman dicipta',
            'page_updated' => 'Halaman dikemas kini',
            'page_published' => 'Halaman diterbitkan',
            'page_unpublished' => 'Halaman dinyahterbit',
            'page_archived' => 'Halaman diarkibkan',
            'section_created' => 'Seksyen dicipta',
            'section_updated' => 'Seksyen dikemas kini',
            'section_deleted' => 'Seksyen dipadam',
            'document_uploaded' => 'Dokumen dimuat naik',
            'document_deleted' => 'Dokumen dipadam',
            'announcement_published' => 'Pengumuman diterbitkan',
            'announcement.archived' => 'Pengumuman diarkibkan',
            'announcement.deleted' => 'Pengumuman dipadam',
            'announcement.pinned' => 'Pengumuman dipin',
            'announcement.unpinned' => 'Pin pengumuman dibuang',
            'announcement.unpublished' => 'Pengumuman dinyahterbit',
            'service_updated' => 'Perkhidmatan dikemas kini',
            'service.published' => 'Perkhidmatan diterbitkan',
            'service.archived' => 'Perkhidmatan diarkibkan',
            'service.deleted' => 'Perkhidmatan dipadam',
            'service.unpublished' => 'Perkhidmatan dinyahterbit',
            'membership_application_submitted' => 'Permohonan dihantar',
            'application_under_review' => 'Permohonan dalam semakan',
            'application_approved' => 'Permohonan diluluskan',
            'application_rejected' => 'Permohonan ditolak',
            'membership_application.cancelled' => 'Permohonan dibatalkan',
            'member_created' => 'Ahli dicipta',
            'member_updated' => 'Ahli dikemas kini',
            'member_status_changed' => 'Status ahli dikemas kini',
            'member_linked_to_user' => 'Ahli dipautkan kepada pengguna',
            'complaint_submitted' => 'Aduan dihantar',
            'complaint_replied' => 'Balasan aduan ditambah',
            'complaint_status_changed' => 'Status aduan dikemas kini',
            'complaint_closed' => 'Aduan ditutup',
        ][$action] ?? str($action)->replace(['.', '_'], ' ')->title()->value();
    }

    private function subjectTypeLabel(?string $subjectType): string
    {
        return match ($subjectType) {
            Cooperative::class => 'Tetapan Koperasi',
            Page::class => 'Halaman CMS',
            PageSection::class => 'Seksyen Halaman',
            Document::class => 'Dokumen',
            Service::class => 'Perkhidmatan',
            MembershipApplication::class => 'Permohonan Keahlian',
            Member::class => 'Ahli',
            Complaint::class => 'Aduan',
            default => $subjectType ? class_basename($subjectType) : 'Umum',
        };
    }

    private function subjectSummary(AuditLog $log): string
    {
        $summary = collect([
            data_get($log->new_values, 'title'),
            data_get($log->new_values, 'name'),
            data_get($log->new_values, 'full_name'),
            data_get($log->new_values, 'subject'),
            data_get($log->new_values, 'application_no'),
            data_get($log->old_values, 'title'),
            data_get($log->old_values, 'name'),
            data_get($log->old_values, 'full_name'),
            data_get($log->old_values, 'subject'),
            data_get($log->old_values, 'application_no'),
        ])->first(fn (?string $value) => filled($value));

        if ($summary) {
            return $summary;
        }

        if ($log->subject_id) {
            return sprintf('%s #%s', $this->subjectTypeLabel($log->subject_type), $log->subject_id);
        }

        return 'Rekod umum';
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureVisible(AuditLog $auditLog): void
    {
        abort_unless($auditLog->cooperative_id === $this->activeCooperative()?->id, 404);
    }
}
