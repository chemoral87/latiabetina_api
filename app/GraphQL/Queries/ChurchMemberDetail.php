<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Concerns\RequiresOrgPermission;
use App\Models\Church\ChurchMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Church-member detail page in one query.
 *
 * Mirrors `ChurchMemberController@show` plus the three follow-up reads the page
 * used to issue separately (`tracking-logs`, `consolidator-logs`, `medals`).
 */
final class ChurchMemberDetail
{
    use RequiresOrgPermission;

    private const TRACKING_LOGS_PER_PAGE = 10;

    /**
     * @param  array{id: string}  $args
     */
    public function __invoke(mixed $root, array $args): ?array
    {
        $user = $this->authenticatedUser();
        // Same gate as `routes/api.php`: GET /church-member/{id}.
        $this->orgIdsFor($user, 'church-member-index');

        $member = $this->scopedQuery($user)
            ->with([
                'creator:id,name,last_name',
                'consolidators:id,name,last_name,second_last_name,email',
            ])
            ->find($args['id']);

        if (! $member instanceof ChurchMember) {
            return null;
        }

        return [
            'member' => $this->memberPayload($member),
            'trackingLogs' => $this->trackingLogsPayload($member),
            'consolidatorLogs' => $this->consolidatorLogsPayload($member),
            'medals' => $this->medalsPayload($member),
        ];
    }

    /**
     * Same scoping as `ChurchMemberController@findMemberInScope`.
     * ponytail: kept in sync by hand; extract a shared trait if it grows.
     */
    private function scopedQuery(User $user): Builder
    {
        $query = ChurchMember::query();

        if ($this->hasChurchMemberAll($user)) {
            $orgIds = $user->getOrgsByPermission('church-member-all');

            return $orgIds === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('org_id', $orgIds);
        }

        $orgIds = $user->getOrgsByPermission('conso-sheet-index');
        $query = $orgIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('org_id', $orgIds);

        return $query->where(function (Builder $q) use ($user) {
            $q->where('created_by', $user->id)
                ->orWhereHas('consoSheet', fn (Builder $sheet) => $sheet->where('created_by', $user->id))
                ->orWhereHas('consolidators', fn (Builder $consolidators) => $consolidators->where('users.id', $user->id));
        });
    }

    private function hasChurchMemberAll(User $user): bool
    {
        try {
            if ($user->hasPermissionTo('church-member-all')) {
                return true;
            }
        } catch (\Throwable) {
            // fall through to the org map
        }

        return $user->getOrgsByPermission('church-member-all') !== [];
    }

    private function memberPayload(ChurchMember $member): array
    {
        return [
            'id' => $member->id,
            'org_id' => $member->org_id,
            'conso_sheet_id' => $member->conso_sheet_id,
            'name' => $member->name,
            'last_name' => $member->last_name,
            'second_last_name' => $member->second_last_name,
            'years_old' => $member->years_old,
            'number_of_children' => $member->number_of_children,
            'cellphone' => $member->cellphone,
            'address' => $member->address,
            'marriage_status' => $member->marriage_status,
            'status' => $member->status,
            'created_by' => $member->created_by,
            'url_image_s3' => $member->url_image_s3,
            'created_at' => $member->created_at?->toIso8601String(),
            'updated_at' => $member->updated_at?->toIso8601String(),
            'creator' => $this->userPayload($member->creator),
            'consolidators' => $member->consolidators
                ->map(fn (User $consolidator) => $this->userPayload($consolidator))
                ->all(),
        ];
    }

    /**
     * Same payload as `ChurchMemberTrackingLogController@trackingLogs` first page.
     */
    private function trackingLogsPayload(ChurchMember $member): array
    {
        $paginator = $member->trackingLogs()
            ->with(['creator', 'churchMember:id,name,last_name,cellphone'])
            ->orderByDesc('contact_datetime')
            ->orderByDesc('id')
            ->paginate(self::TRACKING_LOGS_PER_PAGE, ['*'], 'page', 1);

        return [
            'data' => collect($paginator->items())
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'church_member_id' => $log->church_member_id,
                    'contact_datetime' => $log->contact_datetime?->toIso8601String(),
                    'medium' => $log->medium,
                    'classification' => $log->classification,
                    'description' => $log->description,
                    'created_by' => $log->created_by,
                    'created_at' => $log->created_at?->toIso8601String(),
                    'updated_at' => $log->updated_at?->toIso8601String(),
                    'creator' => $this->userPayload($log->creator),
                    'church_member' => $this->trackingLogMemberPayload($log->churchMember),
                ])
                ->all(),
            'total' => $paginator->total(),
            'itemsPerPage' => $paginator->perPage(),
        ];
    }

    /**
     * Same payload as `ChurchMemberController@consolidatorLogs`.
     */
    private function consolidatorLogsPayload(ChurchMember $member): array
    {
        return $member->consolidatorLogs()
            ->with(['consolidator:id,name,last_name,email', 'changer:id,name,last_name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'church_member_id' => $log->church_member_id,
                'consolidator_id' => $log->consolidator_id,
                'action' => $log->action,
                'changed_by' => $log->changed_by,
                'created_at' => $log->created_at?->toIso8601String(),
                'updated_at' => $log->updated_at?->toIso8601String(),
                'consolidator' => $this->userPayload($log->consolidator),
                'changer' => $this->userPayload($log->changer),
            ])
            ->all();
    }

    /**
     * Same payload as `ChurchMemberMedalController@index`.
     */
    private function medalsPayload(ChurchMember $member): array
    {
        return $member->medals()
            ->with('creator:id,name,last_name')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($medal) => [
                'id' => $medal->id,
                'church_member_id' => $medal->church_member_id,
                'medal' => $medal->medal,
                'description' => $medal->description,
                'created_by' => $medal->created_by,
                'created_at' => $medal->created_at?->toIso8601String(),
                'updated_at' => $medal->updated_at?->toIso8601String(),
                'creator' => $this->userPayload($medal->creator),
            ])
            ->all();
    }

    private function trackingLogMemberPayload(?Model $member): ?array
    {
        if (! $member instanceof ChurchMember) {
            return null;
        }

        return [
            'id' => $member->id,
            'name' => $member->name,
            'last_name' => $member->last_name,
            'cellphone' => $member->cellphone,
        ];
    }

    private function userPayload(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'last_name' => $user->last_name,
            'second_last_name' => $user->second_last_name,
            'email' => $user->email,
        ];
    }
}
