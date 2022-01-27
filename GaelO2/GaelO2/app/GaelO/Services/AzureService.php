<?php  

namespace App\Gaelo\Services;


use App\GaelO\Interfaces\Adapters\HttpClientInterface;  
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use Illuminate\Support\Facades\Log;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;




class AzureService{               
    
    // variable     
    private HttpClientInterface $httpClientInterface;    
    private FrameworkInterface $frameworkInterface;
    private $tenantID;
    private $ressource = "https://management.azure.com/";

    // constructor

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
      $this -> httpClientInterface=$httpClientInterface;
      $this -> frameworkInterface=$frameworkInterface;
      $this -> tenantID =$frameworkInterface::getConfig(SettingsConstants::AZURE_TENANT_ID);
      $this -> getTokenAzure();
      $this -> setServerAddress();
    
    }

   // fonction 

   public function getTokenAzure() {    

    $requestUrl = "https://login.microsoftonline.com/".$this ->tenantID."/oauth2/token";

     $payload=[ 
    "grant_type"=> "client_credentials",
    "client_id"=>$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_ID),
    "client_secret"=>$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_SECRET),
    "resource"=> $this->ressource,
  ];
    $response = $this -> httpClientInterface -> requestUrlEncoded($requestUrl,$payload)->getJsonBody();
    $token =$response["access_token"];
   return $token;
   }   

   public function setToken(){ 
    $authorizationToken=$this->getTokenAzure();
  
     $this->httpClientInterface->setAuthorizationToken($authorizationToken);
 }       

  private function setServerAddress(){
    $subID=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_SUBSCRIPTION_ID);
    $ressourceGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CONTAINER_NAME);
    $containerGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_RESSOURCE_NAME);
    $url="https://management.azure.com/subscriptions/".$subID."/resourceGroups/".$ressourceGroupe."/providers/Microsoft.ContainerInstance/containerGroups/".$containerGroupe."";
    $this->httpClientInterface->setUrl($url);
  }

  /* public function startAci(){
     getTokenAzure();
     setToken();
     $subID=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_SUBSCRIPTION_ID);
     $ressourceGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CONTAINER_NAME);
     $containerGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_RESOURCE_NAME);
     $urlRequest="https://management.azure.com/subscriptions/".$subID."/resourceGroups/".$ressourceGroupe."/providers/Microsoft.ContainerInstance/containerGroups/".$containerGroupe."/start?api-version=2021-09-01";
     $response = $this->httpClientInterface->rowRequest('POST',$urlRequest,['Accept' =>'application/json']);
    

   }
   public function stopACI(){
    getTokenAzure();
    setToken();
    $subID=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_SUBSCRIPTION_ID);
    $ressourceGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CONTAINER_NAME);
    $containerGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_RESOURCE_NAME);
  
    $urlRequest="https://management.azure.com/subscriptions/".$subID."/resourceGroups/".$ressourceGroupe."/providers/Microsoft.ContainerInstance/containerGroups/".$containerGroupe."/stop?api-version=2021-09-01";
    $response = $this->httpClientInterface->rowRequest('POST',$urlRequest,['Accept' =>'application/json']);
  
   } */

  public function getStatusAci(){
    $this->setToken();
    $uri="?api-version=2021-09-01";
    
    try {
      $response = $this->httpClientInterface->rowRequest('GET',$uri, $body='',['Accept' =>'application/json']);
    } catch (\Exception $th) {
   
     Log::info($th->getMessage());
    }
   }
   
}