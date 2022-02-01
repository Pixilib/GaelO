<?php  

namespace App\Gaelo\Services;

use App\GaelO\Interfaces\Adapters\HttpClientInterface;  
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use Illuminate\Support\Facades\Log;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;

class AzureService
{               
     
    private HttpClientInterface $httpClientInterface;    
    private FrameworkInterface $frameworkInterface;
    private $tenantID;
    private $ressource = "https://management.azure.com/";


    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface){     
        $this -> httpClientInterface=$httpClientInterface;
        $this -> frameworkInterface=$frameworkInterface;
        $this -> tenantID =$frameworkInterface::getConfig(SettingsConstants::AZURE_DIRECTORY_ID);
        $this -> setServerAddress();
    }
    
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
        $ressourceGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_RESSOURCE_GROUP);
        $containerGroupe=$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CONTAINER_GROUP);
        $url="https://management.azure.com/subscriptions/".$subID."/resourceGroups/".$ressourceGroupe."/providers/Microsoft.ContainerInstance/containerGroups/".$containerGroupe."";
        $this->httpClientInterface->setUrl($url);
    }

    public function startAci(){
        $this->setToken();
        $uri="/start?api-version=2021-09-01";
        $response = $this->httpClientInterface->rowRequest('POST',$uri,$body='',['Accept' =>'application/json'])->getStatusCode();
        
        return $response	;
    }

    public function stopACI(){
        $this->setToken();
        $uri="/stop?api-version=2021-09-01";    
        $response = $this->httpClientInterface->rowRequest('POST',$uri,$body='' ,['Accept' =>'application/json'])->getStatusCode();

        return $response	;
    } 

    public function getStatusAci():array{
        $this->setToken();
        $uri="?api-version=2021-09-01";
        $response = $this->httpClientInterface->rowRequest('GET',$uri, $body='',['Accept' =>'application/json'])->getJsonBody();
            
        /*3 states disponible
        * Pending -> Creation en cours
        * Running -> en cours d'allumage de l'aci
        * Stopped -> Fermé 
        *
        *L'ip est disponible uniquement en pending et running
        */
        $attributes=[
                'state'=>$response["properties"]['instanceView']["state"],
                'ip'=>  empty($response["properties"]["ipAddress"]["ip"]) ? '0' : $response["properties"]["ipAddress"]["ip"]  ,
            ];
        
        return  $attributes;
    }
   
    public function checkStatus() {
        $test=$this->getStatusAci();
    
            while ($test['state'] === 'Pending') {
                $test=$this->getStatusAci();
                sleep(15);
            }
        Log::info("Lancement des job");
    }   
}