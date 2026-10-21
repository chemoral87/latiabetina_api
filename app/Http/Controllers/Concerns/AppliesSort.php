<?php

namespace App\Http\Controllers\Concerns;

trait AppliesSort
{
    protected function applySort($query, $sortBy, $sortDesc, array $allowed = [])
    {
        if (empty($sortBy)) {
            return $query;
        }

        $columns = is_array($sortBy) ? $sortBy : [$sortBy];
        $directions = is_array($sortDesc) ? $sortDesc : [];

        foreach ($columns as $index => $column) {
            if (!is_string($column)) {
                continue;
            }
            if ($allowed && !in_array($column, $allowed, true)) {
                continue;
            }
            $dir = (isset($directions[$index]) && $directions[$index] == 'true') ? 'desc' : 'asc';
            $query->orderBy($column, $dir);
        }

        return $query;
    }
}
