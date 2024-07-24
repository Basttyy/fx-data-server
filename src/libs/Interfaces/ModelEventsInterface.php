<?php

namespace Basttyy\FxDataServer\libs\Interfaces;

interface ModelEventsInterface
{
    public static function boot(ModelInterface | null $model);

    public static function booting(ModelInterface | null $model);

    public static function booted(ModelInterface | null $model);

    public static function creating($model, callable $callback);

    public static function created($model, callable $callback);

    public static function saving($model, callable $callback);

    public static function saved();

    public static function deleting();

    public static function deleted();
}