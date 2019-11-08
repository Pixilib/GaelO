<?php
/**
 Copyright (C) 2018 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * Object handeling Documentation data in Database
 * @author salim
 *
 */
class Documentation {
    
    public $documentId;
    public $study;
    public $documentName;
    public $documentFileLocation;
    public $documentVersion;
    public $documentDate;
    public $accessInvestigator;
    public $accessController;
    public $accessMonitor;
    public $accessReviewer;
    public $deleted;
    
    
    public function __construct(PDO $linkpdo, int $idDocumentation){
    	
    	$documentationQuery = $linkpdo->prepare("SELECT * FROM documentation WHERE id_documentation = :idDocumentation");
    	$documentationQuery->execute(array('idDocumentation' => $idDocumentation));
    	$documentation=$documentationQuery->fetch(PDO::FETCH_ASSOC);
    	
        $this->documentId=$documentation['id_documentation'];
        $this->study=$documentation['study'];
        $this->documentName=$documentation['name'];
        $this->documentVersion=$documentation['version'];
        $this->documentDate=$documentation['document_date'];
        $this->accessInvestigator=$documentation['investigator'];
        $this->accessController=$documentation['controller'];
        $this->accessMonitor=$documentation['monitor'];
        $this->accessReviewer=$documentation['reviewer'];
        $this->deleted=$documentation['deleted'];
        
        $this->documentFileLocation = ($_SERVER['DOCUMENT_ROOT']."/upload/documentation/".$this->study."/".$this->documentName);
    }
    
    /**
     * Return if the current documentation is allowed for a given role
     * @param String $role
     * @return boolean
     */
    
    public function isDocumentationAllowedForRole(String $role){
        if($role==User::INVESTIGATOR) return $this->accessInvestigator;
        else if($role==User::CONTROLLER) return $this->accessController;
        else if($role==User::MONITOR) return $this->accessMonitor;
        else if($role==User::REVIEWER) return $this->accessReviewer;
        else if($role==User::SUPERVISOR) return true;
        else return false;
    }
    
    /**
     * Update documentation table
     * @param PDO $linkpdo
     * @param int $idDocumentation
     * @param bool $investigator
     * @param bool $monitor
     * @param bool $controller
     * @param bool $reviewer
     * @param bool $deleted
     */
    public static function updateDocumentation(PDO $linkpdo, int $idDocumentation, bool $investigator, bool $monitor, bool $controller, bool $reviewer, bool $deleted){
        $update = $linkpdo->prepare('UPDATE documentation
                                    SET investigator = :investigator,
                                        controller = :controller,
                                        monitor = :monitor,
                                        reviewer=:reviewer,
                                        deleted=:deleted
									WHERE id_documentation = :id');
        
        $update->execute(array('investigator' => intval($investigator),
            'controller' => intval($controller),
            'monitor' => intval($monitor),
            'reviewer' =>intval($reviewer),
            'deleted'=>intval($deleted),
            'id' => $idDocumentation ));
    }
    
    /**
     * Create documentation entry in database
     * @param PDO $linkpdo
     * @param String $uploadedFilename
     * @param String $study
     * @param String $version
     */
    public static function insertDocumentation(PDO $linkpdo, String $uploadedFilename, String $study, String $version){
        $insertion = $linkpdo->prepare('INSERT INTO documentation (name, document_date, study, version)
																			VALUES (:name, :doc_date, :study, :version)');
        $insertion->execute(array(
            'name' => $uploadedFilename,
            'doc_date' => date("Y-m-d"),
            'study' => $study,
            'version'=>$version
        ));
    }
}