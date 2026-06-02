<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\DocumentVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Http\Requests\Admin\UpdateDocumentRequest;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\Files\DocumentStorageService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly DocumentStorageService $documents,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $visibility = $request->string('visibility')->toString();
        $category = $request->integer('category');

        $documents = Document::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->with('category')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('file_name', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, DocumentStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($visibility, DocumentVisibility::values(), true), fn ($query) => $query->where('visibility', $visibility))
            ->when($category > 0, fn ($query) => $query->where('document_category_id', $category))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Document $document) => $this->serializeDocument($document));

        return Inertia::render('Admin/Pages/Documents/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'visibility' => $visibility,
                'category' => $category ?: '',
            ],
            'documents' => $documents,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'visibilityOptions' => $this->visibilityOptions(includeAll: true),
            'categoryOptions' => $this->categoryOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_DOCUMENTS) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_DOCUMENTS) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_DOCUMENTS) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Documents/Form', [
            'mode' => 'create',
            'documentRecord' => null,
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
        ]);
    }

    public function edit(Document $document): Response
    {
        $this->ensureSameCooperative($document);

        return Inertia::render('Admin/Pages/Documents/Form', [
            'mode' => 'edit',
            'documentRecord' => $this->serializeDocument($document->load('category')),
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->store(
            $request->file('file'),
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('admin.documents.edit', $document)
            ->with('status', 'Dokumen berjaya dimuat naik.');
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->ensureSameCooperative($document);

        $validated = $request->validated();

        $document->update([
            'document_category_id' => $validated['document_category_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
            'status' => $validated['status'],
            'version' => $validated['version'] ?? null,
            'published_at' => $validated['status'] === DocumentStatus::Published->value
                ? ($validated['published_at'] ?? $document->published_at ?? now())
                : ($validated['published_at'] ?? null),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        if ($request->hasFile('file')) {
            $this->documents->replace($document, $request->file('file'));
        }

        return back()->with('status', 'Dokumen berjaya dikemas kini.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->ensureSameCooperative($document);
        $this->documents->delete($document);

        return redirect()
            ->route('admin.documents.index')
            ->with('status', 'Dokumen berjaya dipadam.');
    }

    public function download(Document $document): StreamedResponse
    {
        $this->ensureSameCooperative($document);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->file_name ?: basename($document->file_path)
        );
    }

    private function serializeDocument(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'slug' => $document->slug,
            'description' => $document->description,
            'document_category_id' => $document->document_category_id,
            'category_name' => $document->category?->name,
            'visibility' => $document->visibility->value,
            'status' => $document->status->value,
            'version' => $document->version,
            'file_name' => $document->file_name,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'file_size_label' => $this->formatBytes($document->file_size),
            'published_at' => $document->published_at?->format('Y-m-d\TH:i'),
            'published_at_human' => $document->published_at?->format('d/m/Y H:i'),
            'expires_at' => $document->expires_at?->format('Y-m-d\TH:i'),
            'updated_at' => $document->updated_at?->format('d/m/Y H:i'),
            'download_url' => route('admin.documents.download', $document),
        ];
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Document $document): void
    {
        abort_unless(
            $document->cooperative_id && $document->cooperative_id === $this->activeCooperative()?->id,
            404
        );
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $options = DocumentCategory::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->active()
            ->latest()
            ->get()
            ->map(fn (DocumentCategory $category) => [
                'value' => $category->id,
                'label' => $category->name,
            ])
            ->all();

        return $includeAll
            ? [['value' => '', 'label' => 'Semua kategori'], ...$options]
            : [['value' => '', 'label' => 'Tanpa kategori'], ...$options];
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => DocumentStatus::Draft->value, 'label' => 'Draf'],
            ['value' => DocumentStatus::Published->value, 'label' => 'Diterbitkan'],
            ['value' => DocumentStatus::Archived->value, 'label' => 'Diarkibkan'],
            ['value' => DocumentStatus::Expired->value, 'label' => 'Tamat'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }

    private function visibilityOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => DocumentVisibility::Public->value, 'label' => 'Public'],
            ['value' => DocumentVisibility::MembersOnly->value, 'label' => 'Ahli sahaja'],
            ['value' => DocumentVisibility::AdminOnly->value, 'label' => 'Admin sahaja'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua akses'], ...$options]
            : $options;
    }

    private function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '-';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 0).' KB';
    }
}