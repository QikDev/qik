<?php

namespace Qik\Database;

class DBObjectIterator implements \Iterator
{
    private $var = array();

    public function __construct($array)
    {
        if (is_array($array)) 
            $this->var = $array;
    }

    public function rewind() : void
    {
        reset($this->var);
    }
  
    public function current() : mixed
    {
        return current($this->var);
    }
  
    public function key() : mixed
    {
        return key($this->var);
    }
  
    public function next()  : void
    {
        next($this->var);
    }
  
    public function valid() : bool
    {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);

        return $var;
    }

}
