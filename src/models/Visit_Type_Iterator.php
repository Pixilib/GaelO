<?php

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
        if ($this->valid()) {
        	return $this->current();
        } else {
        	return false;
        }
    }

    public function next()
    {
        $this->index++;
    }

    public function hasNext()
    {
        $this->next();
        if ($this->valid()) {
        	return $this->current();
        } else {
        	return false;
        }
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
