<?php

namespace Basttyy\FxDataServer\Models;
require_once __DIR__."/../libs/helpers.php";

use Basttyy\FxDataServer\libs\Interfaces\ModelInterface;
use Basttyy\FxDataServer\libs\Interfaces\UserModelInterface;
use Basttyy\FxDataServer\libs\mysqly;
use Basttyy\FxDataServer\libs\Traits\InitsModelEvents;
use Basttyy\FxDataServer\libs\Traits\QueryBuilder;

abstract class Model implements ModelInterface
{
    use QueryBuilder, InitsModelEvents;

    /**
     * Create a new model instance.
     *
     * @param array  $attributes
     * @param self|self&UserModelInterface $child
     * @return void
     */
    public function __construct(array $values = [], $child = null)
    {
        $this->child = $child;
        mysqly::auth(env('DB_USER'), env('DB_PASS'), env('DB_NAME'), env('DB_HOST'));
        $this->prepareModel($values);
    }
}