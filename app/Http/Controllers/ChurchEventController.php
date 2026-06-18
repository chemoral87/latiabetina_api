<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\ChurchEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        $testimonies = $query->paginate($request->get('itemsPerPage'));

        return new DataSetResource($testimonies);
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

        $query = queryServerSide($request, ChurchEvent::query())
            ->where('org_id', $decodedOrgId)
            ->when($request->get('publish_date'), fn($q, $date) => $q->whereDate('publish_date', '>=', $date))
           // ->when($request->get('event_date'), fn($q, $date) => $q->whereDate('publish_date', '<=', $date))
            ->orderBy('event_date', 'asc')->orderBy('time_start', 'asc');

        $events = $query->get([
            'id', 'name', 'slug_name', 'location', 'description', 
            'publish_date', 'event_date', 'time_start', 'url_image', 
            'classification', 'org_id', 'created_by', 'created_at', 'updated_at'
        ]);

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
            'classification' => 'nullable|string|in:jv3s,general,jv3s-teen,jv3s-legado',
            'org_id' => 'required|exists:organizations,id',
        ]);

        if ($request->filled('url_image') && str_starts_with($request->url_image, 'data:')) {
            $path = "ORG-{$data['org_id']}{$this->path}";
            $data['url_image'] = saveS3Blob($request->url_image, $path);
        }

        if (empty($data['slug_name'])) {
            $data['slug_name'] = Str::slug($data['name']) . '-' . $data['org_id'] . '-' . date('ymd');

            if (ChurchEvent::where('slug_name', $data['slug_name'])->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'slug_name' => [__('validation.unique', ['attribute' => 'slug_name'])],
                ]);
            }
        }

        $data['created_by'] = $request->user()->id;

        $event = ChurchEvent::create($data);

        return response()->json($event->makeVisible('url_image')->append('url_image_s3'), 201);
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
            'classification' => 'nullable|string|in:jv3s,general,jv3s-teen,jv3s-legado',
            'org_id' => 'sometimes|exists:organizations,id',
            'created_by' => 'sometimes|exists:users,id',
        ]);

        if ($request->filled('url_image') && str_starts_with($request->url_image, 'data:')) {
            $orgId = $data['org_id'] ?? $churchEvent->org_id;
            $path = "ORG-{$orgId}{$this->path}";
            $data['url_image'] = saveS3Blob($request->url_image, $path, $churchEvent->url_image);
        }

        if (isset($data['name']) && !isset($data['slug_name'])) {
            $orgId = $data['org_id'] ?? $churchEvent->org_id;
            $data['slug_name'] = Str::slug($data['name']) . '-' . $orgId . '-' . date('ym');

            if (ChurchEvent::where('slug_name', $data['slug_name'])->where('id', '!=', $churchEvent->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'slug_name' => [__('validation.unique', ['attribute' => 'slug_name'])],
                ]);
            }
        }

        $churchEvent->update($data);

        return response()->json($churchEvent->makeVisible('url_image')->append('url_image_s3'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChurchEvent $churchEvent): JsonResponse
    {
        if (!empty($churchEvent->url_image)) {
            try {
                Storage::disk('s3')->delete($churchEvent->url_image);
            } catch (\Exception $e) {
                Log::warning("Failed to delete S3 image ({$churchEvent->url_image}): " . $e->getMessage());
            }
        }

        $churchEvent->delete();

        return response()->json(['message' => 'Church event deleted']);
    }
}