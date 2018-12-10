<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\Helpers as CustomHelper;

class ReferenceController extends Controller
{

    public function __construct(\App\Reference $reference){
        $this->reference = $reference;
        $this->dateFormat=config('constants.date_format');
        $this->dateTimeFormat=config('constants.date_time_format');
    }

    /**
    * Fetching all Reference Method 
    * Return : all Reference 
    **/
    public function allReference(Request $request){
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
	        	$referenceData=$this->reference
	    					->orderBy('reference.ersteller_datum', 'desc')
	    					->get();
	    		$dataArray['data']=$referenceData->toArray();
	        }else{
	        	$referenceData=$this->reference
	    					->orderBy('reference.ersteller_datum', 'desc')
	    					->paginate($dataPerPage);
	    		$dataArray=$referenceData->toArray();
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
    * adding Reference Method
    * Adding a Reference
    * Return : added Reference informations
    **/
    public function addReference(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		//$input=array_map('trim', $request->all());

		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;
    		$isReadyToInsert=0;

			$validationRules=[
	            'full_reference' => 'required'
	        ];

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

	        $fullReference = trim($input['full_reference']);
        	$fullReferenceInArray = explode(",", $fullReference);
        	if(count($fullReferenceInArray) >= 2){
        		$referenceAutor = trim($fullReferenceInArray[0]);
        		array_shift($fullReferenceInArray);
        		$referenceTxt = implode(",", $fullReferenceInArray);

        		$insertData['full_reference']= $fullReference;
	        	$insertData['autor']= (isset($referenceAutor) and $referenceAutor != "") ? $referenceAutor : NULL;
	        	$insertData['reference']= (isset($referenceTxt) and $referenceTxt != "") ? trim($referenceTxt) : NULL;
	        	$insertData['kommentar']= (isset($input['kommentar']) and $input['kommentar'] != "") ? $input['kommentar'] : NULL;
			    $insertData['unklarheiten']= (isset($input['unklarheiten']) and $input['unklarheiten'] != "")? $input['unklarheiten'] : NULL;
			    $insertData['active']= (isset($input['active']) and $input['active'] !="" ) ? $input['active'] : 1;
			    $insertData['ip_address']=$request->ip();
			    $insertData['ersteller_datum']=\Carbon\Carbon::now()->toDateTimeString();
			    $insertData['ersteller_id']=$logedInUser;

			    $referenceResult=$this->reference->create($insertData);
			    if($referenceResult){
		    		$insertedData=$this->reference->where('reference_id', $referenceResult->id)->first();
					$result['data']=$insertedData;
			    	$returnArr['status']=2;
	                $returnArr['content']=$result;
	                $returnArr['message']="Reference created successfully";	
			    }
			    else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not create the reference";
			    }
			    DB::commit();
        	}else{
        		$validationMsg['full_reference']="Provided full reference string does not match the required format. Full reference sample: Matthioli, Comment. in Diosc. lib. IV. Cap. 73.";
        		$returnArr['status']=3;
	            $returnArr['content']=$validationMsg;
	            $returnArr['message']="Validation failed";
        	} 
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
    * view Reference Method
    * view Reference information by it's ID
    * Return : a Reference's informations
    **/
    public function viewReference(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		//$input=$request->all();
		$input=array_map('trim', $request->all());
		try{
			$validationRules=[
	            'reference_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, reference_id not provided";
	            return $returnArr;
	        }

		    $referenceData=$this->reference->where('reference_id', $input['reference_id'])->first();
	        if($referenceData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No reference found with provided reference_id";
	        }else{
	        	$result['data']=$referenceData;
	        	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Reference information fetched successfully";
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
    * User update Reference Method
    * update Reference information by it's ID
    * Return : updated Reference's informations
    **/
    public function updateReference(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		//$input=array_map('trim', $request->all());
		try{
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;
    		$isReadyToInsert=0;

			$validationRules=[
				'reference_id' => 'required',
	            'full_reference' => 'required'
	        ];

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

	        $referenceData=$this->reference->where('reference_id', $input['reference_id'])->first();
	        if($referenceData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No reference found with provided reference_id";
	        }
	        else{
	        	$fullReference = trim($input['full_reference']);
	        	$fullReferenceInArray = explode(",", $fullReference);
	        	if(count($fullReferenceInArray) >= 2){
	        		$referenceAutor = trim($fullReferenceInArray[0]);
	        		array_shift($fullReferenceInArray);
	        		$referenceTxt = implode(",", $fullReferenceInArray);

	        		$updateData['full_reference']= $fullReference;
		        	$updateData['autor']= (isset($referenceAutor) and $referenceAutor != "") ? $referenceAutor : NULL;
		        	$updateData['reference']= (isset($referenceTxt) and $referenceTxt != "") ? trim($referenceTxt) : NULL;
		        	$updateData['kommentar']= (isset($input['kommentar']) and $input['kommentar'] != "") ? $input['kommentar'] : NULL;
				    $updateData['unklarheiten']= (isset($input['unklarheiten']) and $input['unklarheiten'] != "")? $input['unklarheiten'] : NULL;
				    $updateData['active']= (isset($input['active']) and $input['active'] !="" ) ? $input['active'] : 1;
				    $updateData['ip_address']=$request->ip();
				    $updateData['stand']=\Carbon\Carbon::now()->toDateTimeString();
				    $updateData['bearbeiter_id']=$logedInUser;

				    $updateResult=$this->reference->where('reference_id', $input['reference_id'])->update($updateData);
				    if($updateResult){
						$insertedData=$this->reference->where('reference_id', $input['reference_id'])->first();
		    		
						$result['data']=$insertedData;
				    	$returnArr['status']=2;
		                $returnArr['content']=$result;
		                $returnArr['message']="Reference information updated successfully";		    		
				    }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not update the reference";
				    }
	        	}else{
	        		$validationMsg['full_reference']="Provided full reference string does not match the required format. Full reference sample: Matthioli, Comment. in Diosc. lib. IV. Cap. 73.";
	        		$returnArr['status']=3;
		            $returnArr['content']=$validationMsg;
		            $returnArr['message']="Validation failed";
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
    * User delete Reference Method
    * delete a Reference by it's ID
    * Return : nothing(blank)
    **/
    public function deleteReference(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		//$input=array_map('trim', $request->all());
		try{
			DB::beginTransaction();
			$validationRules=[
	            'reference_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, reference_id not provided";
	            return $returnArr;
	        }

	        if (is_array($input['reference_id'])) 
    		{
    			$resultData=$this->reference->whereIn('reference_id', $input['reference_id'])->delete();
	    		if($resultData){
		        	$returnArr['status']=2;
			        $returnArr['content']="";
			        $returnArr['message']="Reference(s) deleted successfully";
		        }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not delete the reference(s). Please check the provided reference(s)";
		        }
    		}
    		else
    		{
    			$referenceData=$this->reference->where('reference_id', $input['reference_id'])->first();
		        if($referenceData === null){
		        	$returnArr['status']=4;
			        $returnArr['content']="";
			        $returnArr['message']="No Reference found with provided reference_id";
		        }else{
		        	$resultData=$this->reference->where('reference_id', $input['reference_id'])->delete();
			        if($resultData){
			        	$returnArr['status']=2;
				        $returnArr['content']="";
				        $returnArr['message']="Reference deleted successfully";
			        }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not delete the reference";
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
