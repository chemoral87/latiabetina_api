<?php

namespace App\Models\Church;

use App\Models\ConsoSheet;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ChurchMember extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = [
        'org_id',
        'conso_sheet_id',
        'name',
        'last_name',
        'second_last_name',
        'years_old',
        'number_of_children',
        'cellphone',
        'address',
        'marriage_status',
        'url_image',
        'status',
    ];

    protected $hidden = ['url_image'];

    protected $appends = ['url_image_s3'];

    protected function casts(): array
    {
        return [
        ];
    }

    public function getUrlImageS3Attribute() {
        $path = is_string($this->url_image) ? $this->url_image : null;
        return temporaryUrlS3($path);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function consoSheet()
    {
        return $this->belongsTo(ConsoSheet::class);
    }

    public function consolidators()
    {
        return $this->belongsToMany(User::class, 'church_member_consolidator', 'church_member_id', 'consolidator_id')->withTimestamps();
    }

    public function trackingLogs()
    {
        return $this->hasMany(ChurchMemberTrackingLog::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(ChurchMemberStatusLog::class);
    }

    public function medals()
    {
        return $this->hasMany(ChurchMemberMedal::class);
    }
}