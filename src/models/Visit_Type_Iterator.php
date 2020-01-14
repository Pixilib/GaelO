<?php
class Visit_Type_Iterator implements Iterator{

    private $visitsTypeArray = [];
    private $index = 0;

    public function __construct(Array $visitTypeArray){
        $this->visitsTypeArray= $visitTypeArray;
    }


    public function current()
    {
        return $this->visitsTypeArray[$this->index];
    }

    public function previous(){
        $this->index--;
    }
 
    public function next()
    {
        $this->index++;
    }
 
    public function rewind()
    {
        $this->index = 0;
    }
 
    public function key()
    {
        return $this->index;
    }
 
    public function valid()
    {
        return isset($this->visitsTypeArray[$this->key()]);
    }
      

}