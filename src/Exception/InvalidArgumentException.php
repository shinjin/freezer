<?php
namespace Freezer\Exception;


class InvalidArgumentException extends \InvalidArgumentException
{

    public function __construct($argument, $type)
    {
        $stack = debug_backtrace(false);

        parent::__construct(
            sprintf(
                'Argument #%d of %s::%s() is no %s',
                $argument,
                $stack[1]['class'],
                $stack[1]['function'],
                $type
            )
        );
    }

}
