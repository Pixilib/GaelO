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

Session::checkSession();
$linkpdo = Session::getLinkpdo();

//Only for admin role
if (!$_SESSION['admin']) {
    require 'includes/no_access.php';
}

if (empty($_POST)) {
    // Include view script
    require 'views/administrator/visit_builder_view.php';
} else {
    // Echo answer to the request
    switch ($_POST['request']) {
        case 'studies':
            echo json_encode(Global_Data::getAllStudies($linkpdo, true));
            break;

        case 'visitTypes':
            if (isset($_POST['study'])) {
                // Get visit types of the study
                try {
                    $study = new Study($_POST['study'], $linkpdo);
                    $possibleVisits = $study->getAllPossibleVisits();
                    $visitTypes = [];
                    foreach ($possibleVisits as $v) {
                        $visitTypes[$v->name] = $v->getSpecificTableInputType();
                    }
                    echo json_encode($visitTypes);
                } catch (Exception $e) {
                    echo 'Could not retrieve visit types list';
                }
            }
            break;

        case 'isDBTableEmpty':
            if (isset($_POST['study'], $_POST['visitType'])) {
                $visitType = new Visit_Type($linkpdo, $_POST['study'], $_POST['visitType']);
                try {
                    $response = [];
                    $response['isEmpty'] = Visit_Builder::isTableEmpty($visitType);
                    echo json_encode($response);
                } catch (Exception $e) {
                    echo 'Could not check if table is empty';
                }
            }
            break;

        case 'commitChanges':
            try {
                if (isset($_POST['study'], $_POST['visitType'])) {
                    $visitType = new Visit_Type($linkpdo, $_POST['study'], $_POST['visitType']);

                    // Alter existing columns
                    if (isset($_POST['changes'])) {
                        // Check if table is empty
                        if (Visit_Builder::isTableEmpty($visitType)) {
                            new Exception('Database table is not empty.');
                        }

                        $columns = array_keys($_POST['changes']);
                        // For each modified columns
                        foreach ($columns as $col) {
                            $changesName = array_keys($_POST['changes'][$col]);

                            if (isset($_POST['changes'][$col]['isDeleted'])) {
                                // Drop column
                                if ($_POST['changes'][$col]['isDeleted'] == 'true') {
                                    Visit_Builder::dropColumn($visitType, $col);
                                    continue;
                                }
                            }

                            if (isset($_POST['changes'][$col]['type'])) {
                                // Change column data type
                                $type = $_POST['changes'][$col]['type'];
                                $typeParam = $_POST['changes'][$col]['typeParam'] ?? null;
                                $dataType = Visit_Builder::formatDataType($type, $typeParam);

                                Visit_Builder::alterColumn($visitType, $col, $col, $dataType);
                            } else if (isset($_POST['changes'][$col]['typeParam'])) {
                                // Change column data type parameters
                                $originalDataType = Visit_Builder::getColumnDataType($visitType, $col);
                                
                                $type = Visit_Builder::extractDataTypeLabel($originalDataType);
                                $typeParam = $_POST['changes'][$col]['typeParam'] ?? null;
                                $dataType = Visit_Builder::formatDataType($type, $typeParam);
                                
                                Visit_Builder::alterColumn($visitType, $col, $col, $dataType);
                            }

                            if (isset($_POST['changes'][$col]['name'])) {
                                // Change column name
                                $colAfter = Visit_Builder::escape($_POST['changes'][$col]['name']);
                                $dataType = Visit_Builder::getColumnDataType($visitType, $col);
                                
                                Visit_Builder::alterColumn($visitType, $col, $colAfter, $dataType);
                            }
                        }
                    }

                    // Add new columns
                    if (isset($_POST['new'])) {
                        foreach ($_POST['new'] as $new) {
                            $columnName = Visit_Builder::escape($new['name']);
                            $typeParam = $new['typeParam'] ?? null;
                            $dataType = Visit_Builder::formatDataType($new['type'], $typeParam);

                            Visit_Builder::addColumn($visitType, $columnName, $dataType);
                        }
                    }
                } else {
                    throw new Exception('Null visit type');
                }
                $response['success'] = true;
                echo json_encode($response);
            } catch (Exception $e) {
                $response['success'] = false;
                $response['error'] = $e->getMessage();
                $response['message'] = '';
                echo json_encode($response);
            }

            break;
    }
}