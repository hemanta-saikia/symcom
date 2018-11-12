<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Libraries\Helpers as CustomHelper;

class Verlage extends Model
{
    protected $table = 'verlag';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'titel', 'strasse', 'plz', 'ort', 'land_id', 'telefon', 'fax', 'email', 'homepage', 'bemerkungen', 'active', 'ip_address', 'stand', 'bearbeiter_id', 'ersteller_datum', 'ersteller_id'
    ];

    public $timestamps = false;

    /**
     * Appending creator name and editor name in the return array
     */
    protected $appends = ['ersteller', 'bearbeiter', 'land'];

    /**
     * The Quelle that belong to the verlag.
     */
    public function quelle()
    {
        return $this->belongsTo('App\Quelle');
    }

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
     * Geting Land(Country) name
     */
    public function getLandAttribute()
    {
        $land=config('constants.land');
        if(array_key_exists($this->land_id, $land))
            $landName=$land[$this->land_id];
        else
            $landName=NULL;
        
        return $landName;
    }

}
