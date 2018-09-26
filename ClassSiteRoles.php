<?php
require_once 'ClassGenericFunctions.php';
class ClassSiteRoles extends ClassGenericFunctions
{
    /*
     *  Creator, Explorer, ExplorerCanPublish, ReadOnly, ServerAdministrator, SiteAdministratorExplorer, SiteAdministratorCreator, Unlicensed, or Viewer.
     
     
     * 0=Perm does not exist for this obj
     * 1=Group None, Site Role None
     * 2=Group Allow
     * 4=Group Denied
     * 8=   User None
     * 16 = user Allow
     * 32 = User Denied
     * 64 = Site Role Denied
     * 128 =Site Role gives Permission i.e Site admin
     */
    
    private $siteRole = "";
    private $objType="";
    
    private $objPermission=array();
    
    public function __construct($name, $siteRole,$objType="")
    {
        $this->name = $name;
        $this->siteRole = $siteRole;
        $this->objType=$objType;
        
    }
    
   
    public function getRolePermissions($siteRole="",$objectType){
        
        
        if ($siteRole=="")
            $siteRole=$this->siteRole;
        
            if($objectType==ClassConstants::ObjectDataSource) {
            
                switch ( $this->siteRole) {
                    case "Explorer":
                        return ClassConstants::RoleExplorerDatasourcePermission;
                    case "Viewer":
                        return ClassConstants::RoleViewerDatasourcePermission;
                    case "ExplorerCanPublish":
                        return ClassConstants::RoleExplorerCanPublishDatasourcePermission;
                    case "Creator":
                        return ClassConstants::RoleCreatorDatasourcePermission;
                    case "Unlicensed":
                        return ClassConstants::RoleUnlicensedDatasourcePermission;
                    case "Viewer":
                        return ClassConstants::RoleViewerDatasourcePermission;
                    case "Guest":
                        return ClassConstants::RoleGuestDatasourcePermission;
                        //ServerAdministrator, SiteAdministratorExplorer, SiteAdministratorCreator
                    default:
                        return ClassConstants::RoleAdminDatasourcePermission;
                        
                }
            }
            //else get Workbook permissions
            
            else {
                switch ( $this->siteRole) {
                    case "Explorer":
                        return ClassConstants::RoleExplorerWorkbookPermission;
                    case "Viewer":
                        return ClassConstants::RoleViewerWorkbookPermission;
                    case "ExplorerCanPublish":
                        return ClassConstants::RoleExplorerCanPublishWorkbookPermission;
                    case "Creator":
                        return ClassConstants::RoleCreatorWorkbookPermission;
                    case "Unlicensed":
                        return ClassConstants::RoleUnlicensedWorkbookPermission;
                    case "Viewer":
                        return ClassConstants::RoleViewerWorkbookPermission;
                    case "Guest":
                        return ClassConstants::RoleGuestWorkbookPermission;
                        //ServerAdministrator, SiteAdministratorExplorer, SiteAdministratorCreator
                    default:
                        return ClassConstants::RoleAdminWorkbookPermission;
                        
                }
                
                
                
            }
            
            
            
            
    }
    
    public function getUserSiteRolePermissionForObject($permName, $objectType){
        
        $localArr= $this->getRolePermissions("",$objectType);
        //Need to convert Permission text from how they come from API, to how we show them to user (we use same terminolgy as Web Server interface)
        
               
        //key may not exist, if so return
        if (key_exists(($permName), $localArr))
            return $localArr[($permName)];
          else return 0;
    }
    
    public function getUserSiteRolePermissionForObjectString($permName,$objectType){
        
        $localArr= $this->getRolePermissions("",$objectType);
        
        //key may not exist, if so return
        if (key_exists(strtolower($permName), $localArr)){
            
            if($localArr[strtolower($permName)]==128)
                    return 'Site Role Allows';
            return $localArr[strtolower($permName)]==1?'None':'Site Role Denied';
        }
        else return 'None';
            
    }
    
    
    public function getSiteRole()
    {
        return (string)$this->siteRole;
    }
    
    
    /**
     * When we add a permission, we compare to the current Max, if it is a user, then it trumps Group, but we subject it to
     * SiteRole Permission
     * @param String $permName
     * @param String $permValue
     * @param String $objectName
     * @param String $objID
     * @param boolean $userPerm
     *
     * 0=Perm does not exist for this obj
     * 1=Group None, Site Role None
     * 2=Group Allow
     * 4=Group Denied
     * 8=   User None
     * 16 = user Allow
     * 32 = User Denied
     * 64 = Site Role Denied
     * 128 =Site Role gives Permission i.e Site admin
     */
      
}