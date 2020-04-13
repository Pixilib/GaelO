<?php

/**
 Copyright (C) 2018-2020 KANOUN Salim
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
 * Performs DB Structure Alteration for the specific forms
 */
class Visit_Builder
{

	/**
	 * Get specific PDO object in order to perform db table alteration
	 * on the visit type tables
	 * @return PDO
	 */
	private static function getLinkpdo(): PDO
	{
		return Session::getLinkpdo();
	}


	/**
	 * Check if the specific db table of a given visit type is empty
	 * @param Visit_Type $vt
	 * @return boolean
	 */
	public static function isTableEmpty(Visit_Type $vt): bool
	{
		$linkpdo=Visit_Builder::getLinkpdo();
		$table=$vt->tableReviewSpecificName;
		$pdoSt=$linkpdo->query('SELECT * FROM '.$table.';');
		return count($pdoSt->fetchAll()) == 0;
	}

	public static function dropColumn(Visit_Type $vt, string $column) : PDOStatement
	{
		$linkpdo=Visit_Builder::getLinkpdo();
		$table=$vt->tableReviewSpecificName;
		$sql='ALTER TABLE `'.$table.'` DROP COLUMN `'.$column.'`;';
		return $linkpdo->query($sql);
	}


	public static function alterColumn(Visit_Type $vt, $columnNameBefore, $columnNameAfter, $dataType) : PDOStatement
	{ 
		$linkpdo=Visit_Builder::getLinkpdo();
		$table=$vt->tableReviewSpecificName;
		$sql='ALTER TABLE `'.$table.'` CHANGE `'.$columnNameBefore.'` `'.$columnNameAfter.'` '.$dataType.';';
		return $linkpdo->query($sql);
	}


	public static function addColumn(Visit_Type $vt, $columnName, $dataType) : PDOStatement
	{
		$linkpdo=Visit_Builder::getLinkpdo();
		$table=$vt->tableReviewSpecificName;
		$sql='ALTER TABLE `'.$table.'` ADD `'.$columnName.'` '.$dataType.';';
		return $linkpdo->query($sql);
	}


	/**
	 * Format mysql data type string for mysql query
	 * @param string $type mysql data type without parameters (e.g. 'decimal')
	 * @param array|null $typeParam list of the data type paramaters
	 * @return string mysql column datatype
	 */
	public static function formatDataType(string $typeLabel, $typeParams): string
	{
		$res='';
		switch ($typeLabel) {
			case 'int':
				$res='INT(11)';
				break;
			case 'tinyint':
				$res='TINYINT(1)';
				break;
			case 'tinytext':
				$res='TINYTEXT';
				break;
			case 'date':
				$res='DATE';
				break;
			case 'varchar':
				$param=Visit_Builder::escape($typeParams[0]);
				// Format params array into string e.g. '(123)'
				$res='VARCHAR('.$param.')';
				break;
			case 'decimal':
				$param1=Visit_Builder::escape($typeParams[0]);
				$param2=Visit_Builder::escape($typeParams[1]);
				// Format params array into string e.g. '(12,3)'
				$res='DECIMAL('.$param1.','.$param2.')';
				break;
			case 'enum':
				$params=[];
				foreach ($typeParams as $tp) {
					array_push($params, Visit_Builder::escape($tp));
				}
				// Format params array into string e.g. '("abc","def","ghi")'
				$res='ENUM("'.implode('","', $params).'")';
				break;
			default:
				throw new Exception('Unknown datatype');
		}
		return $res;
	}


	/**
	 * Get the mysql data type label of a string containing a mysql data
	 * type label (e.g. 'decimal') and data type parameter (e.g. '(10,2)')
	 * @param string $type
	 * @return string data typpe label
	 */
	public static function extractDataTypeLabel(string $datatype): string
	{
		return substr($datatype, 0, strpos($datatype, '('));
	}


	/**
	 * Get mysql column data type of a given column from a given visit type
	 * @param Visit_Type $vt
	 * @param string $columnName
	 * @return string mysql data type with parameters
	 */
	public static function getColumnDataType(Visit_Type $vt, string $columnName): string
	{
		$columns=$vt->getSpecificTableInputType();
		foreach ($columns as $c) {
			// Retrieving original data type
			if ($c['COLUMN_NAME'] == $columnName) {
				return $c['COLUMN_TYPE'];
			}
		}
		throw new Exception('Cannot find column '.$columnName.' for visit type '.$vt);
	}


	/**
	 * Escape special chars from a given string
	 * @param string $s string to escape chars from
	 * @return string escaped string
	 */
	public static function escape(string $s): string
	{
		return preg_replace('/[^A-Za-z0-9\-_]/', '', $s);
	}
}
