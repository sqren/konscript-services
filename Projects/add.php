<?php
include("../inc/Check.class.php");
include("../inc/header.php");
                     
    // save project
    if($_POST){
        
        $projectName = $_POST["projectName"];
        $full_path_to_project = $web_root.$projectName;

        // init check
		$check = new Check();
        $check->setProjectName($projectName);
		        
        // Validations
        $check->checkFolderCannotExist($full_path_to_project); // folder cannot exist
		$check->checkGithub(); // github project must exist
	  
		if ($check->getNumberOfErrors() == 0){
        
 
            // create project root - 02770: leading zero is required; 2 is the sticky bit (set guid); 770 is rwx,rwx,---
            mkdir($full_path_to_project, 02770);
            
            // create dev
            $full_path_to_dev = $full_path_to_project."/dev";
            mkdir($full_path_to_dev, 02770);
            $repo = Git::create($full_path_to_dev); //git init        
            $repo->run('remote add konscript '. $check->getPathToGitRemote());
            
            // wordpress: download and extract latest version
            wp_get_latest($projectName, $_POST["wordpress"]);            
            
            // create prod: clone dev to prod
            $full_path_to_prod = $full_path_to_project."/prod";
            mkdir($full_path_to_prod, 02770);
			recursive_copy($full_path_to_dev, $full_path_to_prod."/1"); 
			
			// add symlink
			$target = $full_path_to_prod."/1";
			$link = $full_path_to_prod."/current";
			symlink($target, $link);			
			
			// create databases
			$connection = New DbConn();
			$connection->connect();
		
		    // create prod database
			$connection->query("CREATE DATABASE IF NOT EXISTS `".$projectName."-prod`");
		    
		    // create dev database
			$connection->query("CREATE DATABASE IF NOT EXISTS `".$projectName."-dev`");
                        
            header("Location: check.php?projectName=".$projectName);
            
        }
        
		echo $check->outputResult();
    }
?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	Project name: <input type="text" name="projectName" /> <br />
	<input type="checkbox" name="wordpress" value="true" /> Download and install wordpress <br />
	<input type="submit" name="save" value="Save" />	
</form>