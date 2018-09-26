<?php
class ClassConstants
{
    
    public const ObjectWorkbook="Workbook";
    public const ObjectDataSource="Data Source";
    public const ObjectProject="Project";
    
    //Permission names retreived from REST API differ from those displayed in Web UI
    public const RESTAPIWorkbookPermissions = array("AddComment"=>"Add Comment",
        "ChangeHierarchy"=>"Move",
        "ChangePermissions"=>"Set Permissions",
        "Connect"=>"Connect",
        "Delete"=>"Delete",
        "ExportData"=> "View Summary Data",
        "ExportImage"=>"Export Image",
        "ExportXml"=>"Download",
        "Filter"=>"Filter",
        "Read"=>"View",
        "ShareView"=> "Share Customized",
        "ViewComments"=> "View Comments",
        "ViewUnderlyingData"=>"View Underlying Data",
        "WebAuthoring"=>"Web Edit",
        "Write"=>"Save");
    
    public const RESTAPIDatasourcePermissions = array(
        "ChangePermissions"=> "Set Permissions",
        "Connect"=>"Connect",
        "Delete"=> "Delete",
        "ExportXml"=> "Download",
        "Read"=> "View",
        "Write"=>"Save");
 
    public const RESTAPIProjectPermissions = array(
        "ProjectLeader"=> "Project Leader",
        "Read"=> "View",
        "Write"=>"Save");
    
    
    
    /*
     *  Creator, Explorer, ExplorerCanPublish, ReadOnly, ServerAdministrator, SiteAdministratorExplorer, SiteAdministratorCreator, Unlicensed, or Viewer.
    when 0 then 'None'
    when 2 then 'Denied'
    when 4 then 'Site Role Denied'
    when 8 then 'Allow'
    when 16 'Site Role Allowed'
     */
    
    public const RoleProjectOwnerDatasourcePermission=array("View"=>8, "Connect"=>0,"Save"=>4,"Download"=>8,"Delete"=>4,"Set Permissions"=>4);
    public const RoleExplorerDatasourcePermission=array("View"=>0, "Connect"=>0,"Save"=>4,"Download"=>0,"Delete"=>4,"Set Permissions"=>4);
    public const RoleExplorerCanPublishDatasourcePermission=array("View"=>0, "Connect"=>0,"Save"=>0,"Download"=>0,"Delete"=>0,"Set Permissions"=>0);
    public const RoleCreatorDatasourcePermission=array("View"=>0, "Connect"=>0,"Save"=>0,"Download"=>0,"Delete"=>0,"Set Permissions"=>0);
    public const RoleViewerDatasourcePermission=array("View"=>0, "Connect"=>0,"Save"=>4,"Download"=>4,"Delete"=>4,"Set Permissions"=>4);
    public const RoleGuestDatasourcePermission=array("View"=>0, "Connect"=>4,"Save"=>4,"Download"=>0,"Delete"=>4,"Set Permissions"=>4);
    public const RoleUnlicensedDatasourcePermission=array("View"=>4, "Connect"=>4,"Save"=>4,"Download"=>4,"Delete"=>4,"Set Permissions"=>4);
    public const RoleAdminDatasourcePermission=array("View"=>16, "Connect"=>16,"Save"=>16,"Download"=>16,"Delete"=>16,"Set Permissions"=>16);
    
    public const RoleProjectownerWorkbookPermission =array("Add Comment"=>0,
        "Move"=>0,
        "Set Permissions"=>0,
        "Delete"=>0,
        "View Summary Data"=>0,
        "Export Image"=>0,
        "Download"=>8,
        "Filter"=>0,
        "View"=>8,
        "Share Customized"=>0,
        "View Comments"=>0,
        "View Underlying Data"=>0,
        "Web Edit"=>0,
        "Save"=>0);
    
    public const RoleGuestWorkbookPermission =array("Add Comment"=>4,
        "Move"=>4,
        "Set Permissions"=>4,
        "Delete"=>4,
        "View Summary Data"=>0,
        "Export Image"=>0,
        "Download"=>0,
        "Filter"=>0,
        "View"=>0,
        "Share Customized"=>4,
        "View Comments"=>0,
        "View Underlying Data"=>0,
        "Web Edit"=>0,
        "Save"=>4
    );
    
    
    public const RoleUnlicensedWorkbookPermission =array("Add Comment"=>4,
        "Move"=>4,
        "Set Permissions"=>4,
        "Delete"=>4,
        "View Summary Data"=>4,
        "Export Image"=>4,
        "Download"=>4,
        "Filter"=>4,
        "View"=>4,
        "Share Customized"=>4,
        "View Comments"=>4,
        "View Underlying Data"=>4,
        "Web Edit"=>4,
        "Save"=>4);
    
    public const RoleViewerWorkbookPermission =array("Add Comment"=>0,
        "Move"=>4,
        "Set Permissions"=>4,
        "Delete"=>4,
        "View Summary Data"=>0,
        "Export Image"=>0,
        "Download"=>4,
        "Filter"=>0,
        "View"=>0,
        "Share Customized"=>4,
        "View Comments"=>0,
        "View Underlying Data"=>4,
        "Web Edit"=>4,
        "Save"=>4
    );
    
    public const RoleExplorerWorkbookPermission =array("Add Comment"=>0,
        "Move"=>0,
        "Set Permissions"=>4,
        "Delete"=>4,
        "View Summary Data"=>0,
        "Export Image"=>0,
        "Download"=>0,
        "Filter"=>0,
        "View"=>0,
        "Share Customized"=>0,
        "View Comments"=>0,
        "View Underlying Data"=>0,
        "Web Edit"=>0,
        "Save"=>4);
    
    public const RoleExplorerCanPublishWorkbookPermission =array("Add Comment"=>0,
        "Move"=>0,
        "Set Permissions"=>0,
        "Delete"=>0,
        "View Summary Data"=>0,
        "Export Image"=>0,
        "Download"=>0,
        "Filter"=>0,
        "View"=>0,
        "Share Customized"=>0,
        "View Comments"=>0,
        "View Underlying Data"=>0,
        "Web Edit"=>0,
        "Save"=>0);
    
    public const RoleCreatorWorkbookPermission =array("Add Comment"=>0,
        "Move"=>0,
        "Set Permissions"=>0,
        "Delete"=>0,
        "View Summary Data"=>0,
        "Export Image"=>0,
        "Download"=>0,
        "Filter"=>0,
        "View"=>0,
        "Share Customized"=>0,
        "View Comments"=>0,
        "View Underlying Data"=>0,
        "Web Edit"=>0,
        "Save"=>0);
    
    public const RoleAdminWorkbookPermission =array("Add Comment"=>16,
        "Move"=>16,
        "Set Permissions"=>16,
        "Delete"=>16,
        "View Summary Data"=>16,
        "Export Image"=>16,
        "Download"=>16,
        "Filter"=>16,
        "View"=>16,
        "Share Customized"=>16,
        "View Comments"=>16,
        "View Underlying Data"=>16,
        "Web Edit"=>16,
        "Save"=>16);
}