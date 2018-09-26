<?php
require_once 'ClassGenericFunctions.php';
class ClassUser extends ClassGenericFunctions
{
    public const _CSVHeader ="TimeStamp,SiteId,UserName,UserId,SiteRole,Email,lastLogin,DisplayName";
   
    private $fullName="";
    private $siteRole = "";
    private $email = "";
    private $lastLogin="";
    private $password="";
    //need to store a list of Groups they are members of
    private $groups = array();
    //save info about each workbook a user can see, and this also contains permissions for the workbook
    //by user and group
  
    private $workbooks =array();
    
    public function __construct($name, $id, $siteRole="",$email="",$lastLogin="",$password="",$fullName="")
    {
        $this->name = $name;
        $this->id = $id;
        $this->siteRole = $siteRole;
        $this->email = $email;
        $this->lastLogin = $lastLogin;
        $this->password = $password;
        $this->fullName=$fullName;
        
        
    }
    
    public function getCSVData(){
        return $this->name.','.$this->id.','.$this->siteRole .','.$this->email .','. $this->lastLogin.','. $this->fullName;
    }
    
    // "User,Role,Email,Password";
    public function getFileData(){
        return $this->name.','.$this->siteRole .','.$this->email .',@password';
    }
   
    
  
    public function getFullName()
    {
        return (string)$this->fullName;
    }
    
    
    public function getSiteRole()
    {
        return (string)$this->siteRole;
    }
    
    public function getEmail()
    {
        return (string)$this->email;
    }
    
    public function getPassword()
    {
        return (string)$this->password;
    }
    
    
    // Note store Name key in lower case as server names are not case sensitive
    // so b==B, so if user name in File is different case from server, could course some problems
    public function addGroup($groupName,$groupId)
    {
        $this->groups[strtolower($groupName)] = (string) $groupId;
    }
    
    public function removeGroup($groupName)
    {
        unset($this->groups[strtolower($groupName)]);
    }
    
    public function getGroups(){
        return $this->groups;
    }
    
    public function addWorkbook($ClassWorkbook,$workbookId)
    {
        $this->workbooks[strtolower($workbookId)] = $ClassWorkbook;
    }
    
    public function removeWorkbook($workbookId)
    {
        unset($this->workbooks[strtolower($workbookId)]);
    }
    
    public function getWorkbooks(){
        return $this->workbooks;
    }
}