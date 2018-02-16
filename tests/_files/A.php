<?php
class A
{
    public $a;
    protected $b;
    private $c;

    public function __construct($a, $b, $c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }

    public function getValues()
    {
        return array($this->a, $this->b, $this->c);
    }
}
