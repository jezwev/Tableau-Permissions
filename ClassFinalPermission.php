<?php

/**
 * @author Jeremy
 * Used to store and compare permissions on an object, then use 
 * User, Group, Site and Project leader permissions to determine final permission 
 */
class ClassFinalPermissions{

    private $groups = array();
    private $user = array();
    private $siteRole = array();
    private $final = array();
    private $projectLeader = false;
    private $userInfo;
    private $objectType;
    private $projectOwner = false;
    
     
   
    public function __construct($userInfo,$objectType,$projectOwner)
    {
        $this->userInfo=$userInfo;
        $this->objectType=$objectType;
        $this->projectOwner=$projectOwner;
    }
    
    public function getUserInfo(){
        return $this->userInfo;
    }
    
    
    private function convertArrayText($userPerm){
        //need to convert format of string
        
        $objectArray = ($this->objectType==ClassConstants::ObjectWorkbook?ClassConstants::RESTAPIWorkbookPermissions:ClassConstants::RESTAPIDatasourcePermissions);
        $newArray=array();
        foreach ($userPerm as $key => $perm) {
            $newArray[$objectArray[$key]]=$perm;
        }
        
        return $newArray;
    }
    
    public function addUser($userPerm){
       
        $this->user=$this->convertArrayText($userPerm);
        $this->convertPermissionsToIntegerValues($this->user);
        
    }
    
    public function addSiteRole($siteRole){
        $this->siteRole=$siteRole;
    }
    
    public function setProjectLeader($projectLeader=false){
        $this->projectLeader=$projectLeader;
    }
    
    //see if already group value, if so loop through and compare each value and keep in orderof
    //Den>>Allow>>None
    public function addGroup($group){
        
        $group=$this->convertArrayText($group);
        
        $this->convertPermissionsToIntegerValues($group);
        if(count($this->groups)<1)
            $this->groups=$group;
          else {
                  
              foreach ($group as $key => $perm) {
                    //
                  if(key_exists($key, $this->groups)){
                      /*compare values
                          when 0 then 'None'
                          when 2 then 'Denied'
                          when 8 then 'Allow'
                       */
                      if($perm>$this->groups[$key])
                          $this->groups[$key]=$perm;
                      
                      
                  }else {
                      //add to array
                      $this->groups[$key]=$perm;
                      
                  }
                  
                  
              }
         }
    }
    
    private function convertPermissionsToIntegerValues(&$arr){
        
        foreach ($arr as $key => $perm){
            
            switch ($arr[$key]){
                
                case "Allow":    
                    $arr[$key]=8;
                    break;
                case "None":
                    $arr[$key]=0;
                    break;
                case "Deny":
                    $arr[$key]=2;
                
            }
            
        }
        
        
    }
         
   public function getFinalPermission(){
             
       //siterole has all keys, so we can loop through this and compare with other values
       $this->final=array();
       if ($this->userInfo->getName()=='f')
           $a=1;
       foreach ($this->siteRole as $key =>$val ) {
           
           //user beats Group, unless value is None, then Default to Group
           //but Project Leader beats user and group
           //Site Role is Cap
           /*
            *   when 0 then 'None'
                when 2 then 'Denied'
                when 4 then 'Site Role Denied'
                when 8 then 'Allow'
                when 16 'Site Role Allowed'
            */
           
           if ($this->projectLeader){
               
               if ($this->siteRole[$key]==0){
                 
                   $this->final[(string)$key]= array( 8,"Project Leader");
               }
               else $this->final[$key]=array($this->siteRole[$key],($this->siteRole[$key]==4?"Site Role Denied":"Site Role Allowed"));
             
           }
           elseif ($this->projectOwner && ($key=='View' || $key=='Download')){
                    $this->final[(string)$key]= array( 8,"Project Owner");
               
           }
           
           else{
               //compare user and Group, and then compare to Site Role
               
               if ($this->siteRole[$key]==4 ||  $this->siteRole[$key]==16)
                   $this->final[$key]=array($this->siteRole[$key],($this->siteRole[$key]==4?"Site Role Denied":"Site Role Allowed"));
                else 
                {
                       //User beats Group, unless user= None, then look to group
                      $userOrGroup="Not granted by any rule";
                       $compare=0;
                       if ($this->getKeyVal($key, $this->user)>0){
                           $compare = $this->getKeyVal($key, $this->user);
                           $userOrGroup="User Permission";
                       }
                       elseif ($this->getKeyVal($key, $this->groups)>0){
                           $compare = $this->getKeyVal($key, $this->groups);
                             $userOrGroup="Group Permission";
                       }
                         
                       
                       if ($this->siteRole[$key]==0)
                            $this->final[$key]=array($compare,$userOrGroup);
                        else  
                            $this->final[$key]==array($this->siteRole[$key],($this->siteRole[$key]==4?"Site Role Denied":"Site Role Allowed"));
                       
                   }
               
               
           }
           
           
       }
             
       return     $this->final;
   }
   
   private function getKeyVal($key, $search){
       
       //we are comparing Permission names from server which are different from how we store them, see ClassConstants RESTAPIWorkbookPermissions
       
       if(key_exists($key, $search) )
           return $search[$key];
       else return -1;
       
   }
        
  
}