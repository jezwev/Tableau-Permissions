<?php

class ClassSites
{
    public const _CSVHeader ="TimeStamp,ServerURL,Name,Id,State,Contenturl,Adminmode,Subscribeothersenabled,Guestaccessenabled,Commentingenabled,CacheWarmUpEnabled";
    
    private $name = "";

    private $id = "";

    private $state = "";

    private $contentURL = "";

    private $adminMode = "";

    private $revisionLimit = "";

    private $subscribeOthersEnabled = FALSE;

    private $guestAccessEnabled = FALSE;

    private $commentingEnabled = FALSE;

    private $cacheWarmupEnabled = FALSE;

    // content for the Site
    private $groups = array();

    private $users = array();

    private $workbooks = array();
    //Note workbooks is a list of workbooks that a user can see, whereas $workbookPermsissions has all permissions for workbooks, which includes deny!
    private $workbookPermsissions=array();
    private $dataSources = array();
    private $projects=array();
    
    private $UsersByID = array();

    public function __construct($name, $id="", $state="", $contentURL="", $adminMode="", $subscribeOthersEnabled = FALSE, $guestAccessEnabled = FALSE, $commentingEnabled = FALSE, $cacheWarmupEnabled = FALSE)
    {
        $this->name = $name;
        $this->id = $id;
        $this->state = $state;
        $this->contentURL =$contentURL;
        $this->adminMode = $adminMode;
        $this->subscribeOthersEnabled = $subscribeOthersEnabled;
        $this->guestAccessEnabled = $guestAccessEnabled;
        $this->commentingEnabled = $commentingEnabled;
        $this->cacheWarmupEnabled = $cacheWarmupEnabled;
    }
    public function getCSVData($date,$serverURL){
        
        return $date.','.$serverURL.',"'.$this->name.'",'.$this->id.','. $this->state .','.$this->contentURL.','.$this->adminMode.','.$this->subscribeOthersEnabled.','.$this->guestAccessEnabled.','.$this->commentingEnabled.','.$this->cacheWarmupEnabled;
    }
    

    public function getName()
    {
        return $this->name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getContentURL()
    {
        return $this->contentURL;
    }

    public function getAdminMode()
    {
        return $this->adminMode;
    }

    public function getSubscribeOthersEnabled()
    {
        return $this->subscribeOthersEnabled;
    }

    public function getGuestAccessEnabled()
    {
        return $this->guestAccessEnabled;
    }

    public function getCcommentingEnabled()
    {
        return $this->commentingEnabled;
    }

    public function getCacheWarmupEnabled()
    {
        return $this->cacheWarmupEnabled;
    }

    public function getLowerCaseName()
    {
        return strtolower($this->name);
    }

    public function getGroups()
    {
        return $this->groups;
    }
    
   

    public function addGroup($group)
    {
        $this->groups = $group;
    }

    public function addToGroups($group)
    {
        $this->groups[$group->getLowerCaseName()] = $group;
    }
    
    public function removeFromGroups($group)
    {
        unset($this->groups[$group->getLowerCaseName()]);
    }
    
    public function getUsers()
    {
        return $this->users;
    }

    public function addUsers($Users)
    {
        $this->users = $Users;
        //now build list of Users by ID
        $this->UsersByID=array();
        foreach ($Users as $x => $User){
            $this->UsersByID[$User->getId()]=$User->getLowerCaseName();
        }
        
        
    }
    
    public function addToUsers($User)
    {
        $this->users[$User->getLowerCaseName()] = $User;
        //also save by ID KEY, for quick search by User ID
        $this->UsersByID[$User->getId()]=$User->getLowerCaseName();
    }
    
    public function getUsersByID(){
        return $this->UsersByID;
    }
    
    public function removeFromUsers($User,$removeFromAllGroups=FALSE)
    {
        unset($this->users[strtolower($User)]);
        
        if ($removeFromAllGroups)
            $this->removeUserFromAllGroups(strtolower($User));
    }
    

    function removeUserFromAllGroups($userName){
        
        //loop through each group and remove user 
        foreach ($this->groups as $x => $group) {
            if (key_exists($userName, $group->getUsers())){
                $this->groups[$group->getLowerCaseName()]->removeUser($userName);
            }
        }
        
    }
    
    public function getDataSources()
    {
        return $this->dataSources;
    }
    
    
    public function addDataSources($dataSources)
    {
        $this->dataSources = $dataSources;
    }

  
    
    public function getWorkbookPermsissions()
    {
        return $this->workbookPermsissions;
    }
    
    
    public function addWorkbookPermsissions($workBooks)
    {
        $this->workbookPermsissions = $workBooks;
    }
    
    public function getWorkbooks()
    {
        return $this->workbooks;
    }

    
    public function addWorkbooks($workBooks)
    {
        $this->workbooks = $workBooks;
    }
    public function getProjects()
    {
        return $this->projects;
    }
    
    public function addProjects($projects)
    {
        $this->projects = $projects;
    }
}