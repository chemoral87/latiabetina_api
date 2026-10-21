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
            return response()->json([
                'data' => $all,
                'total' => $all->count(),
                'itemsPerPage' => -1,
            ]);
        }

        $page = $request->get('page', 1);
        $paginated = $query->paginate((int) $itemsPerPage, ['*'], 'page', (int) $page);
        return new DataSetResource($paginated);
    }

    public function show($id)
    {
        return response()->json(LogEntry::findOrFail($id));
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
