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
 * Tracker related methods
 * @author salim
 *
 */
class Tracker {
    
	/**
	 * Get tracker data by Role +- Study Filter
	 * @param string $role
	 * @param PDO $linkpdo
	 * @param string $study
	 * @return array
	 */
	public static function getTrackerByRoleStudy(string $role, PDO $linkpdo, $study=null) {
		if ($study == null) {
			$queryTracker=$linkpdo->prepare('SELECT * FROM tracker WHERE role=:role');
			$queryTracker->execute(array(
				'role' => $role
			));
           
		} else {
			$queryTracker=$linkpdo->prepare('SELECT * FROM tracker WHERE study = :study AND role=:role');
			$queryTracker->execute(array('study' => $study,
				'role' => $role,
			));
            
		}
		$trackerResult=$queryTracker->fetchAll(PDO::FETCH_ASSOC);

		return $trackerResult;
	}
    
	/**
	 * Return logged users messages of a study
	 * @param string $study
	 * @param PDO $linkpdo
	 * @return array
	 */
	public static function getMessageStudy(string $study, PDO $linkpdo) {
		$queryTracker=$linkpdo->prepare('SELECT * FROM tracker WHERE study = :study AND action_type="Send Message"');
		$queryTracker->execute(array('study' => $study));
		$trackerResult=$queryTracker->fetchAll(PDO::FETCH_ASSOC);
		return $trackerResult;
	}
    
	/**
	 * Get tracker data for a specific visit
	 * @param string $id_visit
	 * @param PDO $linkpdo
	 * @return array
	 */
	public static function getTackerForVisit(string $id_visit, PDO $linkpdo) {
		$queryTracker=$linkpdo->prepare('SELECT * FROM tracker WHERE id_visit = :id_visit ORDER BY date');
		$queryTracker->execute(array('id_visit' => $id_visit));
		$trackerResult=$queryTracker->fetchAll(PDO::FETCH_ASSOC);
		return $trackerResult;    
	}
    
	/**
	 * Activity logger to log user activity in database
	 * Activity should be an associative key, will be JSON encoded
	 */
	public static function logActivity($username, $role, $study, $id_visit, $actionType, $actionDetails) {
        
		$linkpdo=Session::getLinkpdo();
        
		$connecter=$linkpdo->prepare('INSERT INTO tracker (date, username, role, study, id_visit, action_type, action_details)
								VALUES(:date, :username, :role, :study, :id_visit, :action_type, :action_details)' );
        
		$connecter->execute(array(
			"username" => $username,
			"role" => $role,
			"date"=> date('Y-m-d H:i:s').substr((string)microtime(), 1, 6),
			"study"=>$study,
			"id_visit"=>$id_visit,
			"action_type"=>$actionType,
			"action_details"=>json_encode($actionDetails)
		));
        
	}
    
}