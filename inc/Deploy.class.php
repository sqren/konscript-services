<?php
include("inc/Check.class.php");

class Deploy{		
	var $check;

    function Deploy($project_name, $deploy_type){        
    	// initialize check
    	$check = new Check();
    	        
		// set variables  
        $this->check = $check;
	    $check->setProjectName($project_name);    	        
        $check->setStageFolder("prod");    
            
	    // validators                                                    
        $check->checkGitRemote();  
        $check->checkFolderMustExist();
        $check->checkFolderMustExist("/.git");        
		$check->checkFolderWritable();       
		$check->checkFolderWritable("/.git");		
				
		// validators passed
		if ($check->getNumberOfErrors() == 0){
			$path = $check->root.$project_name."/prod/";
			$current_version = get_latest_prod_version($path);
		
			// deploy to new version
			if($deploy_type == "new"){
				$next_version = $current_version + 1;
		
				// validate that the next version doesn't exist
				$this->checkFolderCannotExist($path.$next_version);    				          
				if($check->getNumberOfErrors() == 0){
					recursive_copy($path.$current_version, $path.$next_version);            
					
					// add temporary virtual host
					$pathToTemp = $check->root."temp/link1";
					unlink($pathToTemp);
					symlink($path.$next_version, $pathToTemp);  					
				}
			
				$version = $next_version;
			
			// deploy to existing version
			}else{
				$version = $current_version;
			}
			
			if($check->getNumberOfErrors() == 0){
				$git_response = Git::git_callback('pull konscript master', $path.$version, true);
				$check->checkGitPull($git_response);  
			}
			                                        
		}	
				
		echo $check->outputResult();		       				                

    }                                       

	/**
	 * VALIDATION: folder must NOT exist
	 ***************************************/
    function checkFolderCannotExist($path){
        $status = !file_exists($path) ? 0 : 1;
        $msg = array("success"=>"The folder does not exist: $path", "error"=>"The folder already exists: $path");
        $this->check->addCheck($status, $msg, __function__);    
    }    

}    
?>