<?php
namespace App\Libraries;
use Illuminate\Support\Facades\DB;

class Helpers
{

    public static function getGuard(){
    	$returnVal="";
	    if(\Auth::guard('admin')->check())
	        $returnVal="admin";
	    elseif(\Auth::guard('api')->check())
	        $returnVal="api";

	    return $returnVal;
	}

	public static function getAdminConstants($adminId=null, $columnName=null){
		$returnVal=null;
		if(($adminId != null and $adminId != "") and ($columnName != null and $columnName != ""))
			$returnVal=config('constants.system_admin.'.$adminId.'.'.$columnName);
		
		return $returnVal;
	}

	/**
    * Checking is the id belongs to Admin Method
    * Paramenters : Id
    * Return : is admin id - true or false 
    **/
	public static function isAdminId($id=null){
		$returnVal=false;
		if($id != null and $id != ""){
			$systemAdmins=config('constants.system_admin');
	    	if(isset($systemAdmins) and !empty($systemAdmins)){
	    		if(array_key_exists($id, $systemAdmins))
	    			$returnVal=true;
	    	}	
		}
    	
		return $returnVal;
	}

	public static function getUserData($id=null, $columnName=null){
		$returnVal=null;
		if(($id != null and $id != "") and ($columnName != null and $columnName != "")){
			$adminData=\App\Admin::where('id', $id)->first();
			if($adminData === null){
				$usersData=\App\User::where('id', $id)->first();
				if($usersData === null)
					$returnVal=null;
				else
					$returnVal=$usersData->$columnName;
			}
			else
				$returnVal=config('constants.system_admin.'.$id.'.'.$columnName);

		}

		return $returnVal;
	}

	public static function getConstantsValue($arryName=null, $key=null){
		$returnVal=null;
		if(($arryName != null and !empty($arryName)) && ($key != null and $key != "")){
			$constant=config('constants.'.$arryName);
			if(isset($constant) and !empty($constant)){
				if(array_key_exists($key, $constant))
					$returnVal=$constant[$key];
			}
		}

		return $returnVal;
	}

	/**
    * getting a DB column vale by id Method
    * Paramenters : Tablename, Id column name, Id, Return column 
    * Return : column value 
    **/
	public static function getNameByIdFromDB($tableName=null, $idColumnName=null, $id=null, $returnColumn=null){
		$returnVal=null;
		if(($tableName != null and $tableName != "") and ($idColumnName != null and $idColumnName != "") and ($id != null and $id != "") and ($returnColumn != null and $returnColumn != "")){
			$data=DB::table($tableName)->where($idColumnName, $id)->value($returnColumn);
			if($data)
				$returnVal=$data;
		}

		return $returnVal;
	}

	public static function customBaseUrl(){
		$url=url();
		if (preg_match('/public/', $url) === 1) {
		    $url=preg_replace('/public/', '', $url);
		}
		return $url;
	}
}
