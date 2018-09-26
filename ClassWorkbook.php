<?php
require_once 'ClassGenericFunctions.php';

class ClassWorkbook extends ClassGenericFunctions
{
    public const _CSVHeader = "TimeStamp,SiteID,ProjectName,ProjectID,WorkBookName,WorkBookID,contentUrl,updatedAt,sizeInMegaBytes,ViewCount,ViewContentURL,OwnerID";
  
    private $contentUrl= "";
    private $updatedAt= "";
    private $projectName= "";
    private $projectID= "";
    private $sizeInMegaBytes= 0;
 
    
  
    
    //workbook names are only unique for a project, so use projName.WorkbookName for key
    public function __construct($name, $id,  $contentUrl, $updatedAt, $projectName, $projectID, $sizeInMegaBytes,$ownerID="")
    {
        $this->name = $name;
        $this->id = $id;
        $this->contentUrl =$contentUrl;
        $this->updatedAt =$updatedAt;
        $this->projectName =$projectName;
        $this->projectID =$projectID;
        $this->sizeInMegaBytes=$sizeInMegaBytes;
        $this->ownerID=$ownerID;
    }
    
    public function getCSVData($siteID,$date){
     
     
            return $date.','. $siteID.',"'. $this->projectName.'",'.$this->projectID.',"'.$this->name.'",'
                .$this->id.','.$this->contentUrl.','.$this->updatedAt.','.$this->sizeInMegaBytes.','
                    .(String)$this->totalViewCount.','.$this->viewContentURL.','.$this->getOwnerID().PHP_EOL;
     
    }
    
    public function setTotalViewCount($viewCount){
        $this->totalViewCount = $viewCount;
    }
   
    public function setViewContentURL($viewContentURL){
        $this->viewContentURL = $viewContentURL;
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
    public function getSizeInMegaBytes()
    {
        return (string)$this->sizeInMegaBytes;
    }
    
    
  
}