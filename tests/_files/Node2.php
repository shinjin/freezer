<?php
class Node2
{
    public $parent;
    public $children = array();
    public $payload;

    public function __construct($payload, Node2 $parent = null)
    {
        $this->payload = $payload;
        $this->parent  = $parent;

        if ($this->parent !== null) {
            $this->parent->addChild($this);
        }
    }

    public function addChild(Node2 $child)
    {
        $this->children[] = $child;
    }
}
