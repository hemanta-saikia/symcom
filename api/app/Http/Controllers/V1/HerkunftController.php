<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\Helpers as CustomHelper;

class HerkunftController extends Controller
{

    public function __construct(\App\User $user, \App\Admin $admin, \App\Herkunft $herkunft){
        $this->user = $user;
        $this->admin = $admin;
        $this->herkunft = $herkunft;
        $this->dateFormat=config('constants.date_format');
        $this->dateTimeFormat=config('constants.date_time_format');
    }

    /**
    * Fetching all Herkunft Method 
    * Return : all Herkunft 
    **/
    public function allHerkunft(Request $request){
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
	        	$herkunftData=$this->herkunft
	    					->orderBy('herkunft.ersteller_datum', 'desc')
	    					->get();
	    		$dataArray['data']=$herkunftData->toArray();
	        }else{
	        	$herkunftData=$this->herkunft
	    					->orderBy('herkunft.ersteller_datum', 'desc')
	    					->paginate($dataPerPage);
	    		$dataArray=$herkunftData->toArray();
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
    * adding Herkunft Method
    * Adding a Herkunft
    * Return : added Herkunft informations
    **/
    public function addHerkunft(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;

			$validationRules=[
	            'code' => 'required',
	            'titel' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

		    $insertData['code']= (isset($input['code']) and $input['code'] != "") ? $input['code'] : NULL;
		    $insertData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;
		    $insertData['active']= (isset($input['active']) and $input['active'] != "") ? $input['active'] : 1;
		    $insertData['ip_address']=$request->ip();
		    $insertData['ersteller_datum']=\Carbon\Carbon::now()->toDateTimeString();
		    $insertData['ersteller_id']=$logedInUser;

		    $herkunftResult=$this->herkunft->create($insertData);
		    if($herkunftResult){
		    	$insertedData=$this->herkunft->where('herkunft_id', $herkunftResult->id)->first();

		    	$result['data']=$insertedData;
		    	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Herkunft created successfully";
		    }else{
		    	$returnArr['status']=5;
                $returnArr['content']="";
                $returnArr['message']="Operation failed, could not create the herkunft";
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
    * view Herkunft Method
    * view Herkunft information by it's ID
    * Return : a Herkunft's informations
    **/
    public function viewHerkunft(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			$validationRules=[
	            'herkunft_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, herkunft_id not provided";
	            return $returnArr;
	        }

		    $herkunftData=$this->herkunft->where('herkunft_id', $input['herkunft_id'])->first();
	        if($herkunftData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No herkunft found with provided herkunft_id";
	        }else{
	        	$result['data']=$herkunftData;
	        	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Herkunft information fetched successfully";
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
    * User update Herkunft Method
    * update Herkunft information by it's ID
    * Return : updated Herkunft's informations
    **/
    public function updateHerkunft(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;
    		
			$validationRules=[
	            'herkunft_id' => 'required',
	            'code' => 'required',
	            'titel' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

	        $herkunftData=$this->herkunft->where('herkunft_id', $input['herkunft_id'])->first();
	        if($herkunftData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No herkunft found with provided herkunft_id";
	        }else{
	        	$updateData['code']= (isset($input['code']) and $input['code'] != "") ? $input['code'] : NULL;
		    	$updateData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;
		    	$updateData['active']= (isset($input['active']) and $input['active'] != "") ? $input['active'] : 1;
		    	$updateData['ip_address']=$request->ip();
			    $updateData['stand']=\Carbon\Carbon::now()->toDateTimeString();
			    $updateData['bearbeiter_id']=$logedInUser;

			    $updateResult=$this->herkunft->where('herkunft_id', $input['herkunft_id'])->update($updateData);
			    if($updateResult){
			    	$fetchData=$this->herkunft->where('herkunft_id', $input['herkunft_id'])->first();
		        	
		        	$returnArr['status']=2;
	                $returnArr['content']=$fetchData;
	                $returnArr['message']="Herkunft information updated successfully";	
			    }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not update the herkunft";
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
    * User delete Herkunft Method
    * delete a Herkunft by it's ID
    * Return : nothing(blank)
    **/
    public function deleteHerkunft(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		try{
			DB::beginTransaction();
			$validationRules=[
	            'herkunft_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, herkunft_id not provided";
	            return $returnArr;
	        }

	        if (is_array($input['herkunft_id'])) 
    		{
    			$resultData=$this->herkunft->whereIn('herkunft_id', $input['herkunft_id'])->delete();
	    		if($resultData){
		        	$returnArr['status']=2;
			        $returnArr['content']="";
			        $returnArr['message']="Herkunft(en) deleted successfully";
		        }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not delete the herkunft(en). Please check the provided herkunft_id(s)";
		        }
    		}
    		else{
    			$herkunftData=$this->herkunft->where('herkunft_id', $input['herkunft_id'])->first();
		        if($herkunftData === null){
		        	$returnArr['status']=4;
			        $returnArr['content']="";
			        $returnArr['message']="No herkunft found with provided herkunft_id";
		        }else{
		        	$resultData=$this->herkunft->where('herkunft_id', $input['herkunft_id'])->delete();
			        if($resultData){
			        	$returnArr['status']=2;
				        $returnArr['content']="";
				        $returnArr['message']="Herkunft deleted successfully";
			        }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not delete the herkunft";
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
