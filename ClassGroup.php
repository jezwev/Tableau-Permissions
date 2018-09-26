<?php
require_once 'ClassGenericFunctions.php';

class ClassGroup extends ClassGenericFunctions
{

    public const _CSVHeader = "TimeStamp,SiteId,GroupName,GroupId,UserID";

    private $users = array();

    // holds workbooks groups can see, and permissions on them
    private $workbooks = array();

    public function __construct($groupName, $id)
    {
        $this->name = $groupName;
        $this->id = $id;
    }

    public function getCSVData($siteID, $users,$date)
    {
        $csv = "";
        foreach ($this->users as $value) {
            //guest user account can appear, and not be valid, so catch
            try{
                if (key_exists(strtolower($value), $users))
                 $csv .=$date.','. $siteID . ',' . $this->name . ',' . $this->id . ',' . $users[strtolower($value)]->getID() . PHP_EOL;
            }
            catch (Exception $e){
                debug_to_console("Exception in ClassGroup getCSVData() for ".$this->name);
            }
            catch (Error $e){
                debug_to_console("Error in ClassGroup getCSVData() for ".$this->name);
            }
            
        }
        
        return $csv;
    }

    
    public function getFileData($siteName)
    {
        $csv = "";
        foreach ($this->users as $value) {
            $csv .= $siteName . ',' . $this->name . ',' . $value . PHP_EOL;
        }
        
        return $csv;
    }

    public function getUsers()
    {
        return $this->users;
    }

    // Note store Name key in lower case as server names are not case sensitive
    // so b==B, so if user name in File is different case from server, could course some problems
    public function addUser($userName)
    {
        $this->users[strtolower($userName)] = (string) $userName;
    }

    public function removeUser($userName)
    {
        unset($this->users[strtolower($userName)]);
    }

    public function addWorkbook($ClassWorkbook, $workbookId)
    {
        $this->workbooks[strtolower($workbookId)] = $ClassWorkbook;
    }

    public function removeWorkbook($workbookId)
    {
        unset($this->workbooks[strtolower($workbookId)]);
    }

    public function getWorkbooks()
    {
        return $this->workbooks;
    }
}