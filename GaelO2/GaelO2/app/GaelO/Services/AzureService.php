<?php  

namespace App\Gaelo\Services;

use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;  
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\Psr7ResponseInterface;
use Illuminate\Support\Facades\Log;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;


class AzureService{               
    
    // variable     
    private HttpClientInterface $httpClientInterface;    
    private FrameworkInterface $frameworkInterface;
    private $ressource =  "https://management.azure.com/";
    private  $requestUrl = "https://login.microsoftonline.com/%7BtenantId%7D/oauth2/token";

    // constructor

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
      $this -> httpClientInterface=$httpClientInterface;
      $this -> frameworkInterface=$frameworkInterface;
    }
 
   // fonction 

   private function getTokenAzure() {
    $clientID = $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_ID);
    $tenantID = $this->frameworkInterface::getConfig(SettingsConstants::AZURE_TENANT_ID);
    $client_secret = $this->frameworkInterface::getConfig(SettingsConstants::AZURE_CLIENT_SECRET);
    
   

   }
              
}