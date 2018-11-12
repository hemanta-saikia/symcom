<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\FilesystemServiceProvider;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use App\Libraries\Helpers as CustomHelper;
use Image;

class QuelleController extends Controller
{

    public function __construct(\App\User $user, \App\Admin $admin, \App\Quelle $quelle){
        $this->user = $user;
        $this->admin = $admin;
        $this->quelle = $quelle;
        $this->dateFormat=config('constants.date_format');
        $this->dateTimeFormat=config('constants.date_time_format');
    }

    /**
    * Fetching all Quelle Method 
    * Return : all Quelle 
    **/
    public function allQuelle(Request $request){
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
	        	$quelleData=$this->quelle
	        				->with('autoren', 'herkunft', 'verlag')
	        				->where('quelle.quelle_type_id', 1)
	    					->orderBy('quelle.ersteller_datum', 'desc')
	    					->get();
	    		$dataArray['data']=$quelleData->toArray();
	        }else{
	        	$quelleData=$this->quelle
	        				->with('autoren', 'herkunft', 'verlag')
	        				->where('quelle.quelle_type_id', 1)
	    					->orderBy('quelle.ersteller_datum', 'desc')
	    					->paginate($dataPerPage);
	    		$dataArray=$quelleData->toArray();
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
    * adding Quelle Method
    * Adding a Quelle
    * Return : added Quelle informations
    **/
    public function addQuelle(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		//$input=array_map('trim', $request->all());

		try{
			// ini_set("post_max_size", "32M");
			// ini_set("upload_max_filesize", "32M");
			// ini_set("memory_limit", "20000M");
			DB::beginTransaction();
			$guard=CustomHelper::getGuard();
			$currentUser = \Auth::guard($guard)->user();
    		$logedInUser=isset($currentUser->id) ? $currentUser->id : NULL;
    		$isReadyToInsert=0;

			$validationRules=[
	            'code' => 'required',
	            'titel' => 'required',
	            'sprache' => 'required',
	            'jahr' => 'required|digits:4',
	            'auflage' => 'required',
	            'verlag_id' => 'required'
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
	        	$insertData['quelle_type_id']= 1;
	        	$insertData['quelle_schema_id']= (isset($input['quelle_schema_id']) and $input['quelle_schema_id'] != "") ? $input['quelle_schema_id'] : NULL;
			    $insertData['herkunft_id']= (isset($input['herkunft_id']) and $input['herkunft_id'] != "")? $input['herkunft_id'] : NULL;
			    $insertData['code']= (isset($input['code']) and $input['code'] != "") ? $input['code'] : NULL;
			    $insertData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;
			    $insertData['jahr']= (isset($input['jahr']) and $input['jahr'] != "")? $input['jahr'] : NULL;
			    $insertData['band']= (isset($input['band']) and $input['band'] != "")? $input['band'] : NULL;
			    $insertData['nummer']= (isset($input['nummer']) and $input['nummer'] != "")? $input['nummer'] : NULL;
			    $insertData['auflage']= (isset($input['auflage']) and $input['auflage'] != "")? $input['auflage'] : NULL;
			    $insertData['file_url']= (isset($fileName) and $fileName !="") ? $fileName : NULL;
			    $insertData['verlag_id']= (isset($input['verlag_id']) and $input['verlag_id'] != "") ? $input['verlag_id'] : NULL; 
			    $insertData['sprache']= (isset($input['sprache']) and $input['sprache'] !="" ) ? $input['sprache'] : NULL;
			    $insertData['active']= (isset($input['active']) and $input['active'] !="" ) ? $input['active'] : 1;
			    $insertData['ip_address']=$request->ip();
			    $insertData['ersteller_datum']=\Carbon\Carbon::now()->toDateTimeString();
			    $insertData['ersteller_id']=$logedInUser;

			    $quelleResult=$this->quelle->create($insertData);
			    if($quelleResult){

			    	if(isset($input['autor_id']) and !empty($input['autor_id'])){
			    		$autorData = [];
						foreach ($input['autor_id'] as $autorKey => $autorVal) {
						    $autorData[] = [
						        'quelle_id'  => $quelleResult->id,
						        'autor_id'  	=> $autorVal,
						        'ersteller_datum' => \Carbon\Carbon::now()->toDateTimeString(),
						        'ersteller_id' => $logedInUser,
						    ];
						}

						$autorInsertRes=DB::table('quelle_autor')->insert($autorData);
				    	if($autorInsertRes == true){
				    		$insertedData=$this->quelle->where('quelle_id', $quelleResult->id)->with('autoren', 'herkunft', 'verlag')->first();
        					$result['data']=$insertedData;
					    	$returnArr['status']=2;
			                $returnArr['content']=$result;
			                $returnArr['message']="Quelle created successfully";
				    	}
				    	else{
				    		$returnArr['status']=5;
			                $returnArr['content']="";
			                $returnArr['message']="Operation failed, could not insert the autors";
				    	}
			    	}
			    	else{
			    		$insertedData=$this->quelle->where('quelle_id', $quelleResult->id)->with('autoren', 'herkunft', 'verlag')->first();

        				$result['data']=$insertedData;
				    	$returnArr['status']=2;
		                $returnArr['content']=$result;
		                $returnArr['message']="Quelle created successfully";
			    	}
			    	
			    }
			    else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not create the quelle";
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
    * view Quelle Method
    * view Quelle information by it's ID
    * Return : a Quelle's informations
    **/
    public function viewQuelle(Request $request)
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

		    $quelleData=$this->quelle->where('quelle_id', $input['quelle_id'])->where('quelle_type_id', 1)->with('autoren', 'herkunft', 'verlag')->first();
	        if($quelleData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No quelle found with provided quelle_id";
	        }else{
	        	$result['data']=$quelleData;
	        	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Quelle information fetched successfully";
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
    * User update Quelle Method
    * update Quelle information by it's ID
    * Return : updated Quelle's informations
    **/
    public function updateQuelle(Request $request)
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
	            'sprache' => 'required',
	            'jahr' => 'required|digits:4',
	            'auflage' => 'required',
	            'verlag_id' => 'required'
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

	        $quelleData=$this->quelle->where('quelle_id', $input['quelle_id'])->where('quelle_type_id', 1)->first();
	        if($quelleData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No quelle found with provided quelle_id";
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
		        	$updateData['quelle_schema_id']= (isset($input['quelle_schema_id']) and $input['quelle_schema_id'] != "") ? $input['quelle_schema_id'] : NULL;
				    $updateData['herkunft_id']= (isset($input['herkunft_id']) and $input['herkunft_id'] != "")? $input['herkunft_id'] : NULL;
				    $updateData['code']= (isset($input['code']) and $input['code'] != "") ? $input['code'] : NULL;
				    $updateData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;
				    $updateData['jahr']= (isset($input['jahr']) and $input['jahr'] != "")? $input['jahr'] : NULL;
				    $updateData['band']= (isset($input['band']) and $input['band'] != "")? $input['band'] : NULL;
				    $updateData['nummer']= (isset($input['nummer']) and $input['nummer'] != "")? $input['nummer'] : NULL;
				    $updateData['auflage']= (isset($input['auflage']) and $input['auflage'] != "")? $input['auflage'] : NULL;
				    if(isset($input['file_url']) && $input['file_url']!="")
				    	$updateData['file_url']= (isset($fileName) and $fileName !="") ? $fileName : NULL;
				    $updateData['verlag_id']= (isset($input['verlag_id']) and $input['verlag_id'] != "") ? $input['verlag_id'] : NULL; 
				    $updateData['sprache']= (isset($input['sprache']) and $input['sprache'] !="" ) ? $input['sprache'] : NULL;
				    $updateData['active']= (isset($input['active']) and $input['active'] !="" ) ? $input['active'] : 1;
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
					    		$insertedData=$this->quelle->where('quelle_id', $input['quelle_id'])->with('autoren', 'herkunft', 'verlag')->first();
					    		
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
							$insertedData=$this->quelle->where('quelle_id', $input['quelle_id'])->with('autoren', 'herkunft', 'verlag')->first();
							
				        	$result['data']=$insertedData;
				        	$returnArr['status']=2;
			                $returnArr['content']=$result;
			                $returnArr['message']="Quelle information updated successfully";
						}
				    		
				    }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not update the quelle";
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
    * User delete Quelle Method
    * delete a Quelle by it's ID
    * Return : nothing(blank)
    **/
    public function deleteQuelle(Request $request)
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
			        $returnArr['message']="Quelle(n) deleted successfully";
		        }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not delete the quelle(n). Please check the provided quelle_id(s)";
		        }
    		}
    		else
    		{
    			$quelleData=$this->quelle->where('quelle_id', $input['quelle_id'])->where('quelle_type_id', 1)->first();
		        if($quelleData === null){
		        	$returnArr['status']=4;
			        $returnArr['content']="";
			        $returnArr['message']="No Quelle found with provided quelle_id";
		        }else{
		        	DB::table('quelle_autor')->where('quelle_id', $input['quelle_id'])->delete();
		        	$resultData=$this->quelle->where('quelle_id', $input['quelle_id'])->delete();
			        if($resultData){
			        	$returnArr['status']=2;
				        $returnArr['content']="";
				        $returnArr['message']="Quelle deleted successfully";
			        }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not delet the Quelle";
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
    * Get quelle schemas Method
    * fetching all predefined quelle schemas
    * Return : all predefine quelle schemas
    **/
    public function getPreDefinedQuelleSchemas(){
    	$returnArr=config('constants.return_array');
		try{
			$result['data']=config('constants.quelle_schemas');
        	$returnArr['status']=2;
            $returnArr['content']=$result;
            $returnArr['message']="quelle schemas fetch successfully";
		}
    	catch(\Exception $e){
        	$returnArr['status']=6;
	        $returnArr['content']=$e;
	        $returnArr['message']="Something went wrong";
        }

        return $returnArr; 
    }
}
