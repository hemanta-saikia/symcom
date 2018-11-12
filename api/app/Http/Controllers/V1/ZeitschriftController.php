<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\Helpers as CustomHelper;

class ZeitschriftController extends Controller
{

    public function __construct(\App\User $user, \App\Admin $admin, \App\Quelle $quelle){
        $this->user = $user;
        $this->admin = $admin;
        $this->quelle = $quelle;
        $this->dateFormat=config('constants.date_format');
        $this->dateTimeFormat=config('constants.date_time_format');
    }

    /**
    * Fetching all Zeitschrift Method 
    * Return : all Zeitschrift 
    **/
    public function allZeitschrift(Request $request){
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
	        	$zeitschriftData=$this->quelle
	        				->with('autoren', 'herkunft')
	        				->where('quelle.quelle_type_id', 2)
	    					->orderBy('quelle.ersteller_datum', 'desc')
	    					->get();
	    		$dataArray['data']=$zeitschriftData->toArray();
	        }else{
	        	$zeitschriftData=$this->quelle
	        				->with('autoren', 'herkunft')
	        				->where('quelle.quelle_type_id', 2)
	    					->orderBy('quelle.ersteller_datum', 'desc')
	    					->paginate($dataPerPage);
	    		$dataArray=$zeitschriftData->toArray();
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
    * adding Zeitschrift Method
    * Adding a Zeitschrift
    * Return : added Zeitschrift informations
    **/
    public function addZeitschrift(Request $request)
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
	            'code' => 'required',
	            'titel' => 'required',
	            'jahr' => 'required|digits:4',
	            'autor_id' => 'required'
	        ];
	        if(isset($input['file_url']) && $input['file_url']!=""){
	        	$fileValidationRule=[
		            'file_url' => 'required|mimes:pdf,doc,docx|max:31000'
		        ];
		        $validationRules=array_merge($validationRules, $fileValidationRule);
	        }

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

	        if(isset($input['file_url']) && $input['file_url']!=""){
	        	$micoTime=preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
		        $fileName = $micoTime.'.'.$request->file_url->getClientOriginalExtension();

		        $tempUploadPath = storage_path('temp-files/');
		        $uploadPath = storage_path('uploads/quelle/');
		        $file = $request->file('file_url');
		        $uploadRes=$file->move($tempUploadPath, $fileName);
		        if($uploadRes){
		        	if(rename($tempUploadPath."/".$fileName, $uploadPath."/".$fileName))
		        		$isReadyToInsert=1;
		        }
	        }
	        else
	        	$isReadyToInsert=1;
	        
	        if($isReadyToInsert == 1){
	        	$insertData['quelle_type_id']= 2;
			    $insertData['herkunft_id']= (isset($input['herkunft_id']) and $input['herkunft_id'] != "") ? $input['herkunft_id'] : NULL;
			    $insertData['code']= (isset($input['code']) and $input['code'] != "") ? $input['code'] : NULL;
			    $insertData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;
			    $insertData['jahr']= (isset($input['jahr']) and $input['jahr'] != "") ? $input['jahr'] : NULL;
			    $insertData['band']= (isset($input['band']) and $input['band'] != "") ? $input['band'] : NULL;
			    $insertData['jahrgang']= (isset($input['jahrgang']) and $input['jahrgang'] != "") ? $input['jahrgang'] : NULL;
			    $insertData['nummer']= (isset($input['nummer']) and $input['nummer'] != "") ? $input['nummer'] : NULL;
			    $insertData['supplementheft']= (isset($input['supplementheft']) and $input['supplementheft'] != "") ? $input['supplementheft'] : NULL;
			    $insertData['file_url']= (isset($fileName) and $fileName != "") ? $fileName : NULL;
			    $insertData['active']= (isset($input['active']) and $input['active'] != "") ? $input['active'] : 1;
			    $insertData['ip_address']=$request->ip();
			    $insertData['ersteller_datum']=\Carbon\Carbon::now()->toDateTimeString();
			    $insertData['ersteller_id']=$logedInUser;

			    $zeitschriftResult=$this->quelle->create($insertData);
			    if($zeitschriftResult){

			    	if(isset($input['autor_id']) and !empty($input['autor_id'])){
			    		$autorData = [];
						foreach ($input['autor_id'] as $autorKey => $autorVal) {
						    $autorData[] = [
						        'quelle_id'  => $zeitschriftResult->id,
						        'autor_id'  	=> $autorVal,
						        'ersteller_datum' => \Carbon\Carbon::now()->toDateTimeString(),
						        'ersteller_id' => $logedInUser,
						    ];
						}

						$autorInsertRes=DB::table('quelle_autor')->insert($autorData);
				    	if($autorInsertRes == true){
				    		$insertedData=$this->quelle->where('quelle_id', $zeitschriftResult->id)->with('autoren', 'herkunft')->first();
				    		
        					$result['data']=$insertedData;
					    	$returnArr['status']=2;
			                $returnArr['content']=$result;
			                $returnArr['message']="Zeitschrift created successfully";
				    	}
				    	else{
				    		$returnArr['status']=5;
			                $returnArr['content']="";
			                $returnArr['message']="Operation failed, could not insert the autors";
				    	}
			    	}
			    	else{
			    		$insertedData=$this->quelle->where('quelle_id', $zeitschriftResult->id)->with('autoren', 'herkunft')->first();
			    		
        				$result['data']=$insertedData;
				    	$returnArr['status']=2;
		                $returnArr['content']=$result;
		                $returnArr['message']="Zeitschrift created successfully";
			    	}
			    	
			    }
			    else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not create the zeitschrift";
			    }
			    DB::commit();
	        }
		    else{
		    	$returnArr['status']=5;
                $returnArr['content']="";
                $returnArr['message']="Operation failed, could not upload the file";
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
    * view Zeitschrift Method
    * view Zeitschrift information by it's ID
    * Return : a Zeitschrift's informations
    **/
    public function viewZeitschrift(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		//$input=$request->all();
		$input=array_map('trim', $request->all());
		try{
			$validationRules=[
	            'quelle_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, quelle_id not provided";
	            return $returnArr;
	        }

		    $zeitschriftData=$this->quelle->where('quelle_id', $input['quelle_id'])->where('quelle_type_id', 2)->with('autoren', 'herkunft')->first();
	        if($zeitschriftData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No zeitschrift found with provided quelle_id";
	        }else{
	        	$result['data']=$zeitschriftData;
	        	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Zeitschrift information fetched successfully";
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
    * User update Zeitschrift Method
    * update Zeitschrift information by it's ID
    * Return : updated Zeitschrift's informations
    **/
    public function updateZeitschrift(Request $request)
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
				'quelle_id' => 'required',
	            'code' => 'required',
	            'titel' => 'required',
	            'jahr' => 'required|digits:4',
	            'autor_id' => 'required'
	        ];
	        if(isset($input['file_url']) && $input['file_url']!=""){
	        	$fileValidationRule=[
		            'file_url' => 'required|mimes:pdf,doc,docx|max:31000'
		        ];
		        $validationRules=array_merge($validationRules, $fileValidationRule);
	        }

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

	        $zeitschriftData=$this->quelle->where('quelle_id', $input['quelle_id'])->where('quelle_type_id', 2)->first();
	        if($zeitschriftData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No zeitschrift found with provided quelle_id";
	        }
	        else{

	        	if(isset($input['file_url']) && $input['file_url']!=""){
		        	$micoTime=preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
			        $fileName = $micoTime.'.'.$request->file_url->getClientOriginalExtension();

			        $tempUploadPath = storage_path('temp-files/');
			        $uploadPath = storage_path('uploads/quelle/');
			        $file = $request->file('file_url');
			        $uploadRes=$file->move($tempUploadPath, $fileName);
			        if($uploadRes){
			        	if(rename($tempUploadPath."/".$fileName, $uploadPath."/".$fileName))
			        		$isReadyToInsert=1;
			        }
		        }
		        else
		        	$isReadyToInsert=1;

		        if($isReadyToInsert == 1){
		        	$updateData['herkunft_id']= (isset($input['herkunft_id']) and $input['herkunft_id'] != "") ? $input['herkunft_id'] : NULL;
				    $updateData['code']= (isset($input['code']) and $input['code'] != "") ? $input['code'] : NULL;
				    $updateData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;
				    $updateData['jahr']= (isset($input['jahr']) and $input['jahr'] != "") ? $input['jahr'] : NULL;
				    $updateData['band']= (isset($input['band']) and $input['band'] != "") ? $input['band'] : NULL;
				    $updateData['jahrgang']= (isset($input['jahrgang']) and $input['jahrgang'] != "") ? $input['jahrgang'] : NULL;
				    $updateData['nummer']= (isset($input['nummer']) and $input['nummer'] != "") ? $input['nummer'] : NULL;
				    $updateData['supplementheft']= (isset($input['supplementheft']) and $input['supplementheft'] != "") ? $input['supplementheft'] : NULL;
				    if(isset($input['file_url']) && $input['file_url']!="")
				    	$updateData['file_url']= (isset($fileName) and $fileName != "") ? $fileName : NULL;
				    $updateData['active']= (isset($input['active']) and $input['active'] != "") ? $input['active'] : 1;
			    	$updateData['ip_address']=$request->ip();
				    $updateData['stand']=\Carbon\Carbon::now()->toDateTimeString();
				    $updateData['bearbeiter_id']=$logedInUser;

				    $updateResult=$this->quelle->where('quelle_id', $input['quelle_id'])->update($updateData);
				    if($updateResult){
				    	$deleteExistingAutor=DB::table('quelle_autor')->where('quelle_id', $input['quelle_id'])->delete();

			    		if(isset($input['autor_id']) and !empty($input['autor_id'])){
				    		$autorData = [];
							foreach ($input['autor_id'] as $autorKey => $autorVal) {
								$autorData[] = [
							        'quelle_id'  => $input['quelle_id'],
							        'autor_id'  	=> $autorVal,
							        'ersteller_datum' => \Carbon\Carbon::now()->toDateTimeString(),
							        'ersteller_id' => $logedInUser,
							    ];
							}

							$autorInsertRes=DB::table('quelle_autor')->insert($autorData);
							if($autorInsertRes == true){
					    		$insertedData=$this->quelle->where('quelle_id', $input['quelle_id'])->with('autoren', 'herkunft')->first();
					    		
	        					$result['data']=$insertedData;
						    	$returnArr['status']=2;
				                $returnArr['content']=$result;
				                $returnArr['message']="Quelle information updated successfully";
					    	}
					    	else{
					    		$returnArr['status']=5;
				                $returnArr['content']="";
				                $returnArr['message']="Operation failed, could not assigne the autors";
					    	}
						}else{
							$insertedData=$this->quelle->where('quelle_id', $input['quelle_id'])->with('autoren', 'herkunft')->first();
							
	        				$result['data']=$insertedData;
				        	$returnArr['status']=2;
			                $returnArr['content']=$result;
			                $returnArr['message']="Quelle information updated successfully";
						}
				    		
				    }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not update the zeitschrift";
				    }
		        }
		        else{
		        	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not upload the file";
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
    * User delete Zeitschrift Method
    * delete a Zeitschrift by it's ID
    * Return : nothing(blank)
    **/
    public function deleteZeitschrift(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		//$input=array_map('trim', $request->all());
		try{
			DB::beginTransaction();
			$validationRules=[
	            'quelle_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, quelle_id not provided";
	            return $returnArr;
	        }

	        if (is_array($input['quelle_id'])) 
    		{
    			DB::table('quelle_autor')->whereIn('quelle_id', $input['quelle_id'])->delete();
    			$resultData=$this->quelle->whereIn('quelle_id', $input['quelle_id'])->delete();
	    		if($resultData){
		        	$returnArr['status']=2;
			        $returnArr['content']="";
			        $returnArr['message']="Zeitschrift deleted successfully";
		        }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not delete the zeitschrift. Please check the provided quelle_id(s)";
		        }
    		}
    		else
    		{
    			$zeitschriftData=$this->quelle->where('quelle_id', $input['quelle_id'])->where('quelle_type_id', 2)->first();
		        if($zeitschriftData === null){
		        	$returnArr['status']=4;
			        $returnArr['content']="";
			        $returnArr['message']="No Zeitschrift found with provided quelle_id";
		        }else{
		        	DB::table('quelle_autor')->where('quelle_id', $input['quelle_id'])->delete();
		        	$resultData=$this->quelle->where('quelle_id', $input['quelle_id'])->delete();
			        if($resultData){
			        	$returnArr['status']=2;
				        $returnArr['content']="";
				        $returnArr['message']="Zeitschrift deleted successfully";
			        }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not delet the Zeitschrift";
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
