<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\Helpers as CustomHelper;

class AutorController extends Controller
{

    public function __construct(\App\User $user, \App\Admin $admin, \App\Autor $autor){
        $this->user = $user;
        $this->admin = $admin;
        $this->autor = $autor;
        $this->dateFormat=config('constants.date_format');
        $this->dateTimeFormat=config('constants.date_time_format');
    }

    // Just a testing function
    public function getTokenUser(Request $request){
        //$current_user = \Auth::user();
        $current_user = \Auth::guard('admin')->user();
        return $current_user;
    }

    /**
    * Fetching all autor Method 
    * Return : all autor 
    **/
    public function allAutor(Request $request){
    	$returnArr=config('constants.return_array');
    	$dataPerPage=config('constants.data_per_page');
    	$is_paginate=config('constants.is_paginate');
    	$input=$request->all();

    	try{
    		if(isset($input['is_paginate']) && $input['is_paginate'] == 0){
	            $is_paginate=$input['is_paginate'];
	        }
	        if(isset($input['data_per_page']) && $input['data_per_page']!=""){
	            $dataPerPage=$input['data_per_page'];
	        }

	        if($is_paginate == 0){
	        	$autorData=$this->autor
	    					->orderBy('autor.ersteller_datum', 'desc')
	    					->get();
	    		$dataArray['data']=$autorData->toArray();
	        }else{
	        	$autorData=$this->autor
	    					->orderBy('autor.ersteller_datum', 'desc')
	    					->paginate($dataPerPage);
	    		$dataArray=$autorData->toArray();
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
    * User add autor Method
    * Adding a autor
    * Return : added autor informations
    **/
    public function addAutor(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
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

		    $insertData['code']= (isset($input['code']) and $input['code'] != "") ? $input['code'] : NULL;
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

		    $autorResult=$this->autor->create($insertData);
		    if($autorResult){
		    	$insertedData=$this->autor->where('autor_id', $autorResult->id)->first();

		    	$result['data']=$insertedData;
		    	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Autor created successfully";
		    }else{
		    	$returnArr['status']=5;
                $returnArr['content']="";
                $returnArr['message']="Operation failed, could not create the autor";
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
    * User view autor Method
    * view autor information by it's ID
    * Return : a autor's informations
    **/
    public function viewAutor(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			$validationRules=[
	            'autor_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, autor_id not provided";
	            return $returnArr;
	        }

		    $autorData=$this->autor->where('autor_id', $input['autor_id'])->first();
	        if($autorData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No autor found with provided autor_id";
	        }else{

	        	$result['data']=$autorData;
	        	$result['titles']=config('constants.titles');
	        	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Autor information fetched successfully";
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
    * User update autor Method
    * update autor information by it's ID
    * Return : a autor's informations
    **/
    public function updateAutor(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;
    		
			$validationRules=[
	            'autor_id' => 'required',
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

	        $autorData=$this->autor->where('autor_id', $input['autor_id'])->first();
	        if($autorData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No autor found with provided autor_id";
	        }else{
	        	$updateData['code']= (isset($input['code']) and $input['code'] != "") ? $input['code'] : NULL;
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

			    $updateResult=$this->autor->where('autor_id', $input['autor_id'])->update($updateData);
			    if($updateResult){
			    	$autorData=$this->autor->where('autor_id', $input['autor_id'])->first();

		    		$result['data']=$autorData;
		        	$returnArr['status']=2;
	                $returnArr['content']=$result;
	                $returnArr['message']="Autor information updated successfully";	
			    }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not update the autor";
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
    * User delete autor Method
    * delete a autor by it's ID or delete multiple autor with id array
    * Return : nothing(blank)
    **/
    public function deleteAutor(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$validationRules=[
	            'autor_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, autor_id not provided";
	            return $returnArr;
	        }

	        if (is_array($input['autor_id'])) 
    		{
    			$resultData=$this->autor->whereIn('autor_id', $input['autor_id'])->delete();
	    		if($resultData){
		        	$returnArr['status']=2;
			        $returnArr['content']="";
			        $returnArr['message']="Autor(en) deleted successfully";
		        }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not delete the autor(en). Please check the provided autor_id(s)";
		        }
    		}else{
    			$autorData=$this->autor->where('autor_id', $input['autor_id'])->first();
		        if($autorData === null){
		        	$returnArr['status']=4;
			        $returnArr['content']="";
			        $returnArr['message']="No autor found with provided autor_id";
		        }else{
		        	$resultData=$this->autor->where('autor_id', $input['autor_id'])->delete();
			        if($resultData){
			        	$returnArr['status']=2;
				        $returnArr['content']="";
				        $returnArr['message']="Autor deleted successfully";
			        }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not delet the autor";
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
    * Get Titles Method
    * fetching all predefined titels
    * Return : all predefine titels
    **/
    public function getPreDefinedTitles(){
    	$returnArr=config('constants.return_array');
		try{
			$result['data']=config('constants.titles');
        	$returnArr['status']=2;
            $returnArr['content']=$result;
            $returnArr['message']="titels found successfully";
		}
    	catch(\Exception $e){
        	$returnArr['status']=6;
	        $returnArr['content']=$e;
	        $returnArr['message']="Something went wrong";
        }

        return $returnArr; 
    }
}
