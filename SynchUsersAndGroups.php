<?php

require_once 'ClassUser.php';
require_once 'ClassGroup.php';
require_once 'RestCalls.php';
require_once 'ClassSites.php';
require_once 'SharedFunctions.php';

/*  
 * files with Users, and Group + user are used to update the server, so that the 
 * server will mirror the content of these files.
 * WARNING Groups and users on the server NOT in the files will be permanetly deleted
 * The properties ini file has flags that need to be explicitely set to True for the 
 * User or Groups to be deleted
 * removeUsers = false
   removeGroups = false
   If not True, then the Delete or Add action will appear in the log as
   ***test Mode, action not performed:
 * */




// pass in properties file location as first parameter on cmdline
if (! key_exists(1, $argv)) {
    debug_to_console("Pass the location of the properties as the first parameter on the cmd line.\n i.e php.exe SynchUsersAndGroups.php c:\\temp\\properties.ini");
    die();
}

$file_path = (string) $argv[1];
$iniPropertiesFile = readIniFile($file_path);
testIfReturnValueIsAnError($iniPropertiesFile);
debug_to_console("Properties ini file read in <" . $file_path . ">. It has " . count($iniPropertiesFile) . " values");

$APIVersion = $iniPropertiesFile["APIVersion"];
$username = $iniPropertiesFile["username"];
$password = $iniPropertiesFile["password"];
$siteName = (string) $iniPropertiesFile["siteName"];
$URL = $iniPropertiesFile["URL"];
// add or remove flags need to be set to true for this to happen

$doIt['add']['user'] = key_exists("addUsers", $iniPropertiesFile) ? $iniPropertiesFile["addUsers"] : FALSE;
$doIt['add']['group'] = key_exists("addGroups", $iniPropertiesFile) ? $iniPropertiesFile["addGroups"] : FALSE;
$doIt['remove']['user'] = key_exists("removeUsers", $iniPropertiesFile) ? $iniPropertiesFile["removeUsers"] : FALSE;
$doIt['remove']['group'] = key_exists("removeGroups", $iniPropertiesFile) ? $iniPropertiesFile["removeGroups"] : FALSE;

// store values returned from initial sign in
// Note for SAML authentication, you need to sign in as a non SAML user as we cannot authenticate against the SAML provider (you will have created a Tableau Server admin user is not part of SAML)
$authToken = '';
$siteToken = '';

/**
 * *********************************************************
 * Users
 * Synch users in file from properties file,  with server.
 * Add users not on server, and remove users on server not in file
 * *********************************************************
 */

//Holds users and groups for each site. Array of ClassSites
$serverSites=array();
//holds data for each file of users and groups, which are prefaced by site
$fileSites = array();



/**
 * *********************************************************
 * Groups
 * Synch groups in file specified in properties file with server.
 * Add groups not on server, and remove groups on server not in file
 * Format groupname, user
 * Means we have a list of users for each ClassGroup, that we will also synch
 * *********************************************************
 */



readInUsersToSynchFile($iniPropertiesFile["userFile"], $fileSites);
readInGroupsAndUsersFromSynchFile($iniPropertiesFile["groupFile"], $fileSites);

// sign in to server
curlTableauToken($username, $password, $URL, $siteName);
$urlPreface = $URL . $APIVersion."sites";

/*need to get list of sites from fileSites, and then get the users for each site
so we can compare
Read in groups after we have removed users, or else add logic when removing user from site 
that we remove all group entries, so our Server data structure reflects current state.
*/


getListOfUserAndGroupsForSite($urlPreface, $authToken,$siteToken, $serverSites, $fileSites);

compareServerUsersWithFileUsers($serverSites,$fileSites , $urlPreface, $authToken,$siteToken, $doIt);


//
compareServerGroupsWithFileGroups($serverSites, $fileSites, $urlPreface, $authToken, $siteToken, $doIt);

/*
 * Compares File Groups With Server ClassGroup
 * Then compares users for each File ClassGroup with Server ClassGroup Users
 * Server will end up matching file
 */
function compareServerGroupsWithFileGroups(&$serverSitesData, $fileSites, $urlPreface, &$authToken, &$siteToken,$doIt)
{
    
    
    //need to loop through each Site found in the files
    
    // Proof we have all the Groups and can list through them
    foreach ($fileSites as $x => $fileSite) {
        //only get this if Site is in files we are synching with
        if ( !array_key_exists($fileSite->getLowerCaseName(),$serverSitesData )) {
            
            debug_to_console("ERROR: Unable to find site:[".$fileSite->getName()."] at the server.",TRUE );
        }
        else {
            
            $serverSite = $serverSitesData[$fileSite->getLowerCaseName()];
            
            //need to switch to the site to access values
            if($siteToken!=(string)$serverSite->getId()){
                
                //cannot switch to site already logged on to, so check the $siteToken
                //gets a new authentication token
                
                $payLoad = '<tsRequest><site contentUrl="' . ((string) $serverSite->getContentURL()) . '" /></tsRequest>';
                $xml = curlTableauPost(substr($urlPreface,0,strlen($urlPreface)-5).'auth/switchSite', $authToken, $payLoad);
                if(stripos($xml, "error") === 0) {
                    debug_to_console("Error: Unable to switch sites.....! ".$xml);
                    die();
                }
                
                $authToken = (string) $xml->credentials->attributes()->token;
                $siteToken =(string) $xml->credentials->site->attributes()->id;
            }
            
            // see if need to add Group to Server
            foreach ($fileSite->getGroups() as $x => $fs_ClassGroup) {
                if (! array_key_exists($fs_ClassGroup->getLowerCaseName(), $serverSite->getGroups())) {
                    
                    addOrRemoveGroups($urlPreface, $authToken,$fs_ClassGroup, $serverSite, true, $doIt);
                    
                }
            }
      
             // now see if we need to remove Group from the server
            foreach ($serverSite->getGroups() as $x => $fs_ClassGroup) {
               
                if (! array_key_exists($fs_ClassGroup->getLowerCaseName(), $fileSite->getGroups())) {
                    addOrRemoveGroups($urlPreface, $authToken,$fs_ClassGroup, $serverSite, false, $doIt);
                }
            }
            
//           now loop through each group and check the users
            compareServerGroupUsersWithGroupFileUsers($urlPreface.'/'.$siteToken, $authToken, $serverSite,$fileSite, $doIt);
               //ensure changes are in $serverData
            $serverSitesData[$fileSite->getLowerCaseName()]=$serverSite;
            
        }
        
    }
    
    
    
  
}

/*
 * Compare users in each ClassGroup, Groups have already been synched
 */
function compareServerGroupUsersWithGroupFileUsers($urlPreface, $authtoken,  &$serverSite,$fileSite, $doIt)
{
    
    
         
        // see if need to add users to Server
         foreach ($fileSite->getGroups() as $x => $group) {
            //now loop through the users in the Groups
            foreach ($group->getUsers() as $x => $user) {
                if (! key_exists(strtolower($user),  $serverSite->getGroups()[strtolower( $group->getName())]->getUsers())) {
                    addOrRemoveGroupUsers($urlPreface, $authtoken, $user ,$serverSite->getGroups()[strtolower( $group->getName())], $serverSite, true, $doIt);
                }
            }
        }
        
        // now see if we need to remove users from the server
        // see if need to add users to Server
        foreach ($serverSite->getGroups() as $x => $group) {
            //now loop through the users in the Groups
            foreach ($group->getUsers() as $x => $user) {
                //
                if ($group->getName()!="All Users" && !is_null($fileSite->getGroups()[strtolower( $group->getName())])){
                    if (! key_exists(strtolower($user),  $fileSite->getGroups()[strtolower( $group->getName())]->getUsers())) {
                        addOrRemoveGroupUsers($urlPreface, $authtoken, $user ,$serverSite->getGroups()[strtolower( $group->getName())], $serverSite, false, $doIt);
                    }
                }
            }
        }
        
        
     
}

/*
 * $usersToSynch is a complete list of all users, so ADD ones not on server
 * and REMOVE server users not in list
 * Update $userdata to reflect new users, and removed users, so it holds the current list of users
 */
function compareServerUsersWithFileUsers(&$serverSitesData, $fileSites, $urlPreface, &$authToken, &$siteToken,$doIt)
{
    //need to loop through each Site found in the files
    
    // Proof we have all the Groups and can list through them
    foreach ($fileSites as $x => $fileSite) {
        //only get this if Site is in files we are synching with
        if ( !array_key_exists($fileSite->getLowerCaseName(),$serverSitesData )) {
            
            debug_to_console("ERROR: Unable to find site:[".$fileSite->getName()."] at the server.",TRUE );
        }
        else {
            
            $serverSite = $serverSitesData[$fileSite->getLowerCaseName()];
            
            //need to switch to the site to access values
            if($siteToken!=(string)$serverSite->getId()){
                
                //cannot switch to site already logged on to, so check the $siteToken
                //gets a new authentication token
                
                $payLoad = '<tsRequest><site contentUrl="' . ((string) $serverSite->getContentURL()) . '" /></tsRequest>';
                $xml = curlTableauPost(substr($urlPreface,0,strlen($urlPreface)-5).'auth/switchSite', $authToken, $payLoad);
                if(stripos($xml, "error") === 0) {
                    debug_to_console("Error: Unable to switch sites.....! ".$xml);
                    die();
                }
                
                $authToken = (string) $xml->credentials->attributes()->token;
                $siteToken =(string) $xml->credentials->site->attributes()->id;
            }
            //for each site compare compare users
            // see if need to add users to Server ...data held in ClassUser
            foreach ($fileSite->getUsers() as $x => $fs_ClassUser) {
                if (! array_key_exists(strtolower($fs_ClassUser->getName()), $serverSite->getUsers())) {
                    
                    addOrRemoveUsers($urlPreface ."/".$siteToken, $authToken, $fs_ClassUser, $serverSite, true, $doIt);
                   
                }
            }
            //ensure changes are in $serverData
            $serverSitesData[$fileSite->getLowerCaseName()]=$serverSite;
            
            // now see if we need to remove users from the server
            foreach ($serverSite->getUsers() as $x => $fs_ClassUser) {
                if (! array_key_exists(strtolower($fs_ClassUser->getName()), $fileSite->getUsers())) {
                    // do not remove server admin
                    if ((string) $fs_ClassUser->getSiteRole() != 'ServerAdministrator')
                        
                        addOrRemoveUsers($urlPreface ."/".$siteToken, $authToken, $fs_ClassUser, $serverSite, false,  $doIt);
                }
            }
            
            //ensure changes are in $serverData
            $serverSitesData[$fileSite->getLowerCaseName()]=$serverSite;
            
        }
        
    }
    
}



function addOrRemoveUsers($urlPreface, $token, $fs_ClassUser, &$serverSite, $add = true,  $doIt)
{
    // try and add a user (or delete if they exist)
    $nameToPlayWith = $fs_ClassUser->getName();;
    
    if (array_key_exists(strtolower($nameToPlayWith), $serverSite->getUsers()) && ! $add) {
        // delete
        if ($doIt['remove']['user'] == true) {
            
            $reply = curlTableauDelete($urlPreface . "/users/" . ($serverSite->getUsers()[$fs_ClassUser->getLowerCaseName()]->getId()), $token);
            // now remove user from UserData
            if ($reply == 'OK') {
                debug_to_console('Site: ['.$serverSite->getContentURL().'], User [' . $nameToPlayWith . '] removed from Site',TRUE);
                $serverSite->removeFromUsers( $nameToPlayWith,true);
            } else {
                debug_to_console('Error: Site: ['.$serverSite->getContentURL().'], User [' . $nameToPlayWith . '] NOT removed from Site. Error:' . $reply);
            }
        } else {
            $serverSite->removeFromUsers( $nameToPlayWith,true);
            debug_to_console('***test Mode, action not performed: Site: ['.$serverSite->getContentURL().'], User [' . $nameToPlayWith . '] removed from Site',TRUE);
        }
    } elseif ($add) {
        // add
        $payload = '
                  <tsRequest>
                   <user name="' . $nameToPlayWith . '"
                        siteRole="' . $fs_ClassUser->getSiteRole() . '"
                        authSetting="ServerDefault" />
                  </tsRequest>
                  ';
        
        if ($doIt['add']['user'] == true) {
            $xml = curlTableauPost($urlPreface . "/users", $token, $payload);
            // now add new user to userdata
            if(stripos($xml, "error") !== 0) {
                //__construct($name, $id, $siteRole,$email="",$lastLogin="",$password="",$fullName="")
                $userClass = new ClassUser($xml->user->attributes()->name, $xml->user->attributes()->id, $xml->user->attributes()->siteRole,$fs_ClassUser->getEmail(),
                    "",$fs_ClassUser->getPassword(), $fs_ClassUser->getFullName());
                $serverSite->addToUsers( $userClass);
                debug_to_console('Site: ['.$serverSite->getContentURL().'], User: [' . $nameToPlayWith . '], added to Site as: [' . $fs_ClassUser->getSiteRole().']',TRUE);
                
                //see if need to set password and email (if not SAML or AD)
                updateUser($userClass, $urlPreface. "/users/".$userClass->getId(), $token,$serverSite->getContentURL());
            }
            else {
                debug_to_console('Error: Site: ['.$serverSite->getContentURL().'], User: [' . $nameToPlayWith . '], NOT added to Site as: [' . $fs_ClassUser->getSiteRole().']. Error:'.$xml,TRUE);
                
            }
            
        } else {
            $userClass = new ClassUser($nameToPlayWith, "", $role,"");
            $serverSite->getUsers()[strtolower($userClass->getName())] = $userClass;
            debug_to_console('***test Mode, action not performed: Site: ['.$serverSite->getContentURL().'], User: [' . $nameToPlayWith . '], added to Site as: [' . $fs_ClassUser->getSiteRole().']',TRUE);
        }
    }
}

/*
 *
 *
 *
 */



function addOrRemoveGroups($urlPreface, $token, $fs_ClassGroup, &$serverSite, $add = true,$doIt)
{
    $urlPreface.="/".$serverSite->getId();
    if (! $add) {
        
        // delete
        if ($doIt['remove']['group'] == true) {
            $reply = curlTableauDelete($urlPreface . "/groups/" . ($serverSite->getGroups()[$fs_ClassGroup->getLowerCaseName()]->getId()), $token);
            // now remove group from $groupData
            if ($reply == 'OK') {
                debug_to_console('Site: ['.$serverSite->getContentURL().'], Group [' . $fs_ClassGroup->getName() . '] removed from Site',true);
                $serverSite->removeFromGroups($fs_ClassGroup);
            } else {
                debug_to_console('Error: Site: ['.$serverSite->getContentURL().'], Group [' . $fs_ClassGroup->getName() . '] NOT removed from Site. ' . $reply);
            }
        }else{
            $serverSite->removeFromGroups($fs_ClassGroup);
            debug_to_console('***test Mode, action not performed: Site: ['.$serverSite->getContentURL().'], Group [' . $fs_ClassGroup->getName() . '] removed from Site',TRUE);
            
        }
        
        
    } elseif ($add) {
        // add
        $payload = '
                  <tsRequest>
                   <group name="' . $fs_ClassGroup->getName() . '" />
                  </tsRequest>
                  ';
        
        if ($doIt['add']['group'] == true) {
            $xml = curlTableauPost($urlPreface . "/groups", $token, $payload);
            // now add new user to userdata
            if(stripos($xml, "error") !== 0) {
                $groupClass = new ClassGroup($xml->group->attributes()->name, $xml->group->attributes()->id);
                $serverSite->addToGroups($groupClass);
                debug_to_console('Site: ['.$serverSite->getContentURL().'], Group [' . $fs_ClassGroup->getName() . '] added to Site',TRUE);
            }
            else {
                debug_to_console('Error: Site: ['.$serverSite->getContentURL().'], Group [' . $fs_ClassGroup->getName() . '] NOT added to Site. '.$xml,TRUE);
                
            }
        }else {
            $groupClass = new ClassGroup($fs_ClassGroup->getName(), "");
            $serverSite->addToGroups($groupClass);
            debug_to_console('***test Mode, action not performed: Site: ['.$serverSite->getContentURL().'], Group [' . $fs_ClassGroup->getName() . '] added to Site',TRUE);
            
            
        }
    }
}



function addOrRemoveGroupUsers($urlPreface, $token, $userName, $fs_ClassGroup, &$serverSite, $add, $doIt)
{
    
    
    if ($add && !key_exists(strtolower( $userName),  $serverSite->getUsers())){
        debug_to_console("Warning: Site [".$serverSite->getContentURL()."], User[".$userName."] could not be added to Group [".$fs_ClassGroup->getName()."] as the user is not on the Site");
        return;
    }
    
    $groupID= $serverSite->getGroups()[$fs_ClassGroup->getLowerCaseName()]->getId();
    $userID = $serverSite->getUsers()[strtolower($userName)]->getId();
    
    if (! $add) {
        
        if ($doIt['remove']['group'] == true) {
            // delete
            $reply = curlTableauDelete($urlPreface . "/groups/" . $groupID . '/users/' . $userID, $token);
            // now remove group from $groupData
            if ($reply == 'OK') {
                debug_to_console('Site ['.$serverSite->getContentURL().'],  Group [' . $fs_ClassGroup->getName() . '], User [' . $userName . '] removed.',TRUE);
                $serverSite->getGroups()[$fs_ClassGroup->getLowerCaseName()]->removeUser($userName);
            } else {
                debug_to_console('Error: Site ['.$serverSite->getContentURL().'],  Group [' . $fs_ClassGroup->getName() . '], User [' . $userName . '] NOT removed.' . $reply);
            }
        }
        else{
            $groupData[$groupName]->removeUser($userName);
            debug_to_console('***test Mode, action not performed: Site ['.$serverSite->getContentURL().'],  Group [' . $fs_ClassGroup->getName() . '], User [' . $userName . '] removed.',TRUE);
        }
    } elseif ($add) {
        //test user exists on server
        
            // add
            $payload = '
                      <tsRequest>
                       <user id="' . $userID . '" />
                      </tsRequest>
                      ';
            if ($doIt['add']['group'] == true) {
                
                $xml = curlTableauPost($urlPreface . "/groups/" . $groupID . '/users', $token, $payload);
                // now add new user to userdata
                if(stripos($xml, "error") !== 0) {
                    // add this user to the group
                    $serverSite->getGroups()[$fs_ClassGroup->getLowerCaseName()]->addUser($userName);
                    debug_to_console('Site ['.$serverSite->getContentURL().'],  Group [' . $fs_ClassGroup->getName() . '], User [' . $userName . '] added.',TRUE);
                }
                else {
                    debug_to_console('Error: Site ['.$serverSite->getContentURL().'],  Group [' . $fs_ClassGroup->getName() . '], User [' . $userName . '] NOT removed.'.$xml,TRUE);
                    
                }
            }
            else {
                $groupData[(string) $groupName]->addUser($userName);
                debug_to_console('***test Mode, action not performed: Site ['.$serverSite->getContentURL().'],  Group [' . $fs_ClassGroup->getName() . '], User [' . $userName . '] removed.',TRUE);
            }
        
    }
}



function readInCSVFileToArray($filePath)
{
    try {
        $csvArray = array();
        $file = fopen($filePath, "r");
        if (! $file) {
            throw new Exception("Could not open the file <" . $filePath . ">!");
        }
        while (! feof($file)) {
            array_push($csvArray, fgetcsv($file, 0, ",", '"'));
        }
        
        fclose($file);
        return $csvArray;
    } catch (Exception $e) {
        return ("Error (File: " . $e->getFile() . ", line " . $e->getLine() . "): " . $e->getMessage());
    }
}

function readInUsersToSynchFile($filePath, &$SitesFiledata)
{
    // contains username and siteRole
    $usersToSynch = readInCSVFileToArray($filePath);
    testIfReturnValueIsAnError($usersToSynch, "Error reading Users file");
    
    /*
     * File has data in format
     * Site,User,Role,Email,Password 
     * Email and password may not be set, so check
     */
    
    // now place this data in to ClassUser format
    //file has header
    for ($x = 1; $x < count($usersToSynch); $x ++) {
        // check not empty line
        if(array_key_exists($x, $usersToSynch)&& array_key_exists(1, $usersToSynch[$x])){
            
            //see if the Site already exists
            if (!array_key_exists(strtolower($usersToSynch[$x][0]), $SitesFiledata) )
                $SitesFiledata[strtolower($usersToSynch[$x][0])]=new ClassSites($usersToSynch[$x][0]);
            //this is the current site
            $currentSite = $SitesFiledata[strtolower($usersToSynch[$x][0])];
            
            
            if (array_key_exists($x, $usersToSynch) && array_key_exists(1, $usersToSynch[$x]) && array_key_exists(2, $usersToSynch[$x])) {
                //make sure does not already exist
                $siteUsers = $currentSite->getUsers();
                if (!array_key_exists(strtolower($usersToSynch[$x][1]), $siteUsers)){
                    $userClass = new ClassUser($usersToSynch[$x][1], "", $usersToSynch[$x][2],array_key_exists(3, $usersToSynch[$x])?$usersToSynch[$x][3]:"","",array_key_exists(4, $usersToSynch[$x])?$usersToSynch[$x][4]:"");
                    $siteUsers[strtolower($userClass->getName())] = $userClass;
                    $currentSite->addUsers($siteUsers);
                    $SitesFiledata[strtolower($usersToSynch[$x][0])]=$currentSite;
                }
            }
        }
    }
}

function testIfReturnValueIsAnError($val, $tagLine = "")
{
    if (is_string($val) && strtoupper(substr($val, 0, 5)) == "ERROR") {
        debug_to_console($tagLine . ': ' . $val);
        die();
    }
}

function readInGroupsAndUsersFromSynchFile($filePath, &$SitesFiledata)
{
    // contains site, group,username
    $groupsToSynch = readInCSVFileToArray($filePath);
    testIfReturnValueIsAnError($groupsToSynch, "Error reading Groups, and users in Groups, file");
    // now place this data in to ClassGroup format, for each site
    // Same group name can occur multiple times, once for each user in group
    //File has headers, so start on 2nd line
    for ($x = 1; $x < count($groupsToSynch); $x++) {
        // check not empty line
        if (array_key_exists($x, $groupsToSynch) && array_key_exists(0, $groupsToSynch[$x]) && array_key_exists(1, $groupsToSynch[$x])&& array_key_exists(2, $groupsToSynch[$x])) {
            
            //see if the Site already exists
            if (!array_key_exists(strtolower($groupsToSynch[$x][0]), $SitesFiledata))
                $SitesFiledata[strtolower($groupsToSynch[$x][0])]=new ClassSites($groupsToSynch[$x][0]);
           
            //this is the Groups for the current site
            $siteGroups = $SitesFiledata[strtolower($groupsToSynch[$x][0])]->getGroups();
                 
            if (! array_key_exists(strtolower( $groupsToSynch[$x][1]), $siteGroups)) {
                $groupClass = new ClassGroup($groupsToSynch[$x][1], "");
                $siteGroups[$groupClass->getLowerCaseName()] = $groupClass;
            }
            $groupClass=$siteGroups[strtolower($groupsToSynch[$x][1])];
            //now get back the groupClass and add user
            $groupClass->addUser($groupsToSynch[$x][2]);
            $siteGroups[strtolower($groupsToSynch[$x][1])]=$groupClass;
             
            //save back
           $SitesFiledata[strtolower($groupsToSynch[$x][0])]->addGroup($siteGroups);
          
        }
       
    }
    
}




