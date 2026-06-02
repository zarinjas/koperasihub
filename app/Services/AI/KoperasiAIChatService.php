<?php

namespace App\Services\AI;

use App\Models\AiDocumentChunk;
use Illuminate\Support\Str;

class KoperasiAIChatService
{
    private const STOP_WORDS = [
        'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'pada',
        'adalah', 'ini', 'itu', 'saya', 'anda', 'kami', 'mereka',
        'apa', 'bagaimana', 'kenapa', 'bila', 'mana', 'siapa', 'macam',
        'tentang', 'sahaja', 'sebagai', 'oleh', 'atau', 'tetapi', 'juga',
        'sudah', 'belum', 'lagi', 'boleh', 'akan', 'telah', 'sedang',
        'ada', 'tidak', 'bukan', 'tolong', 'nak', 'mahu', 'hendak', 'perlu',
        'tu', 'ni', 'je', 'lah', 'kah', 'lah', 'pun', 'ya', 'oh', 'ha',
        'dah', 'kan', 'i', 'the', 'a', 'an', 'is', 'it', 'to', 'in',
    ];

    public function ask(string $question): string
    {
        $keywords = $this->extractKeywords($question);
        $chunks = $this->searchChunks($keywords);

        if ($chunks->isEmpty()) {
            return 'Maaf, maklumat yang anda cari tidak dijumpai dalam dokumen koperasi. Sila cuba soalan lain atau hubungi pihak koperasi untuk bantuan lanjut.';
        }

        $response = "Berdasarkan dokumen koperasi:\n\n";
        foreach ($chunks as $i => $chunk) {
            $response .= ($i + 1) . '. ' . trim($chunk->content) . "\n\n";
        }

        return trim($response);
    }

    private function extractKeywords(string $question): array
    {
        $words = preg_split('/[\s\p{P}]+/u', Str::lower($question));

        return array_values(array_filter($words, fn ($word) =>
            strlen($word) > 2 && !in_array($word, self::STOP_WORDS)
        ));
    }

    private function searchChunks(array $keywords)
    {
        if (empty($keywords)) {
            return collect();
        }

        $query = AiDocumentChunk::query();

        foreach ($keywords as $keyword) {
            $query->orWhere('content', 'LIKE', '%' . $keyword . '%');
        }

        return $query->limit(10)->get();
    }
}
