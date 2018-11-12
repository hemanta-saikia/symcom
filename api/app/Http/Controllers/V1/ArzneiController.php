<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\Helpers as CustomHelper;

class ArzneiController extends Controller
{

    public function __construct(\App\Arznei $arznei){
        $this->arznei = $arznei;
        $this->dateFormat=config('constants.date_format');
        $this->dateTimeFormat=config('constants.date_time_format');
    }

    /**
    * Fetching all Arznei Method 
    * Return : all Arznei 
    **/
    public function allArznei(Request $request){
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
	        	$arzneiData=$this->arznei
	        				->with('autoren', 'quelle')
	    					->orderBy('arznei.ersteller_datum', 'desc')
	    					->get();
	    		$dataArray['data']=$arzneiData->toArray();
	        }else{
	        	$arzneiData=$this->arznei
	        				->with('autoren', 'quelle')
	    					->orderBy('arznei.ersteller_datum', 'desc')
	    					->paginate($dataPerPage);
	    		$dataArray=$arzneiData->toArray();
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
    * adding Arznei Method
    * Adding a Arznei
    * Return : added Arznei informations
    **/
    public function addArznei(Request $request)
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
	            'titel' => 'required'
	        ];

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }
	        
        	$insertData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;;
        	$insertData['kommentar']= (isset($input['kommentar']) and $input['kommentar'] != "") ? $input['kommentar'] : NULL;
		    $insertData['unklarheiten']= (isset($input['unklarheiten']) and $input['unklarheiten'] != "")? $input['unklarheiten'] : NULL;
		    $insertData['active']= (isset($input['active']) and $input['active'] !="" ) ? $input['active'] : 1;
		    $insertData['ip_address']=$request->ip();
		    $insertData['ersteller_datum']=\Carbon\Carbon::now()->toDateTimeString();
		    $insertData['ersteller_id']=$logedInUser;

		    $arzneiResult=$this->arznei->create($insertData);
		    if($arzneiResult){
		    	$canProceed=1;
		    	if(isset($input['autor_id']) and !empty($input['autor_id'])){
		    		$autorData = [];
					foreach ($input['autor_id'] as $autorKey => $autorVal) {
					    $autorData[] = [
					        'arznei_id'  => $arzneiResult->id,
					        'autor_id'  	=> $autorVal,
					        'ersteller_datum' => \Carbon\Carbon::now()->toDateTimeString(),
					        'ersteller_id' => $logedInUser,
					    ];
					}

					$autorInsertRes=DB::table('arznei_autor')->insert($autorData);
					if($autorInsertRes == true)
			    		$canProceed=1;
			    	else
			    		$canProceed=0;
		    	}

		    	if($canProceed == 1){
		    		if(isset($input['quelle_id']) and !empty($input['quelle_id'])){
			    		$quelleData = [];
						foreach ($input['quelle_id'] as $queleKey => $quelleVal) {
						    $quelleData[] = [
						        'arznei_id'  => $arzneiResult->id,
						        'quelle_id'  	=> $quelleVal,
						        'ersteller_datum' => \Carbon\Carbon::now()->toDateTimeString(),
						        'ersteller_id' => $logedInUser,
						    ];
						}

						$quelleInsertRes=DB::table('arznei_quelle')->insert($quelleData);
						if($quelleInsertRes == true)
				    		$canProceed=1;
				    	else
				    		$canProceed=0;
			    	}

			    	if($canProceed == 1){
			    		$insertedData=$this->arznei->where('arznei_id', $arzneiResult->id)->with('autoren', 'quelle')->first();
						$result['data']=$insertedData;
				    	$returnArr['status']=2;
		                $returnArr['content']=$result;
		                $returnArr['message']="Arznei created successfully";
			    	}else{
			    		$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not insert the quelle";
			    	}
		    	}else{
		    		$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not insert the autors";
		    	}	
		    }
		    else{
		    	$returnArr['status']=5;
                $returnArr['content']="";
                $returnArr['message']="Operation failed, could not create the arznei";
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
    * view Arznei Method
    * view Arznei information by it's ID
    * Return : a Arznei's informations
    **/
    public function viewArznei(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		//$input=$request->all();
		$input=array_map('trim', $request->all());
		try{
			$validationRules=[
	            'arznei_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, arznei_id not provided";
	            return $returnArr;
	        }

		    $arzneiData=$this->arznei->where('arznei_id', $input['arznei_id'])->with('autoren', 'quelle')->first();
	        if($arzneiData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No arznei found with provided arznei_id";
	        }else{
	        	$result['data']=$arzneiData;
	        	$returnArr['status']=2;
                $returnArr['content']=$result;
                $returnArr['message']="Arznei information fetched successfully";
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
    * User update Arznei Method
    * update Arznei information by it's ID
    * Return : updated Arznei's informations
    **/
    public function updateArznei(Request $request)
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
				'arznei_id' => 'required',
	            'titel' => 'required'
	        ];

	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed";
	            return $returnArr;
	        }

	        $quelleData=$this->arznei->where('arznei_id', $input['arznei_id'])->first();
	        if($quelleData === null){
	        	$returnArr['status']=4;
		        $returnArr['content']="";
		        $returnArr['message']="No arznei found with provided arznei_id";
	        }
	        else{

	        	$updateData['titel']= (isset($input['titel']) and $input['titel'] != "") ? $input['titel'] : NULL;;
	        	$updateData['kommentar']= (isset($input['kommentar']) and $input['kommentar'] != "") ? $input['kommentar'] : NULL;
			    $updateData['unklarheiten']= (isset($input['unklarheiten']) and $input['unklarheiten'] != "")? $input['unklarheiten'] : NULL;
			    $updateData['active']= (isset($input['active']) and $input['active'] !="" ) ? $input['active'] : 1;
			    $updateData['ip_address']=$request->ip();
			    $updateData['stand']=\Carbon\Carbon::now()->toDateTimeString();
			    $updateData['bearbeiter_id']=$logedInUser;

			    $updateResult=$this->arznei->where('arznei_id', $input['arznei_id'])->update($updateData);
			    if($updateResult){
			    	$deleteExistingAutor=DB::table('arznei_autor')->where('arznei_id', $input['arznei_id'])->delete();
			    	$deleteExistingQuelle=DB::table('arznei_quelle')->where('arznei_id', $input['arznei_id'])->delete();
			    	$canProceed=1;

		    		if(isset($input['autor_id']) and !empty($input['autor_id'])){
			    		$autorData = [];
						foreach ($input['autor_id'] as $autorKey => $autorVal) {
						    $autorData[] = [
						        'arznei_id'  => $input['arznei_id'],
						        'autor_id'  	=> $autorVal,
						        'ersteller_datum' => \Carbon\Carbon::now()->toDateTimeString(),
						        'ersteller_id' => $logedInUser,
						    ];
						}

						$autorInsertRes=DB::table('arznei_autor')->insert($autorData);
						if($autorInsertRes == true)
							$canProceed=1;
						else
							$canProceed=0;
					}

					if($canProceed == 1){
						if(isset($input['quelle_id']) and !empty($input['quelle_id'])){
				    		$quelleData = [];
							foreach ($input['quelle_id'] as $quelleKey => $quelleVal) {
							    $quelleData[] = [
							        'arznei_id'  => $input['arznei_id'],
							        'quelle_id'  	=> $quelleVal,
							        'ersteller_datum' => \Carbon\Carbon::now()->toDateTimeString(),
							        'ersteller_id' => $logedInUser,
							    ];
							}

							$quelleInsertRes=DB::table('arznei_quelle')->insert($quelleData);
							if($quelleInsertRes == true)
								$canProceed=1;
							else
								$canProceed=0;
						}

						if($canProceed == 1){
							$insertedData=$this->arznei->where('arznei_id', $input['arznei_id'])->with('autoren', 'quelle')->first();
			    		
	    					$result['data']=$insertedData;
					    	$returnArr['status']=2;
			                $returnArr['content']=$result;
			                $returnArr['message']="Arznei information updated successfully";
						}else{
							$returnArr['status']=5;
			                $returnArr['content']="";
			                $returnArr['message']="Operation failed, could not assigne the quelles";	
						}
					}else{
						$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not assigne the autors";
					}			    		
			    }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not update the arznei";
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
    * User delete Arznei Method
    * delete a Arznei by it's ID
    * Return : nothing(blank)
    **/
    public function deleteArznei(Request $request)
	{ 
		$returnArr=config('constants.return_array');
		$input=$request->all();
		//$input=array_map('trim', $request->all());
		try{
			DB::beginTransaction();
			$validationRules=[
	            'arznei_id' => 'required'
	        ];
	        $validator= \Validator::make($input, $validationRules);
	        if($validator->fails()){
	            $returnArr['status']=3;
	            $returnArr['content']=$validator->errors();
	            $returnArr['message']="Validation failed, arznei_id not provided";
	            return $returnArr;
	        }

	        if (is_array($input['arznei_id'])) 
    		{
    			DB::table('arznei_autor')->whereIn('arznei_id', $input['arznei_id'])->delete();
    			DB::table('arznei_quelle')->whereIn('arznei_id', $input['arznei_id'])->delete();
    			$resultData=$this->arznei->whereIn('arznei_id', $input['arznei_id'])->delete();
	    		if($resultData){
		        	$returnArr['status']=2;
			        $returnArr['content']="";
			        $returnArr['message']="Arznei(s) deleted successfully";
		        }else{
			    	$returnArr['status']=5;
	                $returnArr['content']="";
	                $returnArr['message']="Operation failed, could not delete the arznei(s). Please check the provided arznei_id(s)";
		        }
    		}
    		else
    		{
    			$arzneiData=$this->arznei->where('arznei_id', $input['arznei_id'])->first();
		        if($arzneiData === null){
		        	$returnArr['status']=4;
			        $returnArr['content']="";
			        $returnArr['message']="No Arznei found with provided arznei_id";
		        }else{
		        	DB::table('arznei_autor')->where('arznei_id', $input['arznei_id'])->delete();
		        	DB::table('arznei_quelle')->where('arznei_id', $input['arznei_id'])->delete();
		        	$resultData=$this->arznei->where('arznei_id', $input['arznei_id'])->delete();
			        if($resultData){
			        	$returnArr['status']=2;
				        $returnArr['content']="";
				        $returnArr['message']="Arznei deleted successfully";
			        }else{
				    	$returnArr['status']=5;
		                $returnArr['content']="";
		                $returnArr['message']="Operation failed, could not delet the Arznei";
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
