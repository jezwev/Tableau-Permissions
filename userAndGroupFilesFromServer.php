<?php
$doNotDie = true;
require_once 'ListSitesGroupsUserWorkbooks.php';
// generate csv files of server content, the above builds all we need



$Site_Users="Site,User,Role,Email,Password" . PHP_EOL;
$Site_Group_Users="Site,Group,User" . PHP_EOL;

// List of user by site
foreach ($siteData as $x => $site) {
    
    $UserOnSite = $site->getUsers();
    foreach ($UserOnSite as $y => $user) {
        $Site_Users .= $site->getName() . ',' . $user->getFileData() . PHP_EOL;
    }
}

$filePath = getFilePath("serverUsers");
$file = fopen($filePath, 'w');
if ($file) {
    fwrite($file, $Site_Users);
    fclose($file);
    debug_to_console("serverUsers written to " . $filePath);
}

//for groups
foreach ($siteData as $x => $site) {
    
    $GroupsOnSite = $site->getGroups();
    $UserOnSite = $site->getUsers();
    foreach ($GroupsOnSite as $y => $group) {
        $Site_Group_Users.=$group->getFileData( $site->getName());
    }
    
}

$filePath = getFilePath("serverGroupUsers");
$file = fopen($filePath, 'w');
if ($file) {
    fwrite($file, $Site_Group_Users);
    fclose($file);
    debug_to_console("serverGroupUsers written to " . $filePath);
}

die();

function getFilePath($fileName, $extension = ".csv")
{
    if (key_exists($fileName, $GLOBALS['iniPropertiesFile'])) {
        $file = $GLOBALS['iniPropertiesFile'][$fileName];
        // test can create
        $open = fopen($file, 'w');
        if ($open) {
            fclose($open);
            return $file;
        }
        debug_to_console("Unable to create file specified in ini file for varaible [" . $fileName . "]. File path:[" . $file . "]. Defaulted to:" . getcwd() . "\\" . $fileName . $extension);
    }
    
    return getcwd() . "\\" . $fileName . $extension;
}