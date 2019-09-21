<?php 

class Preferences{
    
    public $patientCodeLength;
    public $plateformName;
    public $adminEmail;
    public $corporation;
    public $plateformAddress;
    public $dateImportFormat;
    public $countryNameFormat;
    
    public $orthancExposedAddress;
    public $orthancExposedPort;
    public $orthancExposedInternalLogin;
    public $orthancExposedInternalPassword;
    public $orthancExposedExternalLogin;
    public $orthancExposedExternalPassword;
    public $orthancPacsAddress;
    public $orthancPacsPort;
    public $orthancPacsLogin;
    public $orthancPacsPassword;
    public $emailUseSmtp;
    public $emailSmtpHost;
    public $emailSmtpPort;
    public $emailSmtpUser;
    public $emailSmtpPassword;
    public $emailSmtpEncryption;

    public function __construct(PDO $linkpdo){
        
        $connecter = $linkpdo->prepare('SELECT * FROM preferences');
        $connecter->execute();
        
        $result = $connecter->fetch(PDO::FETCH_ASSOC);
        
        $this->patientCodeLength=$result['patient_code_length'];
        $this->plateformName=$result['name'];
        $this->adminEmail=$result['admin_email'];
        $this->corporation=$result['corporation'];
        $this->plateformAddress=$result['address'];
        $this->dateImportFormat=$result['parse_date_import'];
        $this->countryNameFormat=$result['parse_country_name'];
        $this->orthancExposedAddress=$result['orthanc_exposed_address'];
        $this->orthancExposedPort=$result['orthanc_exposed_port'];
        $this->orthancExposedInternalLogin=$result['orthanc_exposed_login'];
        $this->orthancExposedInternalPassword=$result['orthanc_exposed_password'];
        $this->orthancExposedExternalLogin=$result['orthanc_exposed_external_login'];
        $this->orthancExposedExternalPassword=$result['orthanc_exposed_external_password'];
        $this->orthancPacsAddress=$result['orthanc_pacs_address'];
        $this->orthancPacsPort=$result['orthanc_pacs_port'];
        $this->orthancPacsLogin=$result['orthanc_pacs_login'];
        $this->orthancPacsPassword=$result['orthanc_pacs_password'];
        $this->emailUseSmtp=$result['use_smtp'];
        $this->emailSmtpHost=$result['smtp_host'];
        $this->emailSmtpPort=$result['smtp_port'];
        $this->emailSmtpUser=$result['smtp_user'];
        $this->emailSmtpPassword=$result['smtp_password'];
        $this->emailSmtpEncryption=$result['smtp_secure'];
        
    }
    
    public static function updatePlateformPreferences($post, $linkpdo){
        
        $prefUpdater=$linkpdo->prepare('UPDATE preferences SET patient_code_length=:codeLenght,
                                            name=:name,
                                            admin_email=:email,
                                            corporation=:corporation,
                                            address=:address,
                                            parse_date_import=:parse_date_import,
                                            parse_country_name=:parseCountryName,
                                            orthanc_exposed_address=:Orthanc_Exposed_Address,
                                            orthanc_exposed_port=:Orthanc_Exposed_Port,
                                            orthanc_exposed_login=:Orthanc_Exposed_Login,
                                            orthanc_exposed_password=:Orthanc_Exposed_Password,
                                            orthanc_exposed_external_login=:Orthanc_Exposed_ExternalLogin,
                                            orthanc_exposed_external_password=:Orthanc_Exposed_ExternalPassword,
                                            orthanc_pacs_address=:Orthanc_Pacs_Address,
                                            orthanc_pacs_port=:Orthanc_Pacs_Port,
                                            orthanc_pacs_login=:Orthanc_Pacs_Login,
                                            orthanc_pacs_password=:Orthanc_Pacs_Password,
                                            use_smtp=:use_smtp,
                                            smtp_host=:smtp_host,
                                            smtp_port=:smtp_port,
                                            smtp_user=:smtp_user,
                                            smtp_password=:smtp_password,
                                            smtp_secure=:smtp_secure
                                            WHERE 1');
        
        $prefUpdater->execute(array('codeLenght'=>$post['patientCodeLenght'],
            'corporation'=>$post['coporation'],
            'address'=>$post['webAddress'],
            'email'=>$post['adminEmail'],
            'name'=>$post['plateformName'],
            'parse_date_import'=>$post['parseDateImport'],
            'parseCountryName'=>$post['parseCountryName'],
            'Orthanc_Exposed_Address'=>$post['orthancExposedAddress'],
            'Orthanc_Exposed_Port'=>$post['orthancExposedPort'],
            'Orthanc_Exposed_Login'=>$post['orthancExposedLogin'],
            'Orthanc_Exposed_Password'=>$post['orthancExposedPassword'],
            'Orthanc_Pacs_Address'=>$post['orthancPacsAddress'],
            'Orthanc_Pacs_Port'=>$post['orthancPacsPort'],
            'Orthanc_Pacs_Login'=>$post['orthancPacsLogin'],
            'Orthanc_Pacs_Password'=>$post['orthancPacsPassword'],
            'use_smtp'=>intval($post['useSmtp']),
            'smtp_host'=>$post['smtpHost'],
            'smtp_port'=>$post['smtpPort'],
            'smtp_user'=>$post['smtpUser'],
            'smtp_password'=>$post['smtpPassword'],
            'smtp_secure'=>$post['smtpSecure'],
            'Orthanc_Exposed_ExternalLogin'=>$post['orthancExposedExternalLogin'],
            'Orthanc_Exposed_ExternalPassword'=>$post['orthancExposedExternalPassword']
        ));
        
    }
}?>