<?php

namespace Tests\Unit;

use App\Services\ChurchEventCopyService;
use PHPUnit\Framework\TestCase;

class ChurchEventCopyServiceTest extends TestCase
{
    public function test_it_generates_dates_for_selected_weekdays_in_a_range(): void
    {
        $service = new ChurchEventCopyService();

        $dates = $service->resolveDates([
            'recurrence' => [
                'start_date' => '2026-01-04',
                'end_date' => '2026-01-11',
                'days_of_week' => [0],
            ],
        ], '2026-01-01');

        $this->assertSame(['2026-01-04', '2026-01-11'], $dates);
    }

    public function test_it_excludes_the_original_event_date_from_the_generated_dates(): void
    {
        $service = new ChurchEventCopyService();

        $dates = $service->resolveDates([
            'recurrence' => [
                'start_date' => '2026-01-04',
                'end_date' => '2026-01-04',
                'days_of_week' => [0],
            ],
        ], '2026-01-04');

        $this->assertSame([], $dates);
    }
}
