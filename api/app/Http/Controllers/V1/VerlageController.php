<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\Helpers as CustomHelper;

class VerlageController extends Controller
{

    public function __construct(\App\User $user, \App\Admin $admin, \App\Verlage $verlage){
        $this->user = $user;
        $this->admin = $admin;
        $this->verlage = $verlage;
        $this->dateFormat=config('constants.date_format');
        $this->dateTimeFormat=config('constants.date_time_format');
    }

    /**
    * Fetching all Verlage Method 
    * Return : all Verlage 
    **/
    public function allVerlage(Request $request){
    	$returnArr=config('constants.return_array');
    	$dataPerPage=config('constants.data_per_page');
    	$is_paginate=config('constants.is_paginate');
    	$land=config('constants.land');
    	$input=$request->all();

    	try{
    		if(isset($input['is_paginate']) && $input['is_paginate'] == 0){
	            $is_paginate=$input['is_paginate'];
	        }
    		if(isset($input['data_per_page']) && $input['data_per_page']!=""){
	            $dataPerPage=$input['data_per_page'];
	        }

	        if($is_paginate == 0){
	        	$verlageData=$this->verlage
	    					->orderBy('verlag.ersteller_datum', 'desc')
	    					->get();
	    		$dataArray['data']=$verlageData->toArray();
	        }else{
	        	$verlageData=$this->verlage
	    					->orderBy('verlag.ersteller_datum', 'desc')
	    					->paginate($dataPerPage);
	    		$dataArray=$verlageData->toArray();
	        }
	        
	    	if(isset($dataArray['data']) && !empty($dataArray['data'])){

	    		if($is_paginate == 0)
	    			$response=$dataArray;
	    		else{
	    			$response=[
		                'data' => $dataArray['data'],
		                'total' => $dataArray['total'],
		                'limit' => $dataArray['per_page'],
		                'pagination' => [
		                    'next_page' => $dataArray['next_page_url'],
		                    'prev_page' => $dataArray['prev_page_url'],
		                    'current_page' => $dataArray['current_page'],
		                    'first_page' => $dataArray['first_page_url'],
		                    'last_page' => $dataArray['last_page_url']
		                ]
		            ];
	    		}

	            $returnArr['status']=2;
	            $returnArr['content']=$response;
	            $returnArr['message']="Data fetched successfully";
	        }else{
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No data found";
	        }
    	}
        catch(\Exception $e){
        	$returnArr['status']=6;
	        $returnArr['content']=$e;
	        $returnArr['message']="Something went wrong";
        }

        return $returnArr; 
    }


    /**
    * adding Verlage Method
    * Adding a Verlage
    * Return : added Verlage informations
    **/
    public function addVerlage(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;

			$validationRules=[
	            'titel' => 'required'
	        ];
	        if(isset($input['email']) && $input['email']!=""){
	        	$emailValidationRule=[
		            'email' => 'email'
		        ];
		        $validationRules=array_merge($validationRules, $emailValidationRule);
	        }

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

		    $insertData['code']= (isset($input['code']) && $input['code'] != "") ? $input['code'] : NULL;
		    $insertData['titel']= (isset($input['titel']) && $input['titel'] != "") ? $input['titel'] : NULL;
		    $insertData['strasse']= (isset($input['strasse']) && $input['strasse'] != "") ? $input['strasse'] : NULL;
		    $insertData['plz']= (isset($input['plz']) && $input['plz'] != "") ? $input['plz'] : NULL;
		    $insertData['ort']= (isset($input['ort']) && $input['ort'] !="") ? $input['ort'] : NULL;
		    $insertData['land_id']= (isset($input['land_id']) && $input['land_id'] !="") ? $input['land_id'] : NULL;
		    $insertData['telefon']= (isset($input['telefon']) && $input['telefon'] !="") ? $input['telefon'] : NULL;
		    $insertData['fax']= (isset($input['fax']) && $input['fax'] != "") ? $input['fax'] : NULL;
		    $insertData['email']= (isset($input['email']) && $input['email'] != "") ? $input['email'] : NULL;
		    $insertData['homepage']= (isset($input['homepage']) && $input['homepage'] != "") ? $input['homepage'] : NULL;
		    $insertData['bemerkungen']= (isset($input['bemerkungen']) && $input['bemerkungen'] != "") ? $input['bemerkungen'] : NULL;
		    $insertData['active']= (isset($input['active']) && $input['active'] !="") ? $input['active'] : 1;
		    $insertData['ip_address']=$request->ip();
		    $insertData['ersteller_datum']=\Carbon\Carbon::now()->toDateTimeString();
		    $insertData['ersteller_id']=$logedInUser;

		    $verlageResult=$this->verlage->create($insertData);
		    if($verlageResult){
		    	$insertedData=$this->verlage->where('verlag_id', $verlageResult->id)->first();

		    	$result['data']=$insertedData;
		    	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Verlage created successfully";
		    }else{
		    	$returnArr['status']=5;
                $returnArr['content']="";
                $returnArr['message']="Operation failed, could not create the verlage";
		    }
		    DB::commit();
	    }
        catch(\Exception $e){
        	DB::rollback();
        	$returnArr['status']=6;
	        $returnArr['content']=$e;
	        $returnArr['message']="Something went wrong";
        }

        return $returnArr; 
	}

	/**
    * view Verlage Method
    * view Verlage information by it's ID
    * Return : a Verlage's informations
    **/
    public function viewVerlage(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			$validationRules=[
	            'verlag_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, verlag_id not provided";
	            return $returnArr;
	        }

		    $verlageData=$this->verlage->where('verlag_id', $input['verlag_id'])->first();
	        if($verlageData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No verlage found with provided verlag_id";
	        }else{
	        	$result['data']=$verlageData;
	        	$result['Länder']=config('constants.land');
	        	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Verlage information fetched successfully";
	        }
	    }
        catch(\Exception $e){
        	$returnArr['status']=6;
	        $returnArr['content']=$e;
	        $returnArr['message']="Something went wrong";
        }

        return $returnArr; 
	}


	/**
    * User update Verlage Method
    * update Verlage information by it's ID
    * Return : updated Verlage's informations
    **/
    public function updateVerlage(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;
    		
			$validationRules=[
	            'verlag_id' => 'required',
	            'titel' => 'required'
	        ];
	        if(isset($input['email']) && $input['email']!=""){
	        	$emailValidationRule=[
		            'email' => 'email'
		        ];
		        $validationRules=array_merge($validationRules, $emailValidationRule);
	        }

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

	        $verlageData=$this->verlage->where('verlag_id', $input['verlag_id'])->first();
	        if($verlageData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No verlage found with provided verlag_id";
	        }else{
	        	$updateData['code']= (isset($input['code']) && $input['code'] != "") ? $input['code'] : NULL;
			    $updateData['titel']= (isset($input['titel']) && $input['titel'] != "") ? $input['titel'] : NULL;
			    $updateData['strasse']= (isset($input['strasse']) && $input['strasse'] != "") ? $input['strasse'] : NULL;
			    $updateData['plz']= (isset($input['plz']) && $input['plz'] != "") ? $input['plz'] : NULL;
			    $updateData['ort']= (isset($input['ort']) && $input['ort'] !="") ? $input['ort'] : NULL;
			    $updateData['land_id']= (isset($input['land_id']) && $input['land_id'] !="") ? $input['land_id'] : NULL;
			    $updateData['telefon']= (isset($input['telefon']) && $input['telefon'] !="") ? $input['telefon'] : NULL;
			    $updateData['fax']= (isset($input['fax']) && $input['fax'] != "") ? $input['fax'] : NULL;
			    $updateData['email']= (isset($input['email']) && $input['email'] != "") ? $input['email'] : NULL;
			    $updateData['homepage']= (isset($input['homepage']) && $input['homepage'] != "") ? $input['homepage'] : NULL;
			    $updateData['bemerkungen']= (isset($input['bemerkungen']) && $input['bemerkungen'] != "") ? $input['bemerkungen'] : NULL;
			    $updateData['active']= (isset($input['active']) && $input['active'] !="") ? $input['active'] : 1;
			    $updateData['ip_address']=$request->ip();
			    $updateData['stand']=\Carbon\Carbon::now()->toDateTimeString();
			    $updateData['bearbeiter_id']=$logedInUser;

			    $updateResult=$this->verlage->where('verlag_id', $input['verlag_id'])->update($updateData);
			    if($updateResult){
			    	$verlageUpdatedData=$this->verlage->where('verlag_id', $input['verlag_id'])->first();

		    		$result['data']=$verlageUpdatedData;
		        	$returnArr['status']=2;
	                $returnArr['content']=$result;
	                $returnArr['message']="Verlage information updated successfully";	
			    }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not update the Verlage";
			    }
	        }
	        DB::commit();
	    }
        catch(\Exception $e){
        	DB::rollback();
        	$returnArr['status']=6;
	        $returnArr['content']=$e;
	        $returnArr['message']="Something went wrong";
        }

        return $returnArr; 
	}


	/**
    * User delete Verlage Method
    * delete a Verlage by it's ID
    * Return : nothing(blank)
    **/
    public function deleteVerlage(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$validationRules=[
	            'verlag_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, verlag_id not provided";
	            return $returnArr;
	        }

	        if (is_array($input['verlag_id'])) 
    		{
    			$resultData=$this->verlage->whereIn('verlag_id', $input['verlag_id'])->delete();
	    		if($resultData){
		        	$returnArr['status']=2;
			        $returnArr['content']="";
			        $returnArr['message']="Verlage(en) deleted successfully";
		        }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not delete the verlage(en). Please check the provided verlag_id(s)";
		        }
    		}else{
    			$verlageData=$this->verlage->where('verlag_id', $input['verlag_id'])->first();
		        if($verlageData === null){
		        	$returnArr['status']=4;
			        $returnArr['content']="";
			        $returnArr['message']="No verlage found with provided verlag_id";
		        }else{
		        	$resultData=$this->verlage->where('verlag_id', $input['verlag_id'])->delete();
			        if($resultData){
			        	$returnArr['status']=2;
				        $returnArr['content']="";
				        $returnArr['message']="Verlage deleted successfully";
			        }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not delete the verlage";
			        }
		        }
    		}
	        DB::commit();
	    }
        catch(\Exception $e){
        	DB::rollback();
        	$returnArr['status']=6;
	        $returnArr['content']=$e;
	        $returnArr['message']="Something went wrong";
        }

        return $returnArr; 
	}

	/**
    * Get land Method
    * fetching all predefined land
    * Return : all predefine land
    **/
    public function getPreDefinedLand(){
    	$returnArr=config('constants.return_array');
		try{
			$result['data']=config('constants.land');
        	$returnArr['status']=2;
            $returnArr['content']=$result;
            $returnArr['message']="land found successfully";
		}
    	catch(\Exception $e){
        	$returnArr['status']=6;
	        $returnArr['content']=$e;
	        $returnArr['message']="Something went wrong";
        }

        return $returnArr; 
    }
}
