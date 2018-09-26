<?php

require_once 'ClassSites.php';
require_once 'ClassGroup.php';
require_once 'ClassUser.php';
require_once 'ClassWorkbook.php';
require_once 'ClassProjects.php';
require_once 'ClassPermissions.php';
require_once 'ClassDataSource.php';
require_once 'RestCalls.php';
require_once 'SharedFunctions.php';

/*
    Iterate over each site and show the 
    Users 
    Group + Users
    User + Groups
    User + Project->Workbooks
    Project->Workbook + Users
   
    All these values should be read from a properties file

 * $APIVersion="/api/3.0/";
 * $username = "";
 * $password = "";
 * $siteName = ""; // blank for default
 * $URL = "192.168.31.1"; //Tableau Server address
 */


function getDataFromServer($argv,&$siteData,&$iniPropertiesFile,&$authToken,&$siteToken,&$URL,&$APIVersion,&$urlPreface){

    // pass in properties file location as first parameter on cmdline
    if (! key_exists(1, $argv)) {
        debug_to_console("Pass the location of the properties as the first parameter on the cmd line.\n i.e php.exe generateCSVFilesForServerContent.php c:\\temp\\properties.ini");
        die();
    }
    
    $file_path = (string) $argv[1];
    $iniPropertiesFile = readIniFile($file_path);
    testIfReturnValueIsAnError($iniPropertiesFile);
    debug_to_console("Properties ini file read in <" . $file_path . ">. It has " . count($iniPropertiesFile) . " values");
    
    $APIVersion = $iniPropertiesFile["APIVersion"];
    $username = $iniPropertiesFile["username"];
    $password = $iniPropertiesFile["password"];
    $siteName = $iniPropertiesFile["siteName"];;
    $URL = $iniPropertiesFile["URL"];
    // store values returned from initial sign in
    // Note for SAML authentication, you need to sign in as a non SAML user as we cannot authenticate against the SAML provider (you will have created a Tableau Server admin user is not part of SAML)
    $authToken = '';
    $siteToken = '';
    
    // sign in to server
    curlTableauToken($username, $password, $URL,  strtolower($siteName)=='default'?'':$siteName,$APIVersion,$authToken,$siteToken);
    
    //if online or a site is specified, then only get that
    if (strpos(strtolower( $URL), "online.tableau.com")>0 || strlen($siteName)>0 || strtolower($siteName)=='default'){
        $urlPreface = $URL . $APIVersion . "sites";
        getSiteForOnline($urlPreface."/$siteToken", $authToken, $siteData);
    }
    else{
        $urlPreface = $URL . $APIVersion . "sites";
        getSites($urlPreface, $authToken, $siteData);
    }
    
    
    //now get groups for each site
    
    debug_to_console("**********************End ClassSites**********************");
    
        
    return ;
}


function testIfReturnValueIsAnError($val, $tagLine = "")
{
    if (is_string($val) && strtoupper(substr($val, 0, 5)) == "ERROR") {
        debug_to_console($tagLine . ': ' . $val);
        die();
    }
}

function collectDataForThisSite($x_value,&$siteData,&$authToken,&$siteToken,$URL,$APIVersion,$urlPreface){
    
    
    
        $local = ($x_value);
        $groupData = array();
        $userData = array();
        $workBooks=array();
        //$workBooks is for users who can see workbooks,  $workbooksAndPermissions includes permissions that stop a user from seeing a workbook
        $workbooksAndPermissions=array();
        $projectData = array();
        $dataSources=array();
        //need to switch to the site to access values
        
        if($siteToken!=(string)$local->getId()){
            
            //cannot switch to site already logged on to, so check the $siteToken
            //gets a new authentication token
            
            $payLoad = '<tsRequest><site contentUrl="' . ((string) $local->getContentURL()) . '" /></tsRequest>';
            $xml = curlTableauPost($URL.$APIVersion.'auth/switchSite', $authToken, $payLoad);
            if(stripos($xml, "error") === 0) {
                
                debug_to_console("Error: Unable to switch sites.....! ".$xml);
                die();
            }
            
            //now get new auth token
            /*
             * <tsResponse>
             <credentials token="authentication-token" >
             <site id="site-id" contentUrl="content-url" />
             <user id="user-id-of-signed-in-user" />
             </credentials>
             </tsResponse>
             *
             */
            $authToken = (string) $xml->credentials->attributes()->token;
            $siteToken =(string) $xml->credentials->site->attributes()->id;
        }
        debug_to_console("**********************Start Site [".(string) $local->getName()."]**********************");
        
        debug_to_console("**********************Listing Users*********************************");
        getServerUsers($urlPreface.'/'.$siteToken.'/', $authToken, $userData,(string) ($local->getContentURL()==""?$local->getName():$local->getContentURL()));
        $siteData[$local->getLowerCaseName()]->addUsers($userData);
        getServerGroups($urlPreface.'/'.$siteToken.'/', $authToken, $groupData,$local->getName(),$userData);
        $siteData[$local->getLowerCaseName()]->addGroup($groupData);
        $siteData[$local->getLowerCaseName()]->addUsers($userData);
        debug_to_console("**********************Listing Users and Groups they belong in*******");
        //now list for each user, what groups they are in
        listUserThenGroup($groupData,$userData);
        debug_to_console("**********************Listing Projects******************************");
        getProjects($urlPreface.'/'.$siteToken.'/', $authToken, $projectData);
        $siteData[$local->getLowerCaseName()]->addProjects($projectData);
        
        debug_to_console("**********************getting list of permissions for each project*******");
        //we need this to get Project Leader settings
        //These can override group and user permissions
        getProjectPermissions($urlPreface.'/'.$siteToken.'/', $authToken, $projectData);
        
        //now get permissions on the Datasource
        debug_to_console("**********************building list of permissions for each data source*******");
        getDatasources($urlPreface.'/'.$siteToken.'/', $authToken, $dataSources);
        getDataSourcePermissions( $urlPreface.'/'.$siteToken.'/', $authToken, $dataSources);
        $siteData[$local->getLowerCaseName()]->addDataSources($dataSources);
        
        //now get permissions on the workbooks, whereas before we were getting a list of workbooks that a user could view
        debug_to_console("**********************building list of permissions for each workbook *******");
        getSiteWorkbooks($urlPreface.'/'.$siteToken.'/', $authToken, $workbooksAndPermissions);
        getWorkbookPermissions( $urlPreface.'/'.$siteToken.'/', $authToken, $workbooksAndPermissions);
        
        debug_to_console("**********************retrieving first View for each workbook*******");
        getFirstViewForEachWorkbook($urlPreface.'/'.$siteToken.'/', $authToken, $workbooksAndPermissions);
        $siteData[$local->getLowerCaseName()]->addWorkbookPermsissions($workbooksAndPermissions);
        
        
        
        debug_to_console("**********************End Site [".(string) $local->getName()."]**********************");
    
    

    
}

