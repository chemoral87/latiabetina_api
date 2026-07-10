<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class ChurchEventCopyService
{
    public function resolveDates(array $payload, ?string $originalEventDate = null): array
    {
        $dates = $payload['dates'] ?? [];
        if (!empty($dates)) {
            return $this->normalizeDates($dates, $originalEventDate);
        }

        $recurrence = $payload['recurrence'] ?? [];
        if (empty($recurrence)) {
            return [];
        }

        $startDate = $recurrence['start_date'] ?? null;
        $endDate = $recurrence['end_date'] ?? null;
        $daysOfWeek = $recurrence['days_of_week'] ?? [];

        if (empty($startDate) || empty($endDate) || empty($daysOfWeek)) {
            return [];
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return [];
        }

        $selectedDays = array_values(array_unique(array_map('intval', $daysOfWeek)));
        $generatedDates = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dayOfWeek = (int) $cursor->dayOfWeek;

            if (in_array($dayOfWeek, $selectedDays, true)) {
                $date = $cursor->format('Y-m-d');
                if ($originalEventDate !== null && $date === $originalEventDate) {
                    $cursor->addDay();
                    continue;
                }

                $generatedDates[] = $date;
            }

            $cursor->addDay();
        }

        return $generatedDates;
    }

    protected function normalizeDates(array $dates, ?string $originalEventDate = null): array
    {
        $normalized = [];

        foreach ($dates as $date) {
            $parsedDate = $this->parseDate($date);
            if ($parsedDate === null) {
                continue;
            }

            if ($originalEventDate !== null && $parsedDate === $originalEventDate) {
                continue;
            }

            $normalized[] = $parsedDate;
        }

        return array_values(array_unique($normalized));
    }

    protected function parseDate($value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && !empty($value)) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
