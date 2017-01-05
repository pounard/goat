<?php

namespace Goat\Core\Error;

class NotImplementedError extends GoatError
{
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (!$message) {
            $message = sprintf("this method is not implemented");
        }

        parent::__construct($message, $code, $previous);
    }
}
