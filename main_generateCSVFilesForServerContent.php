<?php

require_once 'ListSitesGroupsUserWorkbooks.php';
require_once 'ClassConstants.php';



//Sites
$siteData=array();
$iniPropertiesFile;
$authToken='';
$siteToken='';
$URL;
$APIVersion;
$urlPreface;


//this grabs the data we need from the server and stores it in $siteData
/*
 * For each site
 * List of users, and which groups a user is in
 * List of Groups and which users are in them
 * List of workbooks Users can view
 * List of projects
 * List of workbooks and the user and Group permissions on them (this differs from workboks above, as if a user is denied View permission, it will appear here) 
 * List of datasources and the user and Group permissions on them
 * 
 * When building the permissions CSV files, we build this by User, for each Workbook and datasource, we get group permissions, 
 * and then write these out for each user in the group, for each individual user permission, we for every user, we list thier SITEROLE permissions
 * so we can evaluate their final permission on an object
 * 
 */
$date =  date("Y-m-d h:i:s");


getDataFromServer($argv,$siteData,$iniPropertiesFile,$authToken,$siteToken,$URL,$APIVersion,$urlPreface);


$fileAreadyExists=false;
//see if user wants to append data, or overwrite
$appendData=true;
if (key_exists('appendData', $iniPropertiesFile))
    $appendData =    filter_var($iniPropertiesFile['appendData'], FILTER_VALIDATE_BOOLEAN); 


//loop through each site, and write out the data to the files
foreach ($siteData as $x => $x_value) {
    
    collectDataForThisSite($x_value, $siteData,$authToken,$siteToken,$URL,$APIVersion,$urlPreface);
    //now write out to log files    
    writeToLogFiles($appendData, $siteData[$x],$date,$iniPropertiesFile);
    //remove collected data for Site
    unset($siteData[$x]);
   
    $appendData=true;
    
}

$date =  date_create($date);
$interval = date_diff($date,  date_create(date("Y-m-d h:i:s")));
$minutes = $interval->format('%i');
$seconds = $interval->format('%s');
debug_to_console("Finished File generation: duration->".(($minutes*60)+$seconds).' seconds');


die();

function writeToLogFiles($appendData,$site,$date,$iniPropertiesFile){
    $Sites = ClassSites::_CSVHeader . PHP_EOL;
    $Site_Users = ClassUser::_CSVHeader.PHP_EOL;
    $Site_Group_Users = ClassGroup::_CSVHeader.PHP_EOL;
    $Site_Project_WorkBook_Users = ClassWorkbook::_CSVHeader.PHP_EOL;
    $Site_Project = ClassProjects::_CSVHeader.PHP_EOL;
    $Site_Project_groupAndUsers=ClassProjects::_CSVHeaderPermissions.PHP_EOL;;
    $ObjectPermissions = "TimeStamp,SiteId,UserName,UserId,SiteRole,ObjectType,ProjectID,ObjectName,ObjectId,contentURL,PermissionType,PermissionGroupName,PermissionName,PermissionValueint,PermissionValue,Notes".PHP_EOL;
    
     
    $fileAreadyExists;
    
    $filePath = getFilePath("siteData",$iniPropertiesFile,$fileAreadyExists);
    if($fileAreadyExists && $appendData)
        $Sites="";
        $Sites .= $site->getCSVData($date,$iniPropertiesFile['URL']) . PHP_EOL;
   
        
        writeToFile($filePath, $Sites, "SiteData written to " . $filePath, $appendData);
        
        /*****************************************************************************/
        
        $filePath = getFilePath("userData",$iniPropertiesFile,$fileAreadyExists);
        if($fileAreadyExists && $appendData)
            $Site_Users="";
            
                $UserOnSite = $site->getUsers();
                foreach ($UserOnSite as $y => $user) {
                    $Site_Users .=$date. ',' . $site->getID() . ',' . $user->getCSVData() . PHP_EOL;
                }
   
            
            writeToFile($filePath, $Site_Users, "UserData written to " . $filePath, $appendData);
            
            /*******************************************************************************/
            
            //for groups
            $filePath = getFilePath("groupData",$iniPropertiesFile,$fileAreadyExists);
            if($fileAreadyExists && $appendData)
                $Site_Group_Users="";
                
   
                    
                    $GroupsOnSite = $site->getGroups();
                    $UserOnSite = $site->getUsers();
                    foreach ($GroupsOnSite as $y => $group) {
                        $Site_Group_Users.=$group->getCSVData( $site->getID(),$UserOnSite,$date);
                    }
                    
   
                
                writeToFile($filePath, $Site_Group_Users, "groupData written to " . $filePath,$appendData);
                
                /*******************************************************************************/
                
                //Workbooks
                $filePath = getFilePath("workBookData",$iniPropertiesFile,$fileAreadyExists);
                if($fileAreadyExists && $appendData)
                    $Site_Project_WorkBook_Users="";
                    
   
                        
                        $workbooks = $site->getWorkbookPermsissions();
                        foreach ($workbooks as  $wb) {
                            $Site_Project_WorkBook_Users.=$wb->getCSVData( $site->getID(),$date);
                        }
   
                    
                    writeToFile($filePath, $Site_Project_WorkBook_Users, "workBookData written to " . $filePath,$appendData);
                    
                    /*******************************************************************************/
                    
                    //projects
                    $filePath = getFilePath("projectData",$iniPropertiesFile,$fileAreadyExists);
                    if($fileAreadyExists && $appendData)
                        $Site_Project="";
                        
   
                            $projectsOnSite = $site->getProjects();
                            
                            foreach ($projectsOnSite as $y => $proj) {
                                $Site_Project.=$proj->getCSVData( $site->getID(),$date,$projectsOnSite);
                            }
   
                        
                        writeToFile($filePath, $Site_Project, "projectData written to " . $filePath,$appendData);
                        
                        /*******************************************************************************/
                        
                        
                        //projects groups and user who have been granted permissions
                        $filePath = getFilePath("projectDataUserAndGroups",$iniPropertiesFile,$fileAreadyExists);
                        if($fileAreadyExists && $appendData)
                            $Site_Project_groupAndUsers="";
                            
   
                                $projectsOnSite = $site->getProjects();
                                
                                foreach ($projectsOnSite as $y => $proj) {
                                    $Site_Project_groupAndUsers.=$proj->getCSVDataWithGroupsAndUser( $site->getID(),$date,$projectsOnSite,$site->getUsers(),$site->getGroups());
                                }
   
                            
                            writeToFile($filePath, $Site_Project_groupAndUsers, "projectData for Group and users assigned permissions written to " . $filePath,$appendData);
                            
                            /*******************************************************************************/
                            
                            //list workbook permissions, who can see them, and why
                            //can get very large so need to write out to the file as data is collected
                            
                            $data=$ObjectPermissions;
                            $filePath = getFilePath("objectPermissions",$iniPropertiesFile,$fileAreadyExists);
                            if($fileAreadyExists && $appendData)
                                $data="";
                                else
                                    writeToFile($filePath, $data, "" . $filePath,$appendData );
                                    
   
                                        if($site->getName()=="Advancement")
                                            $a=1;
                                            
                                            $workbooks = $site->getWorkbookPermsissions();
                                            $size = count($workbooks);
                                            $counter=1;
                                            foreach ($workbooks as  $wb) {
                                                $data = getCSVObjectPermissionData($site->getId(), $wb, $site->getUsers(),$site->getGroups(),ClassConstants::ObjectWorkbook,$date,$site->getProjects());
                                                writeToFile($filePath, $data,  "Site: ".$site->getName() ." Object permissions written for workbooks for $counter of $size", true );
                                                $counter++;
                                                
                                            }
   
                                    
                                    debug_to_console( "Object permissions for workbooks written to " . $filePath);
                                    
                                    //list data sources permissions, who can see them, and why
                                    //Not based on users, this data is only in ClassDataSource, but we have Group and User IDs, so pass in User Info and Group Info, so we show User and group names
                                    //This can get large, so write as the data comes back
                                    $data="";
   
                                        
                                        $dataSources = $site->getDataSources();
                                        $size = count($dataSources);
                                        $counter=1;
                                        foreach ($dataSources as  $ds) {
                                            $data = getCSVObjectPermissionData($site->getId(), $ds, $site->getUsers(), $site->getGroups(),ClassConstants::ObjectDataSource,$date,$site->getProjects());
                                            writeToFile($filePath, $data, "Site: ".$site->getName() ." Object permissions written for datasources for $counter of $size", true );
                                            $counter++;
                                        }
   
                                    
                                    debug_to_console( "Object permissions for datasources written to " . $filePath);
    
    
}






