<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Libraries\Helpers as CustomHelper;

class Pruefer extends Model
{
    protected $table = 'pruefer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kuerzel', 'suchname', 'titel', 'vorname', 'nachname', 'geburtsdatum', 'sterbedatum', 'kommentar', 'active', 'ip_address', 'stand', 'bearbeiter_id', 'ersteller_datum', 'ersteller_id'
    ];

    public $timestamps = false;

    /**
     * Appending creator name and editor name in the return array
     */
    protected $appends = ['ersteller', 'bearbeiter'];

    /**
     * Geting Created at date time in project format
     */
    public function getErstellerDatumAttribute($value)
    {
        $datetimeFormat=config('constants.date_time_format');
        return ($value != "" and $value != NULL and $value != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($value)->format($datetimeFormat) : NULL;
    }

    /**
     * Geting Updated at date time in project format
     */
    public function getStandAttribute($value)
    {
        $datetimeFormat=config('constants.date_time_format');
        return ($value != "" and $value != NULL and $value != '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($value)->format($datetimeFormat) : NULL;
    }

    /**
     * Geting Creator name
     */
    public function getErstellerAttribute()
    {
        $creatorName=CustomHelper::getUserData($this->ersteller_id, 'full_name');
        return ($creatorName != "") ? $creatorName : NULL;
    }

    /**
     * Geting Editor name
     */
    public function getBearbeiterAttribute()
    {
        $editorName=CustomHelper::getUserData($this->bearbeiter_id, 'full_name');
        return ($editorName != "") ? $editorName : NULL;
    }

    /**
     * Geting Date of birth date in project format
     */
    public function getGeburtsdatumAttribute($value)
    {
        $dateFormat=config('constants.date_format');
        return ($value != "" and $value != NULL and $value != '0000-00-00') ? \Carbon\Carbon::parse($value)->format($dateFormat) : NULL;
    }

    /**
     * Geting Date of death date in project format
     */
    public function getSterbedatumAttribute($value)
    {
        $dateFormat=config('constants.date_format');
        return ($value != "" and $value != NULL and $value != '0000-00-00') ? \Carbon\Carbon::parse($value)->format($dateFormat) : NULL;
    }
}
