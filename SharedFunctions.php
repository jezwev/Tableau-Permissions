<?php
require_once 'ClassSiteRoles.php';
require_once 'ClassPermissions.php';
require_once 'ClassFinalPermission.php';



function listUserThenGroup($groupData, $userdata)
{
    foreach ($userdata as $x => $x_value) {
        $local = ($x_value);
        
        foreach ($groupData as $group => $group_value) {
            
            $serverGroupUsers = $groupData[(string) $group_value->getLowerCaseName()]->getUsers();
            // see if user exists in this Group
            if (key_exists($local->getLowerCaseName(), $serverGroupUsers)) {
                debug_to_console("User: [" . (string) $local->getName() . "] , Group: [" . (string) $group_value->getName() . "]");
            }
        }
    }
}

function listWorkBookThenUser($workbooks)
{
    foreach ($workbooks as $x => $workbook) {
        
        //all users who can view workbook
        $users = $workbook->getUsers();
        foreach ($users as $user => $localUser) {
            debug_to_console("Project->Workbook: [" . (string) $workbook->getProjectName() . "]->[".
                (string)$workbook->getName()."] User:[".$localUser."]");
        }
    }
}


function getProjects($urlPreface, $authToken, &$projects){
    /*
     * <tsResponse version-and-namespace-settings>
     <pagination pageNumber="1" pageSize="100" totalAvailable="1"/>
     <projects>
     <project id="fae56e51-fbee-4eab-a9af-d8283cc0ed77" name="TestNestProject" description="" parentProjectId="711d4c5d-60b7-4f77-a9a6-bbf05021b6ec">
     <owner id="4d8308f7-ec47-4eb1-a383-429374a8d9cb"/>
     </project>
     </projects>
     </tsResponse>
     *
     */
    
    $continue=true;
    $pageNumber=1;
    //need to add paging
    
    while ($continue){
        
        
        $xml = curlTableauXML($urlPreface . "projects?pageSize=50&pageNumber=$pageNumber&fields=_all_", $authToken);
        $totalAvailable= intval($xml->pagination->attributes()->totalAvailable);
        
        if ($totalAvailable<=($pageNumber*50))
            $continue=false;
            else $pageNumber++;
            
            foreach ($xml->projects->project as $project) {
                // if ((string) $project->attributes()->name=='Sandbox'){
                $projectClass = new ClassProjects($project->attributes()->name, $project->attributes()->id,
                    $project->attributes()->description, $project->attributes()->contentPermissions,$project->attributes()->parentProjectId,$project->owner->attributes()->id);
                $projects[strtolower($projectClass->getId())] = $projectClass;
                debug_to_console("Project: [" . (string) $projectClass->getName() . "] ,Permissions: [" . (string) $project->attributes()->contentPermissions."]");
                // }
            }
            
    }
    
}

function getUserAndWorkbooks($userdata,$urlPreface,$authToken,&$workBooks)
{
    foreach ($userdata as $x => $x_value) {
        $local = ($x_value);
        // get list of user on the site
        // email not returned by default, so specifically ask for all fields
        $xml = curlTableauXML($urlPreface . "users/".$local->getID()."/workbooks?pageSize=1000", $authToken);
        /*
         * <tsResponse>
         <pagination pageNumber="pageNumber" pageSize="page-size"
         totalAvailable="total-available" />
         <workbooks>
         <workbook id="workbook-id" name="name"
         contentUrl="content-url"
         showTabs="show-tabs-flag"
         size="size-in-megabytes"
         createdAt="datetime-created"
         updatedAt="datetime-updated"  >
         <project id="project-id" name="project-name" />
         <owner id="user-id" />
         <tags>
         <tag label="tag"/>
         ... additional tags ...
         </tags>
         </workbook>
         ... additional workbooks ...
         </tsResponse>
         */
        //now loop through XML to get workbooks for users
        //need to see if wrokbook already exists
        foreach ($xml->workbooks->workbook as $workbook) {
            //
            if(!key_exists(strtolower($workbook->project->attributes()->name.">".$workbook->attributes()->name), $workBooks) )
                $workbookClass = new ClassWorkbook($workbook->attributes()->name, $workbook->attributes()->id, $workbook->attributes()->contentUrl,
                    $workbook->attributes()->updatedAt, $workbook->project->attributes()->name, $workbook->project->attributes()->id, $workbook->attributes()->size);
                else
                    $workbookClass=$workBooks[strtolower($workbook->project->attributes()->name.">".$workbook->attributes()->name)];
                    // save as lower case key, so can compare with names from File (server names are not case sensitive
                    $workbookClass->addUser($local->getLowerCaseName());
                    //As workbooks are only unique for a project, use Project Name = Workbook Name for key
                    $workBooks[strtolower($workbookClass->getProjectName().">".$workbookClass->getName())] = $workbookClass;
                    debug_to_console("User: [" . (string) $local->getName() . "] ,Project->Workbook: [" . (string) $workbook->project->attributes()->name . "]->[".(string)$workbook->attributes()->name."]");
        }
        
    }
}

function getDatasources($urlPreface,$authToken,&$ds)
{
    
    $continue=true;
    $pageNumber=1;
    //need to add paging
    
    while ($continue){
        
        // get list of user on the site
        // email not returned by default, so specifically ask for all fields
        
        $xml = curlTableauXML($urlPreface . "datasources?pageSize=50&pageNumber=$pageNumber&fields=_default_", $authToken);
        $totalAvailable= intval($xml->pagination->attributes()->totalAvailable);
        
        if ($totalAvailable<=($pageNumber*50))
            $continue=false;
            else $pageNumber++;
            
            
            /*
             <tsResponse>
             <pagination pageNumber="pageNumber" pageSize="page-size"
             totalAvailable="total-available" />
             <datasources>
             <datasource id="datasource1-id"
             name="datasource-name"
             contentUrl="datasource-content-url"
             type="datasource-type"
             createdAt="datetime-created"
             updatedAt="datetime-updated">
             <project id="project-id" name="project-name" />
             <owner id="datasource-owner-id" />
             <tags>
             <tag label="tag"/>
             ... additional tags ...
             </tags>
             </datasource>
             ... additional datasources ...
             </datasources>
             </tsResponse>
             */
            
            //now loop through XML to get datasources
            
            foreach ($xml->datasources->datasource as $datasource) {
                
                $dsClass = new ClassDataSource($datasource->attributes()->name, $datasource->attributes()->id, $datasource->attributes()->contentUrl,
                    $datasource->attributes()->updatedAt, $datasource->project->attributes()->name, $datasource->project->attributes()->id,
                    $datasource->attributes()->type, $datasource->attributes()->isCertified,$datasource->attributes()->createdAt,$datasource->owner->attributes()->id);
                
                $ds[strtolower($dsClass->getProjectName().">".$dsClass->getName())] = $dsClass;
                debug_to_console("DataSource : [" .$dsClass->getProjectName().">".$dsClass->getName() .']');
            }
    }
}


function getSiteWorkbooks($urlPreface,$authToken,&$wb)
{
    $continue=true;
    $pageNumber=1;
    
    while ($continue ){
        
        
        // /api/api-version/sites/site-id/datasources?filter=filter-expression
        $xml = curlTableauXML($urlPreface . "workbooks?pageSize=50&pageNumber=$pageNumber&fields=_default_", $authToken);
        $totalAvailable= intval($xml->pagination->attributes()->totalAvailable);
        
        if ($totalAvailable<=($pageNumber*50))
            $continue=false;
            else $pageNumber++;
            /*
             <tsResponse>
             <pagination pageNumber="page-number"
             pageSize="page-size"
             totalAvailable="total-available" />
             <workbooks>
             <workbook id="workbook-id" name="name"
             contentUrl="content-url"
             showTabs="show-tabs-flag"
             size="size-in-megabytes"
             createdAt="datetime-created"
             updatedAt="datetime-updated"  >
             <project id="project-id" name="project-name" />
             <owner id="user-id" />
             <tags>
             <tag label="tag"/>
             ... additional tags ...
             </tags>
             </workbook>
             ... additional workbooks ...
             </workbooks>
             </tsResponse>
             */
            //now loop through XML to get datasources
            
            foreach ($xml->workbooks->workbook as $workbook) {
                
                
                //if ((string)$workbook->attributes()->name=='sherry'){
                $workbookClass = new ClassWorkbook($workbook->attributes()->name, $workbook->attributes()->id, $workbook->attributes()->contentUrl,
                    $workbook->attributes()->updatedAt, $workbook->project->attributes()->name, $workbook->project->attributes()->id,
                    $workbook->attributes()->size,$workbook->owner->attributes()->id);
                
                $wb[strtolower($workbookClass->getProjectName().">".$workbookClass->getName())] = $workbookClass;
                //}
                
            }
            
    }
    
}
function getFirstViewForEachWorkbook($urlPreface,$authToken,&$workBooks)
{
    $size= count($workBooks);
    $progress=1;
    
    foreach ($workBooks as &$workbook) {
        
        //for each workbook, get list of permissions
        try{
            $xml = curlTableauXML($urlPreface . "workbooks/".$workbook->getID()."/views?includeUsageStatistics=true", $authToken);
            debug_to_console("Workbook: getting first view: " .$progress++.' of '.$size.' for '.$workbook->getName());
            
            /*
             * <tsResponse version-and-namespace-settings>
             <views>
             <view id="1f1e1d1c-2b2a-2f2e-3d3c-3b3a4f4e4d4c" name="Tale of 100 Start-ups"
             contentUrl="Finance/sheets/Taleof100Start-ups"/>
             <view id="9a8a7b6b-5c4c-3d2d-1e0e-9a8a7b6b5b4b" name="Economic Indicators"
             contentUrl="Finance/sheets/EconomicIndicators"/>
             <view id="7b6b59a8-ac3c-4d1d-2e9e-0b5b4ba8a7b6" name="Investing in the Dow"
             contentUrl="Finance/sheets/InvestingintheDow"/>
             </views>
             </tsResponse>
             */
            $workbook->setViewContentURL($xml->views->view->attributes()->contentUrl);
            $workbook->setTotalViewCount($xml->views->view->usage->attributes()->totalViewCount);
        }
        catch (Exception $e){
            debug_to_console("Exception getting first view for ".$workbook->getName()." Error: ".$e->getMessage());
        }
        catch (Error $e){
            debug_to_console("Error getting first view for ".$workbook->getName()." Error: ".$e->getMessage());
        }
    }
    
    
}


function getWorkbookPermissions($urlPreface,$authToken,&$workBooks)
{
    
    /*
     * If user is a project leader, then they get acces to all..same for group, but user and group logic still applies
     * Note: Project leader overrides Denied at the same level - if Project leader, all values are ALLOWED, Add
     * this as  flag so we can see why all are allowed
     *
     * PROJECT LEAD PERMISSIONS are a property of the Project!, so need to check there
     *
     */
    $size= count($workBooks);
    $progress=1;
    
    foreach ($workBooks as $x => $workbook) {
        
        //for each workbook, get list of permissions
        $xml = curlTableauXML($urlPreface . "workbooks/".$workbook->getID()."/permissions", $authToken);
        debug_to_console("Workbook Permissions: " .$progress++.' of '.$size);
        /*
         * <tsResponse>
         <parent type="Project" id="project-id" />
         <permissions>
         <workbook id="workbook-id" name="workbook-name >
         <owner="owner-user-id" />
         </workbook>
         <granteeCapabilities>
         <user id="user-id" />
         <capabilities>
         <capability name="capability" mode="capability-mode" />
         ... additional capabilities for users ...
         </capabilities>
         </granteeCapabilities>
         <granteeCapabilities>
         <group id="group-id" />
         <capabilities>
         <capability name="capability" mode="capability-mode" />
         ... additional capabilities for groups ...
         </capabilities>
         </granteeCapabilities>
         ... additional grantee capability sets ...
         </permissions>
         </tsResponse>
         */
        //now loop through XML to get workbooks for users
        //need to see if wrokbook already exists
        foreach ($xml->permissions->granteeCapabilities as $granteeCapabilitie) {
            $isGroup=false;
            //check to see if permission is for a Group or specific User
            if(isset($granteeCapabilitie->group)){
                $isGroup=true;
                $granteeID = (string)$granteeCapabilitie->group->attributes()->id;
            }
            else
                $granteeID = (string)$granteeCapabilitie->user->attributes()->id;
                
                
                //now loop through all the permissions
                $permissions=new ClassPermissions(( $isGroup?"":$granteeID) ,( $isGroup?$granteeID:"")  );
                foreach ($granteeCapabilitie->capabilities->capability as $capability) {
                    $permissions->addPermission((string)$capability->attributes()->name,(string)$capability->attributes()->mode);
                }
                
                //save this info as a property of the workbook,and also save to the classUser, so we can retreive by user
                $workbook->addPermissions($permissions,$granteeID);
                
                
                
        }
        
    }
}

function getProjectPermissions($urlPreface,$authToken,&$projects)
{
    
    /*
     * If user is a project leader, then they get acces to all..same for group, but user and group logic still applies
     * Note: Project leader overrides Denied at the same level - if Project leader, all values are ALLOWED, Add
     * this as  flag so we can see why all are allowed
     *
     * PROJECT LEAD PERMISSIONS are a property of the Project!, so need to check there
     *
     */
    $size= count($projects);
    $progress=1;
    foreach ($projects as &$project) {
        
        debug_to_console("Project Permissions: " .$progress++.' of '.$size . ' for: ['.$project->getName().']');
        //for each workbook, get list of permissions
        $xml = curlTableauXML($urlPreface . "projects/".$project->getID()."/permissions", $authToken);
        /*
         * <tsResponse>
         <permissions>
         <project id="1f2f3e4e-5d6d-7c8c-9b0b-1a2a3f4f5e6e" name="default">
         <owner id="9f9e9d9c-8b8a-8f8e-7d7c-7b7a6f6d6e6d"/>
         </project>
         <granteeCapabilities>
         <group id="1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d"/>
         <capabilities>
         <capability name="Read" mode="Allow"/>
         <capability name="Write" mode="Allow"/>
         </capabilities>
         </granteeCapabilities>
         </permissions>
         </tsResponse>
         */
        //now loop through XML to get workbooks for users
        //need to see if wrokbook already exists
        foreach ($xml->permissions->granteeCapabilities as $granteeCapabilitie) {
            $isGroup=false;
            //check to see if permission is for a Group or specific User
            if(isset($granteeCapabilitie->group)){
                $isGroup=true;
                $granteeID = (string)$granteeCapabilitie->group->attributes()->id;
            }
            else
                $granteeID = (string)$granteeCapabilitie->user->attributes()->id;
                
                
                //now loop through all the permissions
                $permissions=new ClassPermissions(( $isGroup?"":$granteeID) ,( $isGroup?$granteeID:"")  );
                foreach ($granteeCapabilitie->capabilities->capability as $capability) {
                    $permissions->addPermission((string)$capability->attributes()->name,(string)$capability->attributes()->mode);
                }
                
                //save this info as a property of the project
                $project->addPermissions($permissions,$granteeID);
                
                
                
        }
        
    }
}

function getDataSourcePermissions($urlPreface,$authToken,&$dataSources)
{
    
    $size= count($dataSources);
    $progress=1;
    foreach ($dataSources as $dataSource) {
        
        //for each workbook, get list of permissions
        $xml = curlTableauXML($urlPreface . "datasources/".$dataSource->getID()."/permissions", $authToken);
        debug_to_console("DataSource Permissions: " .$progress++.' of '.$size);
        /*
         * <tsResponse>
         <permissions>
         <parent type="Project" id="project-id"
         <datasource id="datasource-id"
         owner="owner-user-id" />
         <granteeCapabilities>
         <user id="user-id" />
         <capabilities>
         <capability name="capability-name" mode="capability-mode" />
         ... additional capabilities ...
         </capabilities>
         </granteeCapabilities>
         <granteeCapabilities>
         <group id="group-id" />
         <capabilities>
         <capability name="capability" mode="capability-mode" />
         ... additional capabilities ...
         </capabilities>
         </granteeCapabilities>
         ... additional grantee capability sets ...
         </permissions>
         </tsResponse>
         */
        //now loop through XML to get workbooks for users
        //need to see if wrokbook already exists
        foreach ($xml->permissions->granteeCapabilities as $granteeCapabilitie) {
            $isGroup=false;
            //check to see if permission is for a Group or specific User
            if(isset($granteeCapabilitie->group)){
                $isGroup=true;
                $granteeID = (string)$granteeCapabilitie->group->attributes()->id;
            }
            else
                $granteeID = (string)$granteeCapabilitie->user->attributes()->id;
                
                
                //now loop through all the permissions
                $permissions=new ClassPermissions(( $isGroup?"":$granteeID) ,( $isGroup?$granteeID:"")  );
                foreach ($granteeCapabilitie->capabilities->capability as $capability) {
                    $permissions->addPermission((string)$capability->attributes()->name,(string)$capability->attributes()->mode);
                }
                
                //save this info as a property of the workbook,and also save to the classUser, so we can retreive by user
                $dataSource->addPermissions($permissions,$granteeID);
                
        }
        
    }
}




function getGroupNameFromId($id,$groups){
    
    foreach ($groups as $group) {
        ;
        if($group->getId()==$id)
            return $group->getName();
    }
    
}

function debug_to_console($data, $active = FALSE)
{
    $output = $data;
    $localStr = date("Y-m-d H:i:s") . " " . print_r($output, true) . "\n";
    echo ($localStr);
    // if log file set, write to it as well
    if (key_exists('iniPropertiesFile', $GLOBALS)) {
        if (key_exists('verboseLog', $GLOBALS['iniPropertiesFile']))
            $file = fopen($GLOBALS['iniPropertiesFile']['verboseLog'], "a");
            
            // see if we could create the file, if not create in current dir
            if (! $file)
                $file = fopen(getcwd() . '\verboseLog.log', "a");
                if ($file) {
                    fwrite($file, $localStr);
                    fclose($file);
                }
    }
    // see if there is a activity log, which only records changes made to the server
    if ($active && key_exists('iniPropertiesFile', $GLOBALS)) {
        if (key_exists('activityLog', $GLOBALS['iniPropertiesFile']))
            $file = fopen($GLOBALS['iniPropertiesFile']['activityLog'], "a");
            if (! $file)
                $file = fopen(getcwd() . '\activityLog.log', "a");
                if ($file) {
                    fwrite($file, $localStr);
                    fclose($file);
                }
    }
}

function getServerUsers($urlPreface, $authToken, &$userdata,$siteName="")
{
    
    $continue=true;
    $pageNumber=1;
    //need to add paging
    
    while ($continue){
        
        // get list of user on the site
        // email not returned by default, so specifically ask for all fields
        
        $xml = curlTableauXML($urlPreface . "users?pageSize=50&pageNumber=$pageNumber&fields=_all_", $authToken);
        try {
            
            $totalAvailable= intval($xml->pagination->attributes()->totalAvailable);
        
        
        
        if ($totalAvailable<=($pageNumber*50))
            $continue=false;
            else $pageNumber++;
            
            /*
             * Example of response
             *
             * <<tsResponsetsResponse>>
             * <<paginationpagination pageNumberpageNumber==""page-numberpage-number""
             * pageSizepageSize==""page-sizepage-size""
             * totalAvailabletotalAvailabl ="total-available" />
             * <users>
             * <user id="user-id"
             * name="user-name"
             * siteRole="site-role"
             * lastLogin="date-time"
             * externalAuthUserId="authentication-id-from-external-provider"
             * authSetting="auth-setting" />
             * <user id="user-id"
             * name="user-name"
             * siteRole="site-role"
             * lastLogin="date-time"
             * externalAuthUserId="authentication-id-from-external-provider"
             * authSetting="auth-setting" />
             * ... additional users ...
             * </users>
             * </tsResponse>
             */
            
            foreach ($xml->users->user as $user) {
                
                $userClass = new ClassUser($user->attributes()->name, $user->attributes()->id, $user->attributes()->siteRole, $user->attributes()->email, $user->attributes()->lastLogin,"", $user->attributes()->fullName);
                // save as lower case key, so can compare with names from File (server names are not case sensitive
                $userdata[$userClass->getLowerCaseName()] = $userClass;
            }
            
        } catch (Error $e) {
            debug_to_console("Error getting Server users: " . $e->getMessage() );
        }
            
    }
    
    // sort associative arrays in ascending order, according to the key
    ksort($userdata);
    
    foreach ($userdata as $x => $x_value) {
        $local = ($x_value);
        debug_to_console("Site: [".$siteName."], User: [" . (string) $local->getName() . "], Full Name: [".(string) $local->getFullName()."], email: [" . (string) $local->getEmail() . "], siteRole: [" . (string) $local->getSiteRole() . "], id: [" . (string) $local->getId() . "] ");
    }
}

//get groups, and users in groups
//save groups with users, and then we also save for each User, which groups they are in..same data in two different classes
function getServerGroups($urlPreface, $authToken, &$groupData,$siteName="",&$userData)
{
    // get list of groups, and users for each site
    $xml = curlTableauXML($urlPreface . "groups", $authToken);
    
    /*
     * Example of response
     *
     * <tsResponse>
     * <pagination pageNumber="pageNumber"
     * pageSize="page-size"
     * totalAvailable="total-available" />
     * <groups>
     * <group id="group-id"
     * name="group-name">
     * <domain name="domain-for-group" />
     * </group>
     * <group id="group-id"
     * name="group-name">
     * <domain name="domain-for-group" />
     * </group>
     * ... additional groups ...
     * </groups>
     * </tsResponse>
     */
    
    foreach ($xml->groups->group as $group) {
        
        $groupClass = new ClassGroup($group->attributes()->name, $group->attributes()->id);
        $groupData[(string) $groupClass->getLowerCaseName()] = $groupClass;
    }
    // sort associative arrays in ascending order, according to the key
    ksort($groupData);
    
    foreach ($groupData as $x => $x_value) {
        $local = ($x_value);
        debug_to_console("Site: [".$siteName."], Group: [" . (string) $local->getName() . "], ID: [" . (string) $local->getId() . "]");
    }
    
    // now get users in each group
    foreach ($groupData as $x => $x_value) {
        $local = ($x_value);
        $xml = curlTableauXML($urlPreface . "groups/" . $local->getId() . "/users", $authToken);
        /*
         * <tsResponse>
         * <pagination pageNumber="pageNumber"
         * pageSize="page-size"
         * totalAvailable="total-available" />
         * <users>
         * <user id="user-id"
         * name="user-name"
         * siteRole="site-role"
         * lastLogin="last-login-date-time"
         * externalAuthUserId="authentication-id-from-external-provider" />
         * ... additional user information ...
         * </users>
         * </tsResponse>
         */
        foreach ($xml->users->user as $user) {
            $groupData[(string) $local->getLowerCaseName()]->addUser($user->attributes()->name);
            debug_to_console("Site: [".$siteName."], Group: [" . $local->getName() . "] -> User [" . $user->attributes()->name . "]");
            //save this to the user class as well
            //case where user in group is not in list of users (only seen this for guest user, could be if guest is created for
            //some sites, that it appears for all, even if it is not in list of users
            //appears in All Users, but not if you look at site via GUI
            if (key_exists(strtolower($user->attributes()->name), $userData))
                $userData[strtolower($user->attributes()->name)]->addGroup($local->getLowerCaseName(),$local->getId());
                
        }
    }
}

/*
 * catch syntax errors in ini file, so catch Warning
 */
function readIniFile($file_path)
{
    set_error_handler("warning_handler", E_WARNING);
    
    try {
        $local = parse_ini_file($file_path);
        restore_error_handler();
        return $local;
    } catch (Exception $e) {
        restore_error_handler();
        return ("Error reading ini file <" . $file_path . "> " . $e->getMessage());
    }
}

function warning_handler($errno, $errstr)
{
    restore_error_handler();
    debug_to_console("Error reading ini file " . $errstr);
    die();
}

function writeCSVFile($data,$fileLocation){
    // if log file set, write to it as well
    if (key_exists('iniPropertiesFile', $GLOBALS)) {
        if (key_exists('userData', $GLOBALS['iniPropertiesFile']))
            $file = fopen($GLOBALS['iniPropertiesFile']['userData'], "a");
            
            // see if we could create the file, if not create in current dir
            if (! $file)
                $file = fopen(getcwd() . '\userData.csv', "a");
                if ($file) {
                    fwrite($file, $data);
                    fclose($file);
                }
    }
    
}


function updateUser($userClass, $urlPreface, $authToken,$siteName){
    
    /*
     * <tsRequest>
     <user fullName="new-full-name"
     email="new-email"
     password="new-password"
     siteRole="new-site-role"
     authSetting="new-auth-setting" />
     </tsRequest>
     */
    $payload = '
                  <tsRequest>
                   <user fullName="' . ($userClass->getFullName()==""?$userClass->getName():$userClass->getFullName()) . '"
                        email="'.$userClass->getEmail().'"
                        siteRole="' . $userClass->getSiteRole() . '"
                        password="'.$userClass->getPassword().'"/>
                  </tsRequest>
                  ';
    
    //now post
    $xml = curlTableauPut($urlPreface, $authToken, $payload);
    // now add new user to userdata
    if(stripos($xml, "error") !== 0) {
        // add this user to the group
        debug_to_console('Site: ['.$siteName.'], User: [' .  $userClass->getName() . '] properties updated',TRUE);
    }
    else
        debug_to_console('Error: Site: ['.$siteName.'], User: [' .  $userClass->getName() . '] properties NOT updated: ' .$xml,TRUE);
        
        
}

function getListOfUserAndGroupsForSite($urlPreface, &$authToken,&$siteToken,&$siteData, $fileSites){
    
    
    getSites($urlPreface, $authToken, $siteData);
    //now get groups for each site
    
    // Proof we have all the Groups and can list through them
    foreach ($fileSites as $x => $x_value) {
        //only get this if Site is in files we are synching with
        if ( !array_key_exists($x_value->getLowerCaseName(),$siteData )) {
            
            debug_to_console("ERROR: Unable to find site:[".$x_value->getName()."] at the server.",TRUE );
        }
        else {
            
            $local = $siteData[$x_value->getLowerCaseName()];
            $groupData = array();
            $userData = array();
            
            //need to switch to the site to access values
            if($siteToken!=(string)$local->getId()){
                
                //cannot switch to site already logged on to, so check the $siteToken
                //gets a new authentication token
                //remove sites from end of $urlPreface
                $payLoad = '<tsRequest><site contentUrl="' . ((string) $local->getContentURL()) . '" /></tsRequest>';
                $xml = curlTableauPost(substr($urlPreface,0,strlen($urlPreface)-5).'auth/switchSite', $authToken, $payLoad);
                if(stripos($xml, "error") === 0) {
                    debug_to_console("Error: Unable to switch sites.....! ".$xml);
                    die();
                }
                
                $authToken = (string) $xml->credentials->attributes()->token;
                $siteToken =(string) $xml->credentials->site->attributes()->id;
            }
            //only do this if we have got data for
            
            getServerGroups($urlPreface.'/'.$siteToken.'/', $authToken, $groupData,$local->getName());
            $siteData[$local->getLowerCaseName()]->addGroup($groupData);
            
            getServerUsers($urlPreface.'/'.$siteToken.'/', $authToken, $userData,$local->getName());
            $siteData[$local->getLowerCaseName()]->addUsers($userData);
            
        }
        
    }
    
}

//Get List of ClassSites
function getSites($urlPreface, $authToken, &$sitedata)
{
    
    /*
     * Do not do this for online, get sites is an invalid call
     *      */
    // get list of user on the site
    $xml = curlTableauXML($urlPreface , $authToken);
    
    /*
     * Example of response
     *
     <tsResponsetsResponse>
     <paginationpagination  pageNumberpageNumber=="pageNumber""pageNumber"
     pageSizepageSize=="page-size""page-size"
     totalAvailabletotalAvailabl ="total-available" />
     <sites>
     <site id="site-id"
     name="site1-name"
     contentUrl="content-url"
     adminMode="admin-mode"
     tierCreatorCapacity="num-users"
     tierExplorerCapacity="num-users"
     tierViewerCapacity="num-users"
     storageQuota="limit-in-megabytes"
     state="active-or-suspended"
     statusReason="reason-for-state" />
     </site>
     ... additional sites ...
     </sites>
     </tsResponse>
     *
     */
    
    foreach ($xml->sites->site as $site) {
        //some XML tags are new in 3.0, so test is exist
        //$name, $id, $state, $contentURL, $adminMode, $subscribeOthersEnabled = FALSE, $guestAccessEnabled = FALSE, $commentingEnabled = FALSE, $cacheWarmupEnabled = FALSE
        
        $siteClass = new ClassSites($site->attributes()->name, $site->attributes()->id, $site->attributes()->state,$site->attributes()->contentUrl,
            $site->attributes()->adminMode,$site->attributes()->subscribeOthersEnabled,$site->attributes()->guestAccessEnabled,$site->attributes()->commentingEnabled,$site->attributes()->cacheWarmupEnabled);
        // save as lower case key, so can compare with names from File (server names are not case sensitive
        $sitedata[strtolower($siteClass->getName())] = $siteClass;
    }
    
    // sort associative arrays in ascending order, according to the key
    ksort($sitedata);
    //debug_to_console($sitedata);
    
    // Proof we have all the users and can list through them
    foreach ($sitedata as $x => $x_value) {
        $local = ($x_value);
        debug_to_console("Site: [" . $x . "], ContentURL: [".(string) $local->getContentURL().'] ID: [' . (string) $local->getId()."]");
    }
}

//Get List of ClassSites
function getSiteForOnline($urlPreface, $authToken, &$sitedata)
{
    
    /*
     * Do not do this for online, get sites is an invalid call
     *      */
    // get list of user on the site
    $xml = curlTableauXML($urlPreface , $authToken);
    
    /*
     * Example of response
     *
     <tsResponsetsResponse>
     <paginationpagination  pageNumberpageNumber=="pageNumber""pageNumber"
     pageSizepageSize=="page-size""page-size"
     totalAvailabletotalAvailabl ="total-available" />
     <sites>
     <site id="site-id"
     name="site1-name"
     contentUrl="content-url"
     adminMode="admin-mode"
     tierCreatorCapacity="num-users"
     tierExplorerCapacity="num-users"
     tierViewerCapacity="num-users"
     storageQuota="limit-in-megabytes"
     state="active-or-suspended"
     statusReason="reason-for-state" />
     </site>
     ... additional sites ...
     </sites>
     </tsResponse>
     *
     */
    
    foreach ($xml->site as $site) {
        //some XML tags are new in 3.0, so test is exist
        //$name, $id, $state, $contentURL, $adminMode, $subscribeOthersEnabled = FALSE, $guestAccessEnabled = FALSE, $commentingEnabled = FALSE, $cacheWarmupEnabled = FALSE
        
        $siteClass = new ClassSites($site->attributes()->name, $site->attributes()->id, $site->attributes()->state,$site->attributes()->contentUrl,
            $site->attributes()->adminMode,$site->attributes()->subscribeOthersEnabled,$site->attributes()->guestAccessEnabled,$site->attributes()->commentingEnabled,$site->attributes()->cacheWarmupEnabled);
        // save as lower case key, so can compare with names from File (server names are not case sensitive
        $sitedata[strtolower($siteClass->getName())] = $siteClass;
    }
    
    // sort associative arrays in ascending order, according to the key
    ksort($sitedata);
    //debug_to_console($sitedata);
    
    // Proof we have all the users and can list through them
    foreach ($sitedata as $x => $x_value) {
        $local = ($x_value);
        debug_to_console("Site: [" . $x . "], ID: [" . (string) $local->getId()."]");
    }
}
function getCSVPermissionData($siteID,$object,$users,$objectTpe){
    
    $retStr="";
    
    //for each workbook list permissions and users who can view. The workbook holds the users who can view and permissions
    
    foreach ($object->getUsers() as $user) {
        $clsUser=$users[$user];
        
        $start = $siteID.','.$clsUser->getName().','.$clsUser->getId().','.$clsUser->getSiteRole().','.$objectTpe.',';
        //for each work, get permissions
        foreach ($object->getPermissions() as $perm) {
            //loop through and see if permission is set, if not, default to None
            //workbook holds all permissions, so for this user, check if they are in the group, or they are the user
            
            if(($perm->isGroupPermission() && array_search($perm->getWorkbookOrDataSourceID(),$clsUser->getGroups()))
                || (!$perm->isGroupPermission()  && $perm->getWorkbookOrDataSourceID()==$clsUser->getId())) {
                    
                    foreach (ClassConstants::RESTAPIWorkbookPermissions as $index => $value) {
                        $retStr.=$start.'"'.$object->getName().'",'.$object->getId().','.$object->getContentUrl().','.($perm->isGroupPermission()?'Group':'User').',';
                        $retStr.=($perm->isGroupPermission()?$clsUser->getGroups()[  array_search($perm->getWorkbookOrDataSourceID(),$clsUser->getGroups())]->getName():'').','.ClassConstants::RESTAPIPermissionsConversion[$index].',';
                        $retStr.=(array_key_exists($value,$perm->getAllPermissions())?($perm->getAllPermissions()[$value]=='Allow'?2:4):1).','.(array_key_exists($value,$perm->getAllPermissions())?$perm->getAllPermissions()[$value]:'None').PHP_EOL;
                    }
                }
                
        }
        
        
        
        
    }
    return  $retStr;
}


//both Groups and Users have same construct, so pass in as generic object
function getNameFromID($id,$objects){
    
    foreach ($objects as $object) {
        if ($object->getId()==$id)
            return $object->getLowerCaseName();
    }
    
}

function buildFinalPerm( $clsUser, $siteID,$object,$date,$finalObjPerm,$objectType ){
    
    $retStr="";
    $start = $date.','.$siteID.','.$clsUser->getName().','.$clsUser->getId().','.$clsUser->getSiteRole().','.$objectType.','.$object->getProjectID().',';
    
    
    
    foreach ($finalObjPerm as $index => $value) {
        
        switch ($value[0]){
            
            case 0:
                $vStr= 'None';
                break;
            case 2:
                $vStr= 'Denied';
                break;
            case 4:
                $vStr= 'Site Role Denied';
                break;
            case 8:
                $vStr='Site Role Denied';
                break;
            case 16:
                $vStr='Site Role Allowed';
        }
        
        $retStr.=$start.'"'.$object->getName().'",'.$object->getId().','.$object->getContentUrl().',Final,';
        $retStr.=','.ucwords($index).',';
        $retStr.=$value[0].','.$vStr.',"'.(string)$value[1].'"' .PHP_EOL;
        
        
    }
    
    return $retStr;
    
}


function getCSVSiteRole($siteID,$clsUser,$object,$type,$date,&$locCls){
    
    //do this seperately for Data sources and for Workbooks, as
    $retStr="";
    $start = $date.','.$siteID.','.$clsUser->getName().','.$clsUser->getId().','.$clsUser->getSiteRole().','.$type.','.$object->getProjectID().',';
    
    
    //are we looking at Workbooks or datasources, they have different permissions
    
    if ($type=="Workbook"){
        $objPerm = ClassConstants::RESTAPIWorkbookPermissions;
    }
    else {
        $objPerm = ClassConstants::RESTAPIDatasourcePermissions;
    }
    
    foreach ($objPerm as $index => $value) {
        
        $retStr.=$start.'"'.$object->getName().'",'.$object->getId().','.$object->getContentUrl().',SITEROLE,';
        $retStr.='SITEROLE,'.$value.',';
        //now grab the permissions for this SITEROLE
        //temp work around, create new instance of ClassUser
        //($name, $id, $siteRole="",$objectName, $objID, $objType="dataSource")
        $locCls=new ClassSiteRoles($clsUser->getName(),$clsUser->getSiteRole());
        
        $retStr.= $locCls->getUserSiteRolePermissionForObject($value,$type).','. $locCls->getUserSiteRolePermissionForObjectString($value,$type).PHP_EOL;
        
    }
    
    return $retStr;
    
    
}


function addAllUserToObjectIfItDoesNotExist(&$object,$groups,$type){
    
    $allUsersID = $groups['all users']->getId();
    //now see if this ID exists
    foreach ($object->getPermissions() as $perm) {
        if ($perm->getWorkbookOrDataSourceID()== $allUsersID)
            return false;
    }
    //we need to add a new permission for All Users
    $perm=new ClassPermissions("",$allUsersID);
    
    //if perm is set to none, we do not get it back, so code handles this by looping through complete
    //list of values, and if value is not present, we set it to None, so should not need to
    //set values to none here
    
    /*  //based on object type, we add different permissions
     $keys = array_keys(($type==ClassConstants::ObjectDataSource?
     ClassConstants::RESTAPIDatasourcePermissions:ClassConstants::RESTAPIWorkbookPermissions));
     foreach ($keys as $key) {
     $perm->addPermission($key, 'None');
     } */
    //now add this to teh object
    $object->addPermissions($perm,$allUsersID);
    //we do not need to create permissions entry, we just want to get the User Site Role
    return true;
}

/*
 another fun case is where a User or Group only has Project Leader, this does not appear under Object Permissions.
 So need to compare permissions for the Object with permissions for Project, for any unmatched Project Permissions
 see if Project Leader, then add
 Store all project leaders in  $projLeaders[], so we can use to compute final Permissions
 */
function checkForProjectPermissionsNotReflectedInObject($siteID,$object,$users,$groups, $type,$date,$projects,&$projLeaders){
    
    //get Project for Object
    $proj = $projects[$object->getProjectID()];
    $retStr="";
    
    foreach ($proj->getPermissions() as $perm) {
        
        //see if permission exists in workbook
        if(!key_exists($perm->getWorkbookOrDataSourceID(), $object->getPermissions())){
            
            if (key_exists('ProjectLeader',  $perm->getAllPermissions()) && $perm->getAllPermissions()['ProjectLeader']=='Allow'){
                
                
                $localuser="";
                if($perm->isGroupPermission()){
                    
                    $localGroup=$groups[getNameFromID($perm->getWorkbookOrDataSourceID(), $groups)];
                    //now loop through all the users in the group
                    foreach ($localGroup->getUsers() as $user) {
                        
                        $localuser=$users[strtolower( $user)];
                        $projLeaders[]=strtolower( $user);
                        $retStr.=buildObjectPermissions($localuser, $siteID,$perm,$groups,$type,$object,$date,TRUE,TRUE);
                    }
                    
                } else {
                    //perm is for a user
                    $localuser = $users[getNameFromID($perm->getWorkbookOrDataSourceID(), $users)];
                    $projLeaders[]=$localuser->getLowerCaseName();
                    $retStr.=buildObjectPermissions($localuser, $siteID,$perm,$groups,$type,$object,$date,FALSE,TRUE);
                    
                }
            }
        }
    }
    return $retStr;
    
    
}


//We do not know who can View a datasource, with Workbooks there is an API call that show who can view
//with DS, we can only get the permissions, which could mean that a user may not be allowed to view (Denied etc)
//we have UserID and GroupID. Users have a list of Groups they belong to, so we can find out users.
function getCSVObjectPermissionData($siteID,$object,$users,$groups, $type,$date,$projects){
    
    $retStr="";
    $userObjPerm = array();
    $userRole=array();
    $usersWhoAreProjectLeaders=array();
    $usersFinalPermissions=array();
    //can only have one project owner, look for this under Groups, as we iterate All Users
    //Project owner gets Read and download priveleges
    $projectOwnerPermissionSet = "";
    
    //note that if a Group is set to None for all permissions, then we do not see this group
    //but could mean that we miss users who have permissions based on their site role.
    //Check to see if All Users in list of groups, if not, add it, and set all permissions to None
    $addAllUsers=addAllUserToObjectIfItDoesNotExist($object,$groups,$type);
    
    //another fun case is where a User or Group only has Project Leader, this does not appear under Object Permissions
    //so need to compare permissions for object with Permissions for Project, for any unmatched Project
    //see if Project Leader, then add
    //If Project leader, get View and Download access
    
    $retStr=checkForProjectPermissionsNotReflectedInObject($siteID,$object,$users,$groups, $type,$date,$projects,$usersWhoAreProjectLeaders);
    
    
    foreach ($object->getPermissions() as $perm) {
        
        
        /*
         add logic here for determing final permissions on an object
         If User values are set, these are used, unless 'None' is specified, then we look at the Group Values.
         If any Group has Denied, we use that, else we take the highest permission across all Groups
         1 = None, 2 = Allow, 4 = Denied
         But we also have to enforce Site Level Role
         */
        
        
        
        //if Group, we need to find all the users who are in the group
        $localuser="";
        
        
        if($perm->isGroupPermission()){
            
            $localGroup=$groups[getNameFromID($perm->getWorkbookOrDataSourceID(), $groups)];
            //now loop through all the users in the group
            foreach ($localGroup->getUsers() as $user) {
                //can get guest user appear in all users, when they are not really on site
                if(key_exists(strtolower( $user), $users)){
                    $localuser=$users[strtolower( $user)];
                    
                    //
                    //need to check to see if this Group has ProjectLeader assigned,Project lead does not need Project to be locked
                    $projectLeader = UserOrGroupIsProjectLeader($localGroup, $projects, $object->getProjectId());
                    //see if in our list of Project leaders where only PL was assigned (does appear in Object permissions)
                    if ($projectLeader && !key_exists(strtolower( $user), $usersWhoAreProjectLeaders))
                        $usersWhoAreProjectLeaders[strtolower( $user)]=strtolower( $user);
                        
                        if(strlen($projectOwnerPermissionSet)<1){
                            if ($localuser->getId()==$projects[$object->getProjectId()]->getOwnerID()){
                                $projectOwnerPermissionSet=$localuser->getId();
                                $retStr.=buildProjectOwnerObjectPermissions($localuser, $siteID,$perm,$groups,$type,$object,$date);
                            }
                        }
                        
                        
                        
                        
                        // if(!($addAllUsers && $localGroup->getLowerCaseName()=='all users'))
                        $retStr.=buildObjectPermissions($localuser, $siteID,$perm,$groups,$type,$object,$date,TRUE,FALSE);
                        
                        //only create SiteROle for user and Object if not already created
                        if (!key_exists($localuser->getLowerCaseName(), $userRole)){
                            
                            $userRole[$localuser->getLowerCaseName()]=$localuser->getLowerCaseName();
                            $retStr.=getCSVSiteRole($siteID,$localuser,$object,$type,$date,$locCls);
                            //add data to final permission
                            if (key_exists(strtolower( $user),$usersFinalPermissions))
                                $usersFinalPermissions[strtolower( $user)]->addSiteRole($locCls->getRolePermissions("",$object));
                                else {
                                    $FP = new ClassFinalPermissions($localuser,$type,$projectOwnerPermissionSet==$localuser->getId());
                                    $FP->addSiteRole($locCls->getRolePermissions("",$object));
                                    $usersFinalPermissions[strtolower( $user)]=$FP;
                                }
                        }
                        
                        //Want to show every group and for User if Proj Lead
                        if ($projectLeader){
                            $retStr.=buildObjectPermissions($localuser, $siteID,$perm,$groups,$type,$object,$date,TRUE,TRUE);
                        }
                        //add data to final permission
                        if (key_exists(strtolower( $user),$usersFinalPermissions))
                            $usersFinalPermissions[strtolower( $user)]->addGroup($perm->getAllPermissions());
                            else {
                                $FP = new ClassFinalPermissions($localuser,$type,$projectOwnerPermissionSet==$localuser->getId());
                                $FP->addGroup($perm->getAllPermissions());
                                $usersFinalPermissions[strtolower( $user)]=$FP;
                            }
                            if (key_exists(strtolower( $user), $usersWhoAreProjectLeaders)  )
                                $usersFinalPermissions[strtolower( $user)]->setProjectLeader(true);
                }
            }
            
        } else {
            //perm is for a user
            $localuser = $users[getNameFromID($perm->getWorkbookOrDataSourceID(), $users)];
            
            
            //need to check to see if this User has ProjectLeader assigned,Project lead does not need Project to be locked
            $projectLeader = UserOrGroupIsProjectLeader($localuser, $projects, $object->getProjectId());
            //see if in our list of Project leaders where only PL was assigned (does appear in Object permissions)
            if ($projectLeader && !key_exists($localuser->getLowerCaseName(), $usersWhoAreProjectLeaders))
                $usersWhoAreProjectLeaders[$localuser->getLowerCaseName()]=$localuser->getLowerCaseName();
                
                
                if(strlen($projectOwnerPermissionSet)<1){
                    if ($localuser->getId()==$projects[$object->getProjectId()]->getOwnerID()){
                        $projectOwnerPermissionSet=$localuser->getId();
                        $retStr.=buildProjectOwnerObjectPermissions($localuser, $siteID,$perm,$groups,$type,$object,$date);
                    }
                }
                
                $retStr.=buildObjectPermissions($localuser, $siteID,$perm,$groups,$type,$object,$date,FALSE,False);
                //only create SiteROle for user and Object if not already created
                if (!key_exists($localuser->getLowerCaseName(), $userRole)){
                    if($localuser->getLowerCaseName()=='b')
                        $a=1;
                        $userRole[$localuser->getLowerCaseName()]=$localuser->getLowerCaseName();
                        $retStr.=getCSVSiteRole($siteID,$localuser,$object,$type,$date,$locCls);
                }
                
                //Want to show every group and for User if Proj Lead
                if ($projectLeader){
                    $retStr.=buildObjectPermissions($localuser, $siteID,$perm,$groups,$type,$object,$date,TRUE,TRUE);
                }
                
                //add data to final permission
                if (key_exists($localuser->getLowerCaseName(),$usersFinalPermissions))
                    $usersFinalPermissions[$localuser->getLowerCaseName()]->addUser($perm->getAllPermissions());
                    else {
                        $FP = new ClassFinalPermissions($localuser,$type,$projectOwnerPermissionSet==$localuser->getId());
                        $FP->addGroup($perm->getAllPermissions());
                        $usersFinalPermissions[$localuser->getLowerCaseName()]=$FP;
                    }
                    
                    if (key_exists($localuser->getLowerCaseName(), $usersWhoAreProjectLeaders)  )
                        $usersFinalPermissions[$localuser->getLowerCaseName()]->setProjectLeader(true);
                        
        }
        
    }
    
    //build final permissions
    $locvar="";
    foreach ($usersFinalPermissions as $fp){
        
        $loc = $fp->getFinalPermission();
        $locvar.= buildFinalPerm( $fp->getUserInfo(), $siteID,  $object, $date, $loc,$type);
        
        
    }
    
    return  $retStr.$locvar;
}


function UserOrGroupIsProjectLeader($userOrGroup, $projects, $currentProjID){
    
    //     if($userOrGroup->getName()=='jweatherall@tableau.com')
        //         $here=true;
    
    //see if any Permissions for the Project for this group contain Prject leader
    if (key_exists($userOrGroup->getId(), $projects[$currentProjID]->getPermissions())){
        
        if (key_exists('ProjectLeader',  ($projects[$currentProjID]->getPermissions()[$userOrGroup->getId()])->getAllPermissions()))
            return ($projects[$currentProjID]->getPermissions()[$userOrGroup->getId()])->getAllPermissions()['ProjectLeader']=='Allow';
            
    }
    
    return false;
}

function buildObjectPermissions( $clsUser, $siteID,$perm,$groups, $objectType,$object,$date,$isGroup, $isProjectLeader){
    
    $retStr="";
    $start = $date.','.$siteID.','.$clsUser->getName().','.$clsUser->getId().','.$clsUser->getSiteRole().','.$objectType.','.$object->getProjectID().',';
    
    $objPerm = ($objectType==ClassConstants::ObjectDataSource?ClassConstants::RESTAPIDatasourcePermissions:ClassConstants::RESTAPIWorkbookPermissions);
    
    foreach ($objPerm as $index => $value) {
        
        if(!$isProjectLeader){
            $retStr.=$start.'"'.$object->getName().'",'.$object->getId().','.$object->getContentUrl().','.($perm->isGroupPermission()?'Group':'User').',';
            $retStr.=($perm->isGroupPermission()?$groups[array_search($perm->getWorkbookOrDataSourceID(),$clsUser->getGroups())]->getName():'').','.$value.',';
            $retStr.=(array_key_exists($index,$perm->getAllPermissions())?($perm->getAllPermissions()[$index]=='Allow'?8:2):0).','
                .(array_key_exists($index,$perm->getAllPermissions())?$perm->getAllPermissions()[$index]:'None').',""'.PHP_EOL;
                
        } else {
            $retStr.=$start.'"'.$object->getName().'",'.$object->getId().','.$object->getContentUrl().','.($perm->isGroupPermission()?'Group':'User').',';
            $retStr.=($perm->isGroupPermission()?$groups[array_search($perm->getWorkbookOrDataSourceID(),$clsUser->getGroups())]->getName():'').','.$value.',';
            $retStr.='8,Allow,Project Leader'.PHP_EOL;
            
        }
        
        
    }
    return $retStr;
}
function buildProjectOwnerObjectPermissions( $clsUser, $siteID,$perm,$groups, $objectType,$object,$date){
    
    $retStr="";
    $start = $date.','.$siteID.','.$clsUser->getName().','.$clsUser->getId().','.$clsUser->getSiteRole().','.$objectType.','.$object->getProjectID().',';
    
    $objPerm = ($objectType==ClassConstants::ObjectDataSource?ClassConstants::RESTAPIDatasourcePermissions:ClassConstants::RESTAPIWorkbookPermissions);
    
    foreach ($objPerm as $index => $value) {
        $retStr.=$start.'"'.$object->getName().'",'.$object->getId().','.$object->getContentUrl().',Project Owner,';
        $retStr.='Project Owner,'.$value.',';
        $retStr.=  ($value=='View' || $value=='Download'?8:0).','. ($value=='View' || $value=='Download'?'Allow':'None').','.'Project Owner'.PHP_EOL;
        
    }
    
    return $retStr;
    
}


function writeToFile($filePath,$data, $logMessage,$appendData){
    
    $file = fopen($filePath, ($appendData?'a':'w'));
    if ($file) {
        fwrite($file, $data);
        fclose($file);
        debug_to_console($logMessage);
    }
    
}

function getFilePath($fileName,$iniPropertiesFile, &$alreadyExists=false,$extension = ".csv")
{
    if (key_exists($fileName, $iniPropertiesFile)) {
        $file = $iniPropertiesFile[$fileName];
        
        $alreadyExists=file_exists($file);
        // test can create
        $open = fopen($file, 'a');
        if ($open) {
            fclose($open);
            return $file;
        }
        debug_to_console("Unable to create file specified in ini file for varaible [" . $fileName . "]. File path:[" . $file . "]. Defaulted to:" . getcwd() . "\\" . $fileName . $extension);
    }
    $file = getcwd() . "\\" . $fileName . $extension;
    $alreadyExists=file_exists($file);
    return $file;
}



