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
 * This per-study custom class is made to override some methods of the patient visit manager
 * to make more customizable visit creation and management workflow
 */
class StudyName_Patient_Visit_Manager extends Patient_Visit_Manager {
	
    public function __construct(Patient $patientObject, Visit_Group $visitGroupObject){
        parent::__construct($patientObject, $visitGroupObject);
    }
	
}