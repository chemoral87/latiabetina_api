<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\Church\Assistance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AssistanceController extends Controller
{
    use AppliesOrgPermissionScope;

    private const VALID_SERVICE_TIMES = ['09:45', '12:00', '20:00'];

    public function index(Request $request): DataSetResource
    {
        $query = queryServerSide($request, Assistance::query());

        if ($orgId = $request->get('org_id')) {
            $query->where('org_id', $orgId);
        }
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('assistance_date', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('assistance_date', '<=', $endDate);
        }
        if ($serviceTime = $request->get('service_time')) {
            $query->where('service_time', $serviceTime);
        }

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'assistance-index');
        $assistances = $query->orderBy('assistance_date', 'desc')
            ->orderBy('service_time', 'asc')
            ->paginate($request->get('itemsPerPage'));

        return new DataSetResource($assistances);
    }

    public function show(Assistance $assistance): Assistance
    {
        return $assistance;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $assistance = Assistance::updateOrCreate(
            [
                'org_id' => $data['org_id'],
                'assistance_date' => $data['assistance_date'],
                'service_time' => $data['service_time'],
            ],
            $data
        );

        return response()->json([
            'success' => __('messa.assistance_create'),
            'data' => $assistance,
        ], 201);
    }

    public function update(Request $request, Assistance $assistance): JsonResponse
    {
        $this->ensureNotLocked($assistance);

        $data = $this->validated($request, $assistance->id);
        $data['updated_by'] = $request->user()->id;

        $assistance->update($data);

        return response()->json([
            'success' => __('messa.assistance_update'),
            'data' => $assistance,
        ]);
    }

    public function destroy(Assistance $assistance): JsonResponse
    {
        $this->ensureNotLocked($assistance);

        $assistance->delete();

        return response()->json(['success' => __('messa.assistance_delete')]);
    }

    private function ensureNotLocked(Assistance $assistance): void
    {
        $locked = Carbon::parse($assistance->assistance_date)->addWeek()->isPast();

        if ($locked) {
            abort(422, __('messa.assistance_locked'));
        }
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate(['rows' => 'required|array|min:1']);

        $userId = $request->user()->id;
        $created = $updated = 0;

        foreach ($request->rows as $row) {
            $validator = \Validator::make($row, [
                'org_id' => 'required|exists:organizations,id',
                'assistance_date' => 'required|date',
                'service_time' => ['required', 'date_format:H:i', Rule::in(self::VALID_SERVICE_TIMES)],
                'adults' => 'required|integer|min:0',
                'teens' => 'required|integer|min:0',
                'kids' => 'required|integer|min:0',
                'babies' => 'required|integer|min:0',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                abort(422, 'Row validation failed: '.$validator->errors()->first());
            }

            $data = $validator->validated();
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            $existing = Assistance::where([
                ['org_id', $data['org_id']],
                ['assistance_date', $data['assistance_date']],
                ['service_time', $data['service_time']],
            ])->exists();

            Assistance::updateOrCreate(
                [
                    'org_id' => $data['org_id'],
                    'assistance_date' => $data['assistance_date'],
                    'service_time' => $data['service_time'],
                ],
                $data
            );

            $existing ? $updated++ : $created++;
        }

        return response()->json([
            'success' => __('messa.assistance_bulk_imported', ['created' => $created, 'updated' => $updated]),
            'created' => $created,
            'updated' => $updated,
        ]);
    }

    public function chart(Request $request): JsonResponse
    {
        $query = Assistance::query();

        if ($orgId = $request->get('org_id')) {
            $query->where('org_id', $orgId);
        }
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('assistance_date', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('assistance_date', '<=', $endDate);
        }

        $query = $this->applyOrgPermissionScope($query, $request->user(), 'assistance-index');

        $rows = $query->orderBy('assistance_date', 'asc')->get([
            'assistance_date', 'service_time', 'adults', 'teens', 'kids', 'babies',
        ]);

        return response()->json(['data' => $rows]);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'org_id' => $ignoreId ? 'nullable|exists:organizations,id' : 'required|exists:organizations,id',
            'assistance_date' => 'required|date',
            'service_time' => ['required', 'date_format:H:i', Rule::in(self::VALID_SERVICE_TIMES)],
            'adults' => 'required|integer|min:0',
            'teens' => 'required|integer|min:0',
            'kids' => 'required|integer|min:0',
            'babies' => 'required|integer|min:0',
            'newcomers' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);
    }
}
