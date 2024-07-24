<?php

namespace Basttyy\FxDataServer\libs\Traits;

use Basttyy\FxDataServer\libs\Interfaces\ModelInterface;

trait InitsModelEvents
{
    public static function boot(ModelInterface | null $model)
    {
    }

    public static function booting()
    {
    }

    public static function booted()
    {
    }

    public static function creating($model, callable $callback)
    {
        $callback($model);
    }

    public static function created()
    {
    }

    public static function saving()
    {
    }

    public static function saved()
    {
    }

    public static function deleting()
    {
    }

    public static function deleted()
    {
    }
}