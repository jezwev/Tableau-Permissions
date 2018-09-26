<?php
require_once 'ClassGenericFunctions.php';

class ClassDataSource extends ClassGenericFunctions
{
    public const _CSVHeader = "TimeStamp,SiteID,ProjectName,ProjectID,DataSourceName,dataSourceID,contentUrl,updatedAt,isCertified,OwnerID";
  
    private $contentUrl= "";
    private $updatedAt= "";
    private $createdAt= "";
    private $projectName= "";
    private $projectID= "";
    private $type= "";
    private $isCertified=FALSE;
  
    
    //workbook names are only unique for a project, so use projName.WorkbookName for key
    public function __construct($name, $id,  $contentUrl, $updatedAt, $projectName, $projectID, $type,$isCertified, $createdAt,$ownerId="")
    {
        $this->name = $name;
        $this->id = $id;
        $this->contentUrl =$contentUrl;
        $this->updatedAt =$updatedAt;
        $this->projectName =$projectName;
        $this->projectID =$projectID;
        $this->type=$type;
        $this->isCertified=$isCertified;
        $this->createdAt=$createdAt;
        $this->ownerID=$ownerId;
        
    }
    
    public function getCSVData($siteID,$date){
       
            return $date.','. $siteID.',"'. $this->projectName.'",'.$this->projectID.','.$this->name.','.$this->id.','.
                $this->contentUrl.','.$this->updatedAt.','.$this->isCertified.','.$this->getOwnerID().PHP_EOL;
       
    }
     
    public function getContentUrl()
    {
        return (string)$this->contentUrl;
    }
    
    public function getUpdatedAt()
    {
        return (string)$this->updatedAt;
    }
    public function getProjectName()
    {
        return (string)$this->projectName;
    }
    
    public function getProjectID()
    {
        return (string)$this->projectID;
    }
    public function getType()
    {
        return (string)$this->type;
    }
    
    public function getIsCertified()
    {
        return (string)$this->isCertified;
    }
    public function getCreatedAt()
    {
        return (string)$this->createdAt;
    }
    
    
  
}