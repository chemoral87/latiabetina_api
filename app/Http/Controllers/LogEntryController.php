<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\LogEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = LogEntry::query();

        $itemsPerPage = $request->get('itemsPerPage', 10);
        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc');
        $filter = $request->get('filter');
        $level = $request->get('level');

        if ($filter) {
            $term = '%' . $filter . '%';
            $query->where(function ($q) use ($term) {
                $q->where('message', 'like', $term)
                  ->orWhere('channel', 'like', $term);
            });
        }

        if ($level) {
            $query->where('level', $level);
        }

        if ($sortBy) {
            $columns = is_array($sortBy) ? $sortBy : [$sortBy];
            $directions = (is_array($sortDesc) ? $sortDesc : []);
            foreach ($columns as $index => $column) {
                if (!in_array($column, ['id', 'level', 'channel', 'message', 'created_at'], true)) {
                    continue;
                }
                $dir = (isset($directions[$index]) && $directions[$index] == 'true') ? 'desc' : 'asc';
                $query->orderBy($column, $dir);
            }
        } else {
            $query->orderByDesc('created_at');
        }

        if ((int) $itemsPerPage === -1 || $itemsPerPage === '-1') {
            $all = $query->get();
            $this->truncateContexts($all);
            return response()->json([
                'data' => $all,
                'total' => $all->count(),
                'itemsPerPage' => -1,
            ]);
        }

        $page = $request->get('page', 1);
        $paginated = $query->paginate((int) $itemsPerPage, ['*'], 'page', (int) $page);
        $this->truncateContexts($paginated->getCollection());
        return new DataSetResource($paginated);
    }

    public function show($id)
    {
        $entry = LogEntry::findOrFail($id);
        $this->truncateContexts(collect([$entry]));
        return response()->json($entry);
    }

    /**
     * Recorta el contexto JSON a config('log-entry.context_max_chars') caracteres.
     * El recorte se hace en el backend para que el limite no viva en el frontend.
     */
    private function truncateContexts(iterable $entries): void
    {
        $max = (int) config('log-entry.context_max_chars', 1000);

        foreach ($entries as $entry) {
            $context = $entry->context;
            if ($context === null) {
                continue;
            }

            $json = is_string($context)
                ? $context
                : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($json === false || mb_strlen($json) <= $max) {
                continue;
            }

            $entry->context = mb_substr($json, 0, $max) . '…';
        }
    }

    public function destroyMany(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $deleted = LogEntry::whereIn('id', $validated['ids'])->delete();

        return response()->json(['deleted' => $deleted]);
    }

    public function testError()
    {
        Log::error('E2E test error from log-entry page', [
            'source' => 'log-entry-e2e',
            'stamp' => now()->toIso8601String(),
        ]);
        return response()->json(['success' => true]);
    }
}
