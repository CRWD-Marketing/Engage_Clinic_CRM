<?php

namespace App\Services\AiEmployee;

use App\Models\KnowledgeBaseEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keyword/category retrieval over the knowledge base - no embeddings, per the
 * clinic's small (dozens-of-entries) knowledge base. MySQL FULLTEXT gives real
 * relevance ranking; the LIKE fallback covers non-MySQL local dev and the case
 * where FULLTEXT finds nothing for a short/unusual query.
 */
class KnowledgeBaseService
{
    public function search(string $query, int $limit = 5): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return KnowledgeBaseEntry::active()->orderBy('priority')->limit($limit)->get();
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $entries = KnowledgeBaseEntry::active()
                ->selectRaw('*, MATCH(title, content) AGAINST (? IN NATURAL LANGUAGE MODE) AS relevance', [$query])
                ->whereRaw('MATCH(title, content) AGAINST (? IN NATURAL LANGUAGE MODE)', [$query])
                ->orderByDesc('relevance')
                ->orderBy('priority')
                ->limit($limit)
                ->get();

            if ($entries->isNotEmpty()) {
                return $entries;
            }
        }

        $words = collect(preg_split('/\s+/', $query))
            ->filter(fn ($word) => mb_strlen($word) >= 3)
            ->take(8);

        if ($words->isEmpty()) {
            return KnowledgeBaseEntry::active()->orderBy('priority')->limit($limit)->get();
        }

        return KnowledgeBaseEntry::active()
            ->where(function ($outer) use ($words) {
                foreach ($words as $word) {
                    $outer->orWhere(function ($inner) use ($word) {
                        $inner->where('title', 'like', "%{$word}%")
                            ->orWhere('category', 'like', "%{$word}%")
                            ->orWhere('content', 'like', "%{$word}%");
                    });
                }
            })
            ->orderBy('priority')
            ->limit($limit)
            ->get();
    }
}
