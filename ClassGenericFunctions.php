<?php
class ClassGenericFunctions {
    
    private $thisClass;
    protected $usersWhoCanView = array();
    protected $permissions=array();
    protected $name = "";
    protected $id = "";
    protected $totalViewCount=0;
    protected $viewContentURL="";
    protected $ownerID="";
    
    public function __construct() {
        
        $this->thisClass = get_class($this);
    }
    
    
    public function getName()
    {
        return (string) $this->name;
    }
    public function getOwnerID()
    {
        return (string) $this->ownerID;
    }
    
    public function getLowerCaseName()
    {
        return strtolower($this->name) ;
    }
    
    public function getId()
    {
        return (string)$this->id;
    }
    
    //permissions on objects
    public function getPermissions(){
        
        return $this->permissions;
    }
    
    public function addPermissions($perm,$userORGroupID){
        $this->permissions[strtolower($userORGroupID)]=$perm;
    }
    
    public function addUser($userName)
    {
        $this->usersWhoCanView[strtolower($userName)] = (string) $userName;
    }
    
    public function removeUser($userName)
    {
        unset($this->usersWhoCanView[strtolower($userName)]);
    }
    public function getUsers()
    {
        return $this->usersWhoCanView;
    }
}