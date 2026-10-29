<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Church\ChurchEvent;
use App\Models\Professional;
use App\Models\Testimony;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Public landing reads in one query.
 *
 * Mirrors `TestimonyController@publicIndex`, `ChurchEventController@publicIndex`,
 * `ChurchEventController@publicCarousel` and `ProfessionalController@publicIndex`
 * so a landing page can fetch every section it renders in one round trip.
 *
 * No authentication: these are the same unauthenticated REST endpoints.
 */
final class PublicContent
{
    private const EVENT_COLUMNS = [
        'id', 'name', 'slug_name', 'location', 'description',
        'publish_date', 'event_date', 'time_start', 'url_image',
        'classification', 'org_id', 'created_by', 'created_at', 'updated_at',
    ];

    private const DEFAULT_EVENT_DAYS = 15;

    private const DEFAULT_TESTIMONY_ITEMS_PER_PAGE = 25;

    private const DEFAULT_PROFESSIONAL_ITEMS_PER_PAGE = 20;

    public function __invoke(mixed $root, array $args): array
    {
        $orgId = $this->decodeOrgId($args['orgId'] ?? null);

        return [
            'testimonies' => $this->testimonies($orgId, $args),
            'events' => $this->events($orgId, $args),
            'carousel' => ($args['includeCarousel'] ?? true)
                ? $this->carousel($orgId, $args)
                : null,
            'professionals' => $this->professionals($args),
        ];
    }

    /**
     * The public endpoints receive a base64-encoded org id.
     */
    private function decodeOrgId(?string $orgId): ?int
    {
        if ($orgId === null || $orgId === '') {
            return null;
        }

        $decoded = base64_decode($orgId, true);

        return $decoded !== false && is_numeric($decoded) ? (int) $decoded : null;
    }

    private function testimonies(?int $orgId, array $args): array
    {
        $query = Testimony::query()
            ->where('status', 'approved')
            ->where('org_id', $orgId);

        if (! empty($args['testimonyFilter'])) {
            $query->where('name', 'like', '%'.$args['testimonyFilter'].'%');
        }

        $testimonies = $query
            ->orderByDesc('created_at')
            ->select(['name', 'categories', 'link', 'description', 'created_at'])
            ->paginate(
                (int) ($args['testimonyItemsPerPage'] ?? self::DEFAULT_TESTIMONY_ITEMS_PER_PAGE),
                ['*'],
                'page',
                (int) ($args['testimonyPage'] ?? 1),
            );

        return [
            'data' => collect($testimonies->items())
                ->map(fn (Testimony $testimony) => [
                    'name' => $testimony->name,
                    'categories' => $testimony->categories ?? [],
                    'link' => $testimony->link,
                    'description' => $testimony->description,
                    'created_at' => $testimony->created_at?->toIso8601String(),
                ])
                ->all(),
            'total' => $testimonies->total(),
            'itemsPerPage' => $testimonies->perPage(),
            'current_page' => $testimonies->currentPage(),
        ];
    }

    private function events(?int $orgId, array $args): array
    {
        $query = ChurchEvent::query()->where('org_id', $orgId);
        $today = Carbon::now()->subHours(6);

        if (! empty($args['eventSearch'])) {
            // Same narrow search as the REST endpoint: published, upcoming, max 15.
            $events = $query
                ->where('name', 'like', '%'.$args['eventSearch'].'%')
                ->whereDate('publish_date', '<=', $today)
                ->whereDate('event_date', '>=', $today)
                ->orderBy('event_date')->orderBy('time_start')
                ->limit(15)
                ->get(self::EVENT_COLUMNS);

            return $events->map(fn (ChurchEvent $event) => $this->eventPayload($event))->all();
        }

        $startDate = $args['eventStartDate'] ?? null;
        $endDate = $args['eventEndDate'] ?? null;
        $nextDays = $args['eventNextDays'] ?? self::DEFAULT_EVENT_DAYS;

        if (! is_numeric($nextDays) || $nextDays < 0) {
            $nextDays = self::DEFAULT_EVENT_DAYS;
        }

        // Same window as the REST endpoint: upcoming events within N days,
        // further narrowed (never widened) by the explicit date range.
        $events = $query
            ->whereDate('publish_date', '<=', $today)
            ->whereDate('event_date', '>=', $today)
            ->whereDate('event_date', '<=', $today->copy()->addDays((int) $nextDays))
            ->when($startDate, fn (Builder $q) => $q->whereDate('event_date', '>=', $startDate))
            ->when($endDate, fn (Builder $q) => $q->whereDate('event_date', '<=', $endDate))
            ->orderBy('event_date')->orderBy('time_start')
            ->get(self::EVENT_COLUMNS);

        return $events->map(fn (ChurchEvent $event) => $this->eventPayload($event))->all();
    }

    private function carousel(?int $orgId, array $args): array
    {
        $today = Carbon::now()->subHours(6);

        $nextDays = $args['carouselNextDays'] ?? self::DEFAULT_EVENT_DAYS;
        if (! is_numeric($nextDays) || $nextDays < 0) {
            $nextDays = self::DEFAULT_EVENT_DAYS;
        }

        $events = ChurchEvent::query()
            ->where('org_id', $orgId)
            ->whereDate('publish_date', '<=', $today)
            ->whereDate('event_date', '>=', $today)
            ->whereDate('event_date', '<=', $today->copy()->addDays((int) $nextDays))
            ->orderBy('event_date')->orderBy('time_start')
            ->get(self::EVENT_COLUMNS);

        return $events->map(fn (ChurchEvent $event) => $this->eventPayload($event))->all();
    }

    private function professionals(array $args): array
    {
        $query = Professional::query();

        if (! empty($args['professionalFilter'])) {
            $filter = $args['professionalFilter'];
            $query->where(function (Builder $q) use ($filter) {
                $q->where('title', 'like', '%'.$filter.'%')
                    ->orWhere('name', 'like', '%'.$filter.'%')
                    ->orWhere('last_name', 'like', '%'.$filter.'%')
                    ->orWhere('description', 'like', '%'.$filter.'%');
            });
        }

        $professionals = $query
            ->orderByDesc('created_at')
            ->select(['id', 'name', 'last_name', 'phone_number', 'title', 'description', 'status', 'created_at'])
            ->paginate(
                (int) ($args['professionalItemsPerPage'] ?? self::DEFAULT_PROFESSIONAL_ITEMS_PER_PAGE),
                ['*'],
                'page',
                (int) ($args['professionalPage'] ?? 1),
            );

        return [
            'data' => collect($professionals->items())
                ->map(fn (Professional $professional) => [
                    'id' => $professional->id,
                    'name' => $professional->name,
                    'last_name' => $professional->last_name,
                    'phone_number' => $professional->phone_number,
                    'title' => $professional->title,
                    'description' => $professional->description,
                    'status' => $professional->status,
                    'created_at' => $professional->created_at?->toIso8601String(),
                ])
                ->all(),
            'total' => $professionals->total(),
            'itemsPerPage' => $professionals->perPage(),
            'current_page' => $professionals->currentPage(),
        ];
    }

    private function eventPayload(ChurchEvent $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'slug_name' => $event->slug_name,
            'location' => $event->location,
            'description' => $event->description,
            'publish_date' => $event->publish_date?->format('Y-m-d'),
            'event_date' => $event->event_date?->format('Y-m-d'),
            'time_start' => $event->time_start?->format('H:i'),
            'url_image_s3' => $event->url_image_s3,
            'classification' => $event->classification,
            'org_id' => $event->org_id,
            'created_by' => $event->created_by,
            'created_at' => $event->created_at?->toIso8601String(),
            'updated_at' => $event->updated_at?->toIso8601String(),
        ];
    }
}
