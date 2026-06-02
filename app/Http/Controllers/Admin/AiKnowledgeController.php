<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiDocumentChunk;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiKnowledgeController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(): Response
    {
        $documents = AiDocumentChunk::query()
            ->select('document_name')
            ->selectRaw('COUNT(*) as chunk_count')
            ->selectRaw('MAX(created_at) as last_uploaded')
            ->groupBy('document_name')
            ->orderByDesc('last_uploaded')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->document_name,
                'chunk_count' => $item->chunk_count,
                'last_uploaded' => $item->last_uploaded,
            ]);

        return Inertia::render('Admin/Pages/AiKnowledge/Index', [
            'documents' => $documents,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf|max:10240',
        ]);

        $file = $request->file('document');
        $documentName = $file->getClientOriginalName();

        $parser = new \Smalot\PdfParser\Parser;
        $pdf = $parser->parseFile($file->path());
        $text = $pdf->getText();

        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        $chunks = str_split($text, 1000);

        foreach ($chunks as $chunk) {
            $trimmed = trim($chunk);
            if (!empty($trimmed)) {
                AiDocumentChunk::create([
                    'document_name' => $documentName,
                    'content' => $trimmed,
                ]);
            }
        }

        $this->auditLogs->log('ai_knowledge_upload', "Muat naik dokumen AI: {$documentName}");

        return redirect()->back()->with('status', 'Dokumen berjaya dimuat naik.');
    }

    public function destroy(string $documentName): RedirectResponse
    {
        $documentName = urldecode($documentName);
        $deleted = AiDocumentChunk::where('document_name', $documentName)->delete();

        if ($deleted) {
            $this->auditLogs->log('ai_knowledge_delete', "Padam dokumen AI: {$documentName}");
        }

        return redirect()->back()->with('status', 'Dokumen berjaya dipadam.');
    }
}
