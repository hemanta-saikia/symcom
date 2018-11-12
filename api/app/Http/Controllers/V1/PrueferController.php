<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\Helpers as CustomHelper;

class PrueferController extends Controller
{

    public function __construct(\App\Pruefer $pruefer){
        $this->pruefer = $pruefer;
        $this->dateFormat=config('constants.date_format');
        $this->dateTimeFormat=config('constants.date_time_format');
    }

    /**
    * Fetching all Pruefer Method 
    * Return : all Pruefer 
    **/
    public function allPruefer(Request $request){
    	$returnArr=config('constants.return_array');
    	$dataPerPage=config('constants.data_per_page');
    	$is_paginate=config('constants.is_paginate');
    	//$input=$request->all();
    	$input=array_map('trim', $request->all());
    	try{
    		if(isset($input['is_paginate']) && $input['is_paginate'] == 0){
	            $is_paginate=$input['is_paginate'];
	        }
	        if(isset($input['data_per_page']) && $input['data_per_page']!=""){
	            $dataPerPage=$input['data_per_page'];
	        }

	        if($is_paginate == 0){
	        	$prueferData=$this->pruefer
	    					->orderBy('pruefer.ersteller_datum', 'desc')
	    					->get();
	    		$dataArray['data']=$prueferData->toArray();
	        }else{
	        	$prueferData=$this->pruefer
	    					->orderBy('pruefer.ersteller_datum', 'desc')
	    					->paginate($dataPerPage);
	    		$dataArray=$prueferData->toArray();
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
    * User add pruefer Method
    * Adding a pruefer
    * Return : added pruefer informations
    **/
    public function addPruefer(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		//$input=$request->all();
		$input=array_map('trim', $request->all());
		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;

			$validationRules=[
	            'nachname' => 'required'
	        ];
	        if( (isset($input['geburtsdatum']) && $input['geburtsdatum']!="") and (isset($input['sterbedatum']) && $input['sterbedatum']!="") ){
	        	$geburtsdatumValidationRule=[
		            'geburtsdatum' => 'date_format:"'.$this->dateFormat.'"|required|before:sterbedatum',
		            'sterbedatum' => 'date_format:"'.$this->dateFormat.'"|required'
		        ];
		        $validationRules=array_merge($validationRules, $geburtsdatumValidationRule);
	        }
	       	else if(isset($input['geburtsdatum']) && $input['geburtsdatum']!=""){
	        	$geburtsdatumValidationRule=[
		            'geburtsdatum' => 'date_format:"'.$this->dateFormat.'"|required'
		        ];
		        $validationRules=array_merge($validationRules, $geburtsdatumValidationRule);
	        }
	        else if(isset($input['sterbedatum']) && $input['sterbedatum']!=""){
	        	$sterbedatumValidationRule=[
		            'sterbedatum' => 'date_format:"'.$this->dateFormat.'"|required'
		        ];
		        $validationRules=array_merge($validationRules, $sterbedatumValidationRule);
	        }

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

		    $insertData['kuerzel']= (isset($input['kuerzel']) and $input['kuerzel'] != "") ? $input['kuerzel'] : NULL;
		    $insertData['suchname']= (isset($input['suchname']) and $input['suchname'] != "") ? $input['suchname'] : NULL;
		    $insertData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;
		    $insertData['vorname']= (isset($input['vorname']) and $input['vorname'] != "") ? $input['vorname'] : NULL;
		    $insertData['nachname']= (isset($input['nachname']) and $input['nachname'] != "") ? $input['nachname'] : NULL;
		    $insertData['geburtsdatum']= (isset($input['geburtsdatum']) and $input['geburtsdatum'] != "") ? \Carbon\Carbon::createFromFormat($this->dateFormat, $input['geburtsdatum'])->format('Y-m-d') : NULL;
		    $insertData['sterbedatum']= (isset($input['sterbedatum']) and $input['sterbedatum'] != "") ? \Carbon\Carbon::createFromFormat($this->dateFormat, $input['sterbedatum'])->format('Y-m-d') : NULL;
		    $insertData['kommentar']= (isset($input['kommentar']) and $input['kommentar'] != "") ? $input['kommentar'] : NULL;
		    $insertData['active']= (isset($input['active']) and $input['active'] != "")? $input['active'] : 1;
		    $insertData['ip_address']=$request->ip();
		    $insertData['ersteller_datum']=\Carbon\Carbon::now()->toDateTimeString();
		    $insertData['ersteller_id']=$logedInUser;

		    $prueferResult=$this->pruefer->create($insertData);
		    if($prueferResult){
		    	$insertedData=$this->pruefer->where('pruefer_id', $prueferResult->id)->first();

		    	$result['data']=$insertedData;
		    	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Pruefer created successfully";
		    }else{
		    	$returnArr['status']=5;
                $returnArr['content']="";
                $returnArr['message']="Operation failed, could not create the pruefer";
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
    * User view pruefer Method
    * view pruefer information by it's ID
    * Return : a pruefer's informations
    **/
    public function viewPruefer(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			$validationRules=[
	            'pruefer_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, pruefer_id not provided";
	            return $returnArr;
	        }

		    $prueferData=$this->pruefer->where('pruefer_id', $input['pruefer_id'])->first();
	        if($prueferData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No pruefer found with provided pruefer_id";
	        }else{

	        	$result['data']=$prueferData;
	        	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Pruefer information fetched successfully";
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
    * User update pruefer Method
    * update pruefer information by it's ID
    * Return : a pruefer's informations
    **/
    public function updatePruefer(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;
    		
			$validationRules=[
	            'pruefer_id' => 'required',
	            'nachname' => 'required'
	        ];
	        if( (isset($input['geburtsdatum']) && $input['geburtsdatum']!="") and (isset($input['sterbedatum']) && $input['sterbedatum']!="") ){
	        	$geburtsdatumValidationRule=[
		            'geburtsdatum' => 'date_format:"'.$this->dateFormat.'"|required|before:sterbedatum',
		            'sterbedatum' => 'date_format:"'.$this->dateFormat.'"|required'
		        ];
		        $validationRules=array_merge($validationRules, $geburtsdatumValidationRule);
	        }
	       	else if(isset($input['geburtsdatum']) && $input['geburtsdatum']!=""){
	        	$geburtsdatumValidationRule=[
		            'geburtsdatum' => 'date_format:"'.$this->dateFormat.'"|required'
		        ];
		        $validationRules=array_merge($validationRules, $geburtsdatumValidationRule);
	        }
	        else if(isset($input['sterbedatum']) && $input['sterbedatum']!=""){
	        	$sterbedatumValidationRule=[
		            'sterbedatum' => 'date_format:"'.$this->dateFormat.'"|required'
		        ];
		        $validationRules=array_merge($validationRules, $sterbedatumValidationRule);
	        }

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

	        $prueferData=$this->pruefer->where('pruefer_id', $input['pruefer_id'])->first();
	        if($prueferData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No pruefer found with provided pruefer_id";
	        }else{
	        	$updateData['kuerzel']= (isset($input['kuerzel']) and $input['kuerzel'] != "") ? $input['kuerzel'] : NULL;
			    $updateData['suchname']= (isset($input['suchname']) and $input['suchname'] != "") ? $input['suchname'] : NULL;
			    $updateData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;
			    $updateData['vorname']= (isset($input['vorname']) and $input['vorname'] != "") ? $input['vorname'] : NULL;
			    $updateData['nachname']= (isset($input['nachname']) and $input['nachname'] != "") ? $input['nachname'] : NULL;
			    $updateData['geburtsdatum']= (isset($input['geburtsdatum']) and $input['geburtsdatum'] != "") ? \Carbon\Carbon::createFromFormat($this->dateFormat, $input['geburtsdatum'])->format('Y-m-d') : NULL;
			    $updateData['sterbedatum']= (isset($input['sterbedatum']) and $input['sterbedatum'] != "") ? \Carbon\Carbon::createFromFormat($this->dateFormat, $input['sterbedatum'])->format('Y-m-d') : NULL;
			    $updateData['kommentar']= (isset($input['kommentar']) and $input['kommentar'] != "") ? $input['kommentar'] : NULL;
			    $updateData['active']= (isset($input['active']) and $input['active'] != "")? $input['active'] : 1;
			    $updateData['ip_address']=$request->ip();
			    $updateData['stand']=\Carbon\Carbon::now()->toDateTimeString();
			    $updateData['bearbeiter_id']=$logedInUser;

			    $updateResult=$this->pruefer->where('pruefer_id', $input['pruefer_id'])->update($updateData);
			    if($updateResult){
			    	$autorData=$this->pruefer->where('pruefer_id', $input['pruefer_id'])->first();

		    		$result['data']=$autorData;
		        	$returnArr['status']=2;
	                $returnArr['content']=$result;
	                $returnArr['message']="Pruefer information updated successfully";	
			    }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not update the pruefer";
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
    * User delete pruefer Method
    * delete a pruefer by it's ID or delete multiple pruefer with id array
    * Return : nothing(blank)
    **/
    public function deletePruefer(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$validationRules=[
	            'pruefer_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, pruefer_id not provided";
	            return $returnArr;
	        }

	        if (is_array($input['pruefer_id'])) 
    		{
    			$resultData=$this->pruefer->whereIn('pruefer_id', $input['pruefer_id'])->delete();
	    		if($resultData){
		        	$returnArr['status']=2;
			        $returnArr['content']="";
			        $returnArr['message']="pruefer(en) deleted successfully";
		        }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not delete the pruefer(en). Please check the provided pruefer_id(s)";
		        }
    		}else{
    			$prueferData=$this->pruefer->where('pruefer_id', $input['pruefer_id'])->first();
		        if($prueferData === null){
		        	$returnArr['status']=4;
			        $returnArr['content']="";
			        $returnArr['message']="No pruefer found with provided pruefer_id";
		        }else{
		        	$resultData=$this->pruefer->where('pruefer_id', $input['pruefer_id'])->delete();
			        if($resultData){
			        	$returnArr['status']=2;
				        $returnArr['content']="";
				        $returnArr['message']="Pruefer deleted successfully";
			        }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not delet the pruefer";
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
	
}
