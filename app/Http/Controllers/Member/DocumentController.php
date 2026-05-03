<?php

namespace App\Http\Controllers\Member;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends MemberPortalController
{
    public function index(Request $request): Response
    {
        $member = $this->currentMemberOrNull($request);
        $cooperativeId = $this->activeCooperativeId($request);

        $memberDocuments = $member
            ? Document::query()
                ->published()
                ->where('cooperative_id', $cooperativeId)
                ->where('visibility', DocumentVisibility::SpecificMember->value)
                ->where('member_id', $member->id)
                ->with('category')
                ->latest('published_at')
                ->latest('updated_at')
                ->get()
                ->map(fn (Document $document) => $this->serializeDocument($document))
                ->all()
            : [];

        $generalDocuments = Document::query()
            ->published()
            ->where('cooperative_id', $cooperativeId)
            ->where('visibility', DocumentVisibility::MembersOnly->value)
            ->with('category')
            ->latest('published_at')
            ->latest('updated_at')
            ->get()
            ->map(fn (Document $document) => $this->serializeDocument($document))
            ->all();

        return Inertia::render('Member/Pages/Documents/Index', [
            'memberLinked' => (bool) $member,
            'memberDocuments' => $memberDocuments,
            'generalDocuments' => $generalDocuments,
        ]);
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('viewMember', $document);

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
            'description' => $document->description,
            'category_name' => $document->category?->name,
            'visibility' => $document->visibility->value,
            'file_name' => $document->file_name,
            'file_size_label' => $this->formatBytes($document->file_size),
            'published_at' => $document->published_at?->format('d/m/Y'),
            'download_url' => route('member.documents.download', $document),
        ];
    }
}
