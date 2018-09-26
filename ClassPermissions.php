<?php
/*

 * restapi /  Server
AddComment	Add Comment
ChangeHierarchy	Move
ChangePermissions	Set Permissions
Connect	Connect
Delete	Delete
ExportData	View Summary Data
ExportImage	Export Image
ExportXml	Download
Filter	Filter
InheritedProjectLeader	Project Leader
ProjectLeader	Project Leader
Read	View
ShareView	Share Customized
ViewComments	View Comments
ViewUnderlyingData	View Underlying Data
WebAuthoring	Web Edit
Write	Save
 * 
 */


/**
 * 
 * @author Jeremy
 *Permissions are saved with the object, workbook or datasource, so we do not need to identify the object
 */
class ClassPermissions
{
    public const _CSVHeader ="SiteId,ProjectName,ProjectId,Description,ContentPermissions";
   
    private $userId = "";
    private $groupId = "";
    private $name="";
    private $workbookOrDataSourceID="";
    private $permissions = array();
    
    
    public function __construct( $userId = "",$groupId="")
    {
       
        $this->userId = $userId;
        $this->groupId = $groupId;
        $this->workbookOrDataSourceID=($this->isGroupPermission()?$groupId:$userId);
        
    }
    
    public function getCSVData($siteID){
        return $siteID.','. $this->name.','.$this->id.','.$this->Description.','.$this->ContentPermissions.PHP_EOL;
    }
    
    public function getWorkbookOrDataSourceID()
    {
        return (string) $this->workbookOrDataSourceID;
    }
    
    public function isGroupPermission(){
        
        return strlen($this->groupId)>0;
        
    }
    
    
    public function getUserID(){
        return (string) $this->userId;
    }
    
    public function getGroupID(){
        return (string) $this->groupId;
    }
    
    public function addPermission($key,$value){
        $this->permissions[$key] = (string) $value;
    }
  
    public function getAllPermissions(){
        
        return $this->permissions;
    }
}