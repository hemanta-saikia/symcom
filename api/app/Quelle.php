<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Libraries\Helpers as CustomHelper;

class Quelle extends Model
{
    protected $table = 'quelle';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'quelle_type_id', 'quelle_schema_id', 'herkunft_id', 'code', 'titel', 'jahr', 'band', 'jahrgang', 'nummer', 'supplementheft', 'auflage', 'file_url', 'verlag_id', 'sprache', 'active', 'ip_address', 'stand', 'bearbeiter_id', 'ersteller_datum', 'ersteller_id'
    ];

    public $timestamps = false;

    /**
     * Appending creator name and editor name in the return array
     */
    protected $appends = ['ersteller', 'bearbeiter', 'quelle_schema'];

    /**
     * The autoren that belong to the quelle.
     */
    public function autoren()
    {
        return $this->belongsToMany('App\Autor', 'quelle_autor', 'quelle_id', 'autor_id', 'quelle_id', 'autor_id');
    }

    /**
     * The Herkunft that belong to the quelle.
     */
    public function herkunft()
    {
        return $this->hasOne('App\Herkunft', 'herkunft_id', 'herkunft_id');
    }

    /**
     * The Verlag that belong to the quelle.
     */
    public function Verlag()
    {
        return $this->hasOne('App\Verlage', 'verlag_id', 'verlag_id');
    }

    /**
     * The Arznei that belong to the autor.
     */
    public function arznei()
    {
        return $this->belongsToMany('App\Arznei');
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
     * Geting Quelle Schema name
     */
    public function getQuelleSchemaAttribute()
    {
        $quelleSchemaName=CustomHelper::getConstantsValue('quelle_schemas', $this->quelle_schema_id);
        return ($quelleSchemaName != "") ? $quelleSchemaName : NULL;
    }

    /**
     * Geting full file url
     */
    public function getFileUrlAttribute($value)
    {
        $returnData = null;
        if($value != "" and $value != null)
            $returnData = CustomHelper::customBaseUrl().'storage/uploads/quelle/'.$value;

        return $returnData;
    }
}
