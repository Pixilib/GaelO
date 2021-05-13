<?php

namespace App\GaelO\Services;

use App\GaelO\Constants\Constants;
use App\GaelO\Constants\SettingsConstants;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use App\GaelO\Services\StoreObjects\TagAnon;
use App\GaelO\Services\StoreObjects\OrthancStudy;

class OrthancService
{
    private HttpClientInterface $httpClientInterface;
    private FrameworkInterface $frameworkInterface;

    public function __construct(HttpClientInterface $httpClientInterface, FrameworkInterface $frameworkInterface)
    {
        $this->httpClientInterface = $httpClientInterface;
        $this->frameworkInterface = $frameworkInterface;
    }

    public function setOrthancServer(bool $storage) : void
    {
        //Set Time Limit at 3H as operation could be really long
        set_time_limit(10800);
        //Set address of Orthanc server
        if ($storage) {
            $address = $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_STORAGE_ADDRESS);
            $port = $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_STORAGE_PORT);
            $login = $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_STORAGE_LOGIN);
            $password = $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_STORAGE_PASSWORD);
        } else {
            $address = $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_TEMPORARY_ADDRESS);
            $port = $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_TEMPORARY_PORT);
            $login = $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_TEMPORARY_LOGIN);
            $password = $this->frameworkInterface::getConfig(SettingsConstants::ORTHANC_TEMPORARY_PASSWORD);
        }

        $this->httpClientInterface->setAddress($address, $port);
        $this->httpClientInterface->setBasicAuthentication($login, $password);
    }

    public function getOrthancRessourcesDetails(string $level, string $orthancID) : array {
        return $this->httpClientInterface->requestJson('GET', '/'.$level.'/'.$orthancID)->getJsonBody();
    }

    public function getOrthancRessourcesStatistics(string $level, string $orthancID) : array {
        return $this->httpClientInterface->requestJson('GET', '/'.$level.'/'.$orthancID.'/statistics/')->getJsonBody();
    }

    public function getInstanceTags(string $orthancInstanceID) : array {
        return $this->httpClientInterface->requestJson('GET', '/instances/'.$orthancInstanceID.'/tags/')->getJsonBody();
    }

    public function getOrthancPeers() : array
    {
        return $this->httpClientInterface->requestJson('GET', '/peers')->getJsonBody();
    }

    public function addPeer(string $name, string $url, string $username, string $password)
    {

        $data = array(
            'Username' => $username,
            'Password' => $password,
            'Url' => $url
        );

        $this->httpClientInterface->requestJson('PUT', '/peers/' . $name, $data);
    }

    /**
     * Remove Peer declaration from Orthanc
     * @param string $name
     */
    public function deletePeer(string $name)
    {
        $this->httpClientInterface->rowRequest('DELETE', '/peers/' . $name, null, null);
    }

    /**
     * Remove all peers from orthanc
     */
    public function removeAllPeers() : void
    {
        $peers = $this->getOrthancPeers();

        foreach ($peers as $peer) {
            $this->deletePeer($peer);
        }
    }


    public function searchInOrthanc(
        string $level,
        string $patientID = '',
        string $patientName = '',
        string $studyDate = '',
        string $studyUID = '',
        string $accessionNumber = '',
        string $studyDescription = ''
    ) : array {

        $query = array(
            'Level' => $level,
            'CaseSensitive' => false,
            'Expand' => false,
            'Query' => array(
                'PatientID' => $patientID,
                'PatientName' => $patientName,
                'StudyDate' => $studyDate,
                'StudyInstanceUID' => $studyUID,
                'AccessionNumber' => $accessionNumber,
                'StudyDescription' => $studyDescription,
            )

        );

        return $this->httpClientInterface->requestJson('POST', '/tools/find', $query)->getJsonBody();
    }

    public function deleteFromOrthanc(string $level, string $uid)
    {
        $this->httpClientInterface->rowRequest('DELETE', '/' . $level . '/' . $uid, null, null);
    }

    public function isPeerAccelerated(string $peer) : bool
    {

        $peers = $this->httpClientInterface->rowRequest('GET', '/transfers/peers/', null, null)->getJsonBody();

        if ($peers[$peer] == "installed") {
            return true;
        }

        return false;
    }

    public function sendToPeer(string $peer, array $ids, bool $synchronous)
    {
        $data = [
            'Synchronous' => $synchronous,
            'Resources' => $ids
        ];

        return $this->httpClientInterface->requestJson('POST', '/peers/' . $peer . '/store', $data);
    }

    public function sendToPeerAsyncWithAccelerator(string $peer, array $ids, bool $gzip)
    {

        //If Peer dosen't have accelerated transfers fall back to regular orthanc peer transfers
        if (!$this->isPeerAccelerated($peer)) {
            $answer = $this->sendToPeer($peer, $ids, false);
            return $answer;
        }

        $data = array(
            'Peer' => $peer,
            'Compression' => $gzip === true ? "gzip" : "none"
        );


        foreach ($ids as $serieID) {
            $data['Resources'][] = array(
                'Level' => 'Series',
                'ID' => $serieID
            );
        }

        return $this->httpClientInterface->requestJson('POST', '/transfers/send', $data);
    }

    public function importFiles(array $files) : array
    {
        $psr7ResponseAdapterArray = $this->httpClientInterface->requestUploadArrayDicom('POST', '/instances', $files);
        $arrayAnswer = array_map( function ($response){
            return json_decode($response->getBody(), true);
        }, $psr7ResponseAdapterArray);
        return $arrayAnswer;
    }



    /**
     * Anonymize a study ressources according to Anon Profile
     * Return the Anonymized Orthanc ID
     * @param string $studyID
     * @param string $profile
     * @param string $patientCode
     * @param string $visitType
     * @param string $studyName
     * @return string anonymizedOrthancStudyID
     */
    public function anonymize(string $studyID, string $profile, string $patientCode, string $visitType, string $studyName) : string
    {

        $jsonAnonQuery = $this->buildAnonQuery($profile, $patientCode, $patientCode, $visitType, $studyName);

        $answer = $this->httpClientInterface->requestJson('POST', "/studies/" . $studyID . "/anonymize", $jsonAnonQuery);

        //get the resulting Anonymized study Orthanc ID
        $anonAnswer = $answer->getJsonBody();
        $anonymizedID = $anonAnswer['ID'];

        //Remove SC if any in the anonymized study
        $this->removeSC($anonymizedID);

        return $anonymizedID;
    }

    /**
     * Build Anon Json post for Anon settings
     * @param string $profile
     * @param string $newPatientName
     * @param string $newPatientID
     * @param string $newStudyDescription
     * @return string
     */
    private function buildAnonQuery(
        string $profile,
        string $newPatientName,
        string $newPatientID,
        string $newStudyDescription,
        string $clinicalStudy
    ) : array {

        $tagsObjects = [];

        if ($profile == Constants::ORTHANC_ANON_PROFILE_DEFAULT) {
            $date = TagAnon::KEEP;
            $body = TagAnon::KEEP;

            $tagsObjects[] = new TagAnon("0010,0030", TagAnon::REPLACE, "19000101"); // BirthDay
            $tagsObjects[] = new TagAnon("0008,1030", TagAnon::REPLACE, $newStudyDescription); //studyDescription
            $tagsObjects[] = new TagAnon("0008,103E", TagAnon::KEEP); //series Description


        } else if ($profile == Constants::ORTHANC_ANON_PROFILE_FULL) {
            $date = TagAnon::CLEAR;
            $body = TagAnon::CLEAR;

            $tagsObjects[] = new TagAnon("0010,0030", TagAnon::REPLACE, "19000101"); // BirthDay
            $tagsObjects[] = new TagAnon("0008,1030", TagAnon::CLEAR); // studyDescription
            $tagsObjects[] = new TagAnon("0008,103E", TagAnon::CLEAR); //series Description
        }

        //List tags releted to Date
        $tagsObjects[] = new TagAnon("0008,0022", $date); // Acquisition Date
        $tagsObjects[] = new TagAnon("0008,002A", $date); // Acquisition DateTime
        $tagsObjects[] = new TagAnon("0008,0032", $date); // Acquisition Time
        $tagsObjects[] = new TagAnon("0038,0020", $date); // Admitting Date
        $tagsObjects[] = new TagAnon("0038,0021", $date); // Admitting Time
        $tagsObjects[] = new TagAnon("0008,0035", $date); // Curve Time
        $tagsObjects[] = new TagAnon("0008,0025", $date); // Curve Date
        $tagsObjects[] = new TagAnon("0008,0023", $date); // Content Date
        $tagsObjects[] = new TagAnon("0008,0033", $date); // Content Time
        $tagsObjects[] = new TagAnon("0008,0024", $date); // Overlay Date
        $tagsObjects[] = new TagAnon("0008,0034", $date); // Overlay Time
        $tagsObjects[] = new TagAnon("0040,0244", $date); // ...Start Date
        $tagsObjects[] = new TagAnon("0040,0245", $date); // ...Start Time
        $tagsObjects[] = new TagAnon("0008,0021", $date); // Series Date
        $tagsObjects[] = new TagAnon("0008,0031", $date); // Series Time
        $tagsObjects[] = new TagAnon("0008,0020", $date); // Study Date
        $tagsObjects[] = new TagAnon("0008,0030", $date); // Study Time
        $tagsObjects[] = new TagAnon("0010,21D0", $date); // Last menstrual date
        $tagsObjects[] = new TagAnon("0008,0201", $date); // Timezone offset from UTC
        $tagsObjects[] = new TagAnon("0040,0002", $date); // Scheduled procedure step start date
        $tagsObjects[] = new TagAnon("0040,0003", $date); // Scheduled procedure step start time
        $tagsObjects[] = new TagAnon("0040,0004", $date); // Scheduled procedure step end date
        $tagsObjects[] = new TagAnon("0040,0005", $date); // Scheduled procedure step end time

        // same for Body characteristics
        $tagsObjects[] = new TagAnon("0010,2160", $body); // Patient's ethnic group
        $tagsObjects[] = new TagAnon("0010,21A0", $body); // Patient's smoking status
        $tagsObjects[] = new TagAnon("0010,0040", $body); // Patient's sex
        $tagsObjects[] = new TagAnon("0010,2203", $body); // Patient's sex neutered
        $tagsObjects[] = new TagAnon("0010,1010", $body); // Patient's age
        $tagsObjects[] = new TagAnon("0010,21C0", $body); // Patient's pregnancy status
        $tagsObjects[] = new TagAnon("0010,1020", $body); // Patient's size
        $tagsObjects[] = new TagAnon("0010,1030", $body); // Patient's weight

        //Others
        $tagsObjects[] = new TagAnon("0008,0050", TagAnon::REPLACE, $clinicalStudy); // Accession Number contains study name
        $tagsObjects[] = new TagAnon("0010,0020", TagAnon::REPLACE, $newPatientID); //new Patient Name
        $tagsObjects[] = new TagAnon("0010,0010", TagAnon::REPLACE, $newPatientName); //new Patient Name

        // Keep some Private tags usefull for PET/CT or Scintigraphy
        $tagsObjects[] = new TagAnon("7053,1000", TagAnon::KEEP); //Phillips
        $tagsObjects[] = new TagAnon("7053,1009", TagAnon::KEEP); //Phillips
        $tagsObjects[] = new TagAnon("0009,103B", TagAnon::KEEP); //GE
        $tagsObjects[] = new TagAnon("0009,100D", TagAnon::KEEP); //GE
        $tagsObjects[] = new TagAnon("0011,1012", TagAnon::KEEP); //Other

        $jsonArrayAnon = [];
        $jsonArrayAnon['KeepPrivateTags'] = false;
        $jsonArrayAnon['Force'] = true;

        foreach ($tagsObjects as $tag) {

            if ($tag->choice == TagAnon::REPLACE) {
                $jsonArrayAnon['Replace'][$tag->tag] = $tag->newValue;
            } else if ($tag->choice == TagAnon::KEEP) {
                $jsonArrayAnon['Keep'][] = $tag->tag;
            }
        }

        return $jsonArrayAnon;
    }

    /**
     * Remove secondary captures in the study
     * @param string $orthancStudyID
     */
    private function removeSC(string $orthancStudyID)
    {

        $studyOrthanc = new OrthancStudy($this);
        $studyOrthanc->setStudyOrthancID($orthancStudyID);
        $studyOrthanc->retrieveStudyData();
        $seriesObjects = $studyOrthanc->orthancSeries;
        foreach ($seriesObjects as $serie) {
            if ($serie->isSecondaryCapture()) {
                $this->deleteFromOrthanc("series", $serie->serieOrthancID);
                error_log("Deleted SC");
            }
        }
    }


    /**
     * Return JobId details (get request in Orthanc)
     * @param String $jobId
     * @return mixed
     */
    public function getJobDetails(String $jobId) {
        return $this->httpClientInterface->requestJson('GET', '/jobs/'.$jobId)->getJsonBody();
    }

    public function getStudyOrthancDetails(string $orthancStudyID){
        $studyOrthanc = new OrthancStudy($this);
        $studyOrthanc->setStudyOrthancID($orthancStudyID);
        $studyOrthanc->retrieveStudyData();
        return $studyOrthanc;
    }

    public function getOrthancZipStream(array $seriesOrthancIDs){
        $payload = array('Transcode'=>'1.2.840.10008.1.2.1', 'Resources' => $seriesOrthancIDs);
        $this->httpClientInterface->streamResponse('POST', '/tools/create-archive', $payload);
    }

}
