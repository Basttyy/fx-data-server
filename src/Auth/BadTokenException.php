<?php declare(strict_types=1);

namespace Basttyy\FxDataServer\Auth;

use Exception;
use RuntimeException;

final class BadTokenException extends RuntimeException
{
    public function __construct(Exception $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }
}