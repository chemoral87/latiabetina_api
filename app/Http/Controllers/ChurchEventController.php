<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\ChurchEvent;
use App\Services\ChurchEventCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class ChurchEventController extends Controller      
{
    use AppliesOrgPermissionScope;

    protected string $path = '/church-events/';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): DataSetResource
    {
        $query = queryServerSide($request, ChurchEvent::query());

        if ($filter = $request->get('filter')) {
            $query->where('name', 'like', "%{$filter}%");
        }

        if ($orgId = $request->get('org_id')) {
            $query->where('org_id', $orgId);
        }

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'church-event-index');
        $churchEvents = $query->paginate($request->get('itemsPerPage'));

        return new DataSetResource($churchEvents);
    }

    /**
     * Display a calendar listing filtered by date range.
     */
    public function calendar(Request $request): JsonResponse
    {
        $query = ChurchEvent::query();

        if ($filter = $request->get('filter')) {
            $query->where('name', 'like', "%{$filter}%");
        }

        if ($orgId = $request->get('org_id')) {
            $query->where('org_id', $orgId);
        }

        if ($startDate = $request->get('start_date')) {
            $query->whereDate('event_date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->whereDate('event_date', '<=', $endDate);
        }

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'church-event-index');

        $events = $query->orderBy('event_date', 'asc')->orderBy('time_start', 'asc')->get();

        $events->each(function ($event) {
            $event->makeVisible('url_image')->append('url_image_s3');
        });

        return response()->json([
            'data' => $events,
            'total' => $events->count(),
        ]);
    }

    /**
     * Display a public listing of the resource filtered by organization.
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $orgId = $request->get('org_id');
        $decodedOrgId = null;

        if ($orgId) {
            $decoded = base64_decode($orgId, true);
            if ($decoded !== false && is_numeric($decoded)) {
                $decodedOrgId = (int) $decoded;
            }
        }

        $today = Carbon::now()->subHours(6);
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $columns = [
            'id', 'name', 'slug_name', 'location', 'description',
            'publish_date', 'event_date', 'time_start', 'url_image',
            'classification', 'org_id', 'created_by', 'created_at', 'updated_at'
        ];

        if ($request->has('search')) {
            $events = ChurchEvent::query()
                ->where('org_id', $decodedOrgId)
                ->where('name', 'like', "%{$request->get('search')}%")
                ->whereDate('publish_date', '<=', $today)
                ->whereDate('event_date', '>=', $today)
                ->orderBy('event_date', 'asc')->orderBy('time_start', 'asc')
                ->limit(15)
                ->get($columns);
        } else {
            $events =  ChurchEvent::query()
                ->where('org_id', $decodedOrgId)
                ->when($request->get('slug_name'), fn($q, $slug) => $q->where('slug_name', $slug))
                ->whereDate('publish_date', '<=', $today)
                ->when($start_date, fn($q) => $q->whereDate('event_date', '>=', $start_date))
                ->when($end_date, fn($q) => $q->whereDate('event_date', '<=', $end_date))
                ->orderBy('event_date', 'asc')->orderBy('time_start', 'asc')
                ->get($columns);
        }

        // Transform collection efficiently while ensuring custom attributes are appended
        $transformedEvents = $events->map(function ($event) {
            $event->makeVisible('url_image')->append('url_image_s3');
            return [
                'id' => $event->id,
                'name' => $event->name,
                'slug_name' => $event->slug_name,
                'location' => $event->location,
                'description' => $event->description,
                'publish_date' => $event->publish_date?->format('Y-m-d'),
                'event_date' => $event->event_date?->format('Y-m-d'),
                'time_start' => $event->time_start?->format('H:i'),
                'org_id' => $event->org_id,
                'created_by' => $event->created_by,
                'url_image_s3' => $event->url_image_s3,
                'classification' => $event->classification,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ];
    });

        return response()->json($transformedEvents);
    }

    /**
     * Display a public listing of events for the next N days.
     */
    public function publicCarousel(Request $request): JsonResponse
    {
        $orgId = $request->get('org_id');
        $decodedOrgId = null;

        if ($orgId) {
            $decoded = base64_decode($orgId, true);
            if ($decoded !== false && is_numeric($decoded)) {
                $decodedOrgId = (int) $decoded;
            }
        }

        $today = Carbon::now()->subHours(6);
        $nextDays = $request->get('nextDays', 15);
        if (!is_numeric($nextDays) || $nextDays < 0) {
            $nextDays = 15;
        } else {
            $nextDays = (int) $nextDays;
        }
        $endDate = $today->copy()->addDays($nextDays);

        $columns = [
            'id', 'name', 'slug_name', 'location', 'description',
            'publish_date', 'event_date', 'time_start', 'url_image',
            'classification', 'org_id', 'created_by', 'created_at', 'updated_at'
        ];

        $events = ChurchEvent::query()
            ->where('org_id', $decodedOrgId)
            ->whereDate('publish_date', '<=', $today)
            ->whereDate('event_date', '>=', $today)
            ->whereDate('event_date', '<=', $endDate)
            ->orderBy('event_date', 'asc')->orderBy('time_start', 'asc')
            ->get($columns);

        $transformedEvents = $events->map(function ($event) {
            $event->makeVisible('url_image')->append('url_image_s3');
            return [
                'id' => $event->id,
                'name' => $event->name,
                'slug_name' => $event->slug_name,
                'location' => $event->location,
                'description' => $event->description,
                'publish_date' => $event->publish_date?->format('Y-m-d'),
                'event_date' => $event->event_date?->format('Y-m-d'),
                'time_start' => $event->time_start?->format('H:i'),
                'org_id' => $event->org_id,
                'created_by' => $event->created_by,
                'url_image_s3' => $event->url_image_s3,
                'classification' => $event->classification,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ];
        });

        return response()->json($transformedEvents);
    }


    /**
     * Display the specified resource.
     */
    public function show(ChurchEvent $churchEvent): ChurchEvent
    {
       // return $churchEvent->load(['organization', 'creator'])
         return $churchEvent
            ->makeVisible('url_image')
            ->append('url_image_s3');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('ChurchEventController@store', $request->all());

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug_name' => 'nullable|string|unique:church_events,slug_name|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'publish_date' => 'required|date',
            'event_date' => 'nullable|date|after_or_equal:publish_date',
            'time_start' => 'nullable|date_format:H:i',
            'url_image' => 'nullable|string',
               'classification' => 'nullable|string',
            // 'classification' => 'nullable|string|in:jv3s,general,jv3s-teen,jv3s-legado',
            'org_id' => 'required|exists:organizations,id',
        ]);

        if ($request->filled('url_image') && str_starts_with($request->url_image, 'data:')) {
            $path = "ORG-{$data['org_id']}{$this->path}";
            $treatedImage = treatImage($request->url_image, 95);
            $data['url_image'] = saveS3Blob($treatedImage, $path);
        }

        if (empty($data['slug_name'])) {
            $data['slug_name'] = Str::slug($data['name']) . '-' . $data['org_id'] . '-' . Str::uuid();
            //$data['slug_name'] = Str::slug($data['name']) . '-' . $data['org_id'] . '-' . date('ymd');

            if (ChurchEvent::where('slug_name', $data['slug_name'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'slug_name' => [__('validation.unique', ['attribute' => 'slug_name'])],
                ]);
            }
        }

        $data['created_by'] = $request->user()->id;

        $event = ChurchEvent::create($data);

        return response()->json([
            'success' => __('messa.church_event_create', ['name' => $event->name]),
            'data' => $event->makeVisible('url_image')->append('url_image_s3'),
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChurchEvent $churchEvent): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug_name' => 'nullable|string|unique:church_events,slug_name,' . $churchEvent->id . '|max:255',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'publish_date' => 'sometimes|date',
            'event_date' => 'nullable|date|after_or_equal:publish_date',
            'time_start' => 'nullable|date_format:H:i',
            'url_image' => 'nullable|string',
            'classification' => 'nullable|string',
           // 'classification' => 'nullable|string|in:jv3s,general,jv3s-teen,jv3s-legado',
            'org_id' => 'sometimes|exists:organizations,id',
            'created_by' => 'sometimes|exists:users,id',
        ]);

        if ($request->filled('url_image') && str_starts_with($request->url_image, 'data:')) {
            $orgId = $data['org_id'] ?? $churchEvent->org_id;
            $path = "ORG-{$orgId}{$this->path}";
            $treatedImage = treatImage($request->url_image, 95);
            $data['url_image'] = saveS3Blob($treatedImage, $path, $churchEvent->url_image);
        }

        if (isset($data['name']) && !isset($data['slug_name'])) {
            $orgId = $data['org_id'] ?? $churchEvent->org_id;
            $data['slug_name'] = Str::slug($data['name']) . '-' . $orgId . '-' . Str::uuid();

            if (ChurchEvent::where('slug_name', $data['slug_name'])->where('id', '!=', $churchEvent->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'slug_name' => [__('validation.unique', ['attribute' => 'slug_name'])],
                ]);
            }
        }

        $churchEvent->update($data);

        return response()->json([
            'success' => __('messa.church_event_update', ['name' => $churchEvent->name]),
            'data' => $churchEvent->makeVisible('url_image')->append('url_image_s3'),
        ]);
    }

    /**
     * Copy the specified event into several new events, one per selected date.
     */
    public function copy(Request $request, ChurchEvent $churchEvent): JsonResponse
    {
        $data = $request->validate([
            'dates' => 'nullable|array|min:1',
            'dates.*' => 'date',
            'recurrence' => 'nullable|array',
            'recurrence.start_date' => 'nullable|required_with:recurrence|date',
            'recurrence.end_date' => 'nullable|required_with:recurrence|date',
            'recurrence.days_of_week' => 'nullable|required_with:recurrence|array',
            'recurrence.days_of_week.*' => 'integer|min:0|max:6',
        ]);

        $copyService = app(ChurchEventCopyService::class);
        $eventDates = $copyService->resolveDates($data, $churchEvent->event_date?->format('Y-m-d'));

        if (empty($eventDates)) {
            return response()->json([
                'created' => [],
                'skipped' => [],
            ], 201);
        }

        $createdEvents = [];
        $skippedDates = [];

        foreach (array_unique($eventDates) as $eventDate) {
            $attributes = $churchEvent->only([
                'name', 'location', 'description', 'publish_date',
                'time_start', 'url_image', 'classification', 'org_id', 'created_by',
            ]);

            $attributes['event_date'] = $eventDate;

            $duplicate = ChurchEvent::where('org_id', $attributes['org_id'])
                ->where('name', $attributes['name'])
                ->whereDate('event_date', $eventDate)
                ->exists();

            if ($duplicate) {
                $skippedDates[] = $eventDate;
                continue;
            }

            $attributes['slug_name'] = Str::slug($attributes['name']) . '-' . $attributes['org_id'] . '-' . Carbon::parse($eventDate)->format('ymd') . '-' . Str::random(4);

            $newEvent = ChurchEvent::create($attributes);
            $createdEvents[] = $newEvent->makeVisible('url_image')->append('url_image_s3');
        }

        return response()->json([
            'created' => $createdEvents,
            'skipped' => $skippedDates,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChurchEvent $churchEvent): JsonResponse
    {
        if (!empty($churchEvent->url_image)) {
            try {
              //  deleteS3($churchEvent->url_image);
            } catch (\Exception $e) {
                Log::warning("Failed to delete S3 image ({$churchEvent->url_image}): " . $e->getMessage());
            }
        }

        $churchEvent->delete();

        return response()->json(['success' => __('messa.church_event_delete')]);
    }
}
