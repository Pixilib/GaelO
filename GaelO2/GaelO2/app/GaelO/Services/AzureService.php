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
      $this -> tenantID =$frameworkInterface::getConfig(SettingsConstants::AZURE_DIRECTORY_ID);
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
    //Log::info("je suis dans le get token" ,$response);
   return $token;
   }   

   public function setToken(){ 
    $authorizationToken=$this->getTokenAzure();
  
     $this->httpClientInterface->setAuthorizationToken($authorizationToken);
 }       

  private function setServerAddress(){
    $subID=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_SUBSCRIPTION_ID);
    $ressourceGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_RESSOURCE_GROUP);
    $containerGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CONTAINER_GROUP);
    $url="https://management.azure.com/subscriptions/".$subID."/resourceGroups/".$ressourceGroupe."/providers/Microsoft.ContainerInstance/containerGroups/".$containerGroupe."";
    $this->httpClientInterface->setUrl($url);
  }

  public function startAci(){
  
     $this->setToken();
     $uri="/start?api-version=2021-09-01";
     try {
     $response = $this->httpClientInterface->rowRequest('POST',$uri,$body='',['Accept' =>'application/json'])->getStatusCode();
     Log::info($response);
    } catch (\Exception $th) {
   
      Log::info($th->getMessage());
     }
  }
 
   
   public function stopACI(){
   
    $this->setToken();
    $uri="/stop?api-version=2021-09-01";
    try {
    $response = $this->httpClientInterface->rowRequest('POST',$uri,$body='' ,['Accept' =>'application/json'])->getStatusCode();
    Log::info($response);
  } catch (\Exception $th) {
   
    Log::info($th->getMessage());
   }
   } 

  public function getStatusAci():array{
    $this->setToken();
    $uri="?api-version=2021-09-01";
    
    try {
      $response = $this->httpClientInterface->rowRequest('GET',$uri, $body='',['Accept' =>'application/json'])->getJsonBody();
      
      /*3 states disponible
      * Pending -> Creation en cours
      * Running -> en cours d'allumage de l'aci
      * Stopped -> Fermé 
      *
      *L'ip est disponible uniquement en pending et running
      */
      Log::info("je suis le state de getstatus");
     Log::info($response["properties"]['instanceView']["state"]);
     Log::info("je suis l ip de getstatus");
     Log::info($response["properties"]["ipAddress"]["ip"]);
  
    } catch (\Exception $th) {
    
     Log::info($th->getMessage());
    }
    $attributes=[
      'state'=>$response["properties"]['instanceView']["state"],
      'ip'=>$response["properties"]["ipAddress"]["ip"],
    ];
    return  $attributes;
    }

   
}