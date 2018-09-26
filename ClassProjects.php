<?php

require_once 'ClassGenericFunctions.php';

class ClassProjects extends ClassGenericFunctions
{
    public const _CSVHeader ="TimeStamp,SiteId,ProjectName,ProjectId,Description,ContentPermissions,ParentID,OwnerID,ParentPath";

    public const _CSVHeaderPermissions ="TimeStamp,SiteId,ProjectName,ProjectId,GroupOrUser,GroupOrUserID"; 
    
    private $Description = "";
    private $ContentPermissions = "";
    private $parentId="";
   
    private $parentPath = "";
    

    //Store permissions set at the proecjt level for Workbooks and data sources seperatetly
    
   
    
    public function __construct($projectName,$id,$Description,$ContentPermissions, $parentID, $ownerID)
    {
        $this->name = $projectName;
        $this->id = $id;
        $this->Description = $Description;
        $this->ContentPermissions = $ContentPermissions;
        $this->parentId = $parentID;
        //if Project owner can view and download content
        $this->ownerID = $ownerID;
      
        
    }
    
    public function getCSVData($siteID,$date,&$projects){
        /*
         * Description may have line feeds, replace with Strings
         */
        $desc= str_replace(array("\r", "\n"), ' ', $this->Description);
        $desc= str_replace('"', '""', $desc);
        //recursive call, that calls each parent to see if they have a parent
        $fullParentPath = $this->getFullParentPath($projects);
        if(strlen($fullParentPath)>0)
            $fullParentPath=substr($fullParentPath, 0,strlen($fullParentPath)-2);
        
        return $date.','.$siteID.',"'. $this->name.'",'.$this->id.',"'.$desc.'",'.$this->ContentPermissions.','
            .$this->parentId.','.$this->ownerID.',"'.$fullParentPath.'"'.PHP_EOL;
    }
    
    public function getCSVDataWithGroupsAndUser($siteID,$date,&$projects,$users,$groups){
       
        //recursive call, that calls each parent to see if they have a parent
        $fullParentPath = $this->getFullParentPath($projects);
        if(strlen($fullParentPath)>0)
            $fullParentPath=substr($fullParentPath, 0,strlen($fullParentPath)-2);
            
        //loop through permissions for Porject (only for users and groups, not memembers of these groups
            $rtn="";
            foreach ($this->getPermissions() as $perm) {
            
                $userOrGroup;    
                if($perm->isGroupPermission())
                        $userOrGroup=$groups[getNameFromID($perm->getWorkbookOrDataSourceID(), $groups)];
                 else
                        $userOrGroup = $users[getNameFromID($perm->getWorkbookOrDataSourceID(), $users)];
                
                                
                 $rtn.=  $date.','.$siteID.',"'. $this->name.'",'.$this->id.','.($perm->isGroupPermission()?'Group':'User').",".$userOrGroup->getId().PHP_EOL;
            
             }
             return $rtn;   
    }
    
    
    public function getFullParentPath($projects, $breadCrumb=""){
        
        if (strlen($this->parentId)<1 ){
            if(strlen($breadCrumb)<1)
                    return "";
             else  return '['.$this->getName().']>>'.$breadCrumb;     
                  
        }
      
        //we need to build this, it maybe more than one than one parent, 
        //by asking parent for full path, it will get its own, and ask it it has a parent (in theory!)
        foreach ($projects as $y => $proj) {
            if ($proj->getId()==$this->parentId){
                    //refresh it's the parents, parent
                   return $proj->getFullParentPath($projects,'['.$this->getName().']>>'.$breadCrumb);
            }
        }
        
            
    }
    
    public function getParentId(){
        
        return (string) $this->parentId;
    }
  
    
}