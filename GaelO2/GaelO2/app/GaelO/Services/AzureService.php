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
    private $ressource =  "https://management.azure.com/";

    // constructor

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
      $this -> httpClientInterface=$httpClientInterface;
      $this -> frameworkInterface=$frameworkInterface;
      $this -> tenantID =frameworkInterface::getConfig(SettingsConstants::AZURE_TENANT_ID);

    }

   // fonction 
  
   public function getTokenAzure() {    

    $requestUrl = "https://login.microsoftonline.com/".$this ->tenantID."/oauth2/token";

     $payload=[ 
    'clientID'=>$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_ID),
    'client_secret'=>$this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_SECRET),
    'ressource'=> $this->ressource,
  ];
    $response = $this -> httpClientInterface -> requestUrlEncoded($requestUrl,$payload);
  
   }          
}