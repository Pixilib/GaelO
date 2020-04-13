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
 * Iterate in Visit Type order in a Visit Group
 */
class Visit_Type_Iterator implements Iterator
{

	private array $visitsTypeArray=[];
	private int $index=0;

	public function __construct(array $visitTypeArray)
	{
		$this->visitsTypeArray=$visitTypeArray;
	}

	public function setVisitPosition(String $visitName)
	{
		$visitNameArray=array_map(function(Visit_Type $visitTypeObject) {
			return $visitTypeObject->name;
		}, $this->visitsTypeArray);

		$this->index=array_search($visitName, $visitNameArray);
	}


	public function current(): Visit_Type
	{
		return $this->visitsTypeArray[$this->index];
	}

	public function previous()
	{
		$this->index--;
	}

	public function hasPrevious()
	{
		$this->previous();
		if ($this->valid()) return $this->current();
		else return false;
	}

	public function next()
	{
		$this->index++;
	}

	public function hasNext()
	{
		$this->next();
		if ($this->valid()) return $this->current();
		else return false;
	}

	public function rewind()
	{
		$this->index=0;
	}

	public function key(): int
	{
		return $this->index;
	}

	public function valid(): bool
	{
		return isset($this->visitsTypeArray[$this->key()]);
	}
}
