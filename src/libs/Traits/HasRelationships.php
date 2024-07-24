<?php

namespace Basttyy\FxDataServer\libs\Traits;

use Basttyy\FxDataServer\libs\Interfaces\ModelRelationshipInterface;
use Basttyy\FxDataServer\libs\Str;
use Exception;

trait HasRelationships
{
    public function hasOne(ModelRelationshipInterface | string $class_name, $foreign_key = null, $local_key = null)
    {
        try {
            $classname = get_called_class();
            $foreign_model = new $class_name;

            $foreign_key = $foreign_key ?? Str::lower($classname) . '_id';
            $local_key = $local_key ?? 'id';

            $foreign_model = $foreign_model->where($foreign_key, $this->{$local_key})->first();

            if (!$foreign_model) {
                return null;
            }
            return $foreign_model;
        } catch (Exception $e) {
            logger()->error("got the following error: ".$e->getMessage(), $e->getTrace());
        }
    }

    public function hasMany(ModelRelationshipInterface | string $class_name, $foreign_key = null, $local_key = null)
    {
        try {
            $classname = get_called_class();
            $foreign_model = new $class_name;

            $foreign_key = $foreign_key ?? Str::lower($classname) . '_id';
            $local_key = $local_key ?? 'id';

            $foreign_models = $foreign_model->where($foreign_key, $this->{$local_key})->all();

            if (!$foreign_models) {
                return null;
            }
            $models = [];

            foreach ($foreign_models as $model) {
                $models[] = $foreign_model->fill($model);
            }
            return $models;
        } catch (Exception $e) {
            logger()->error("got the following error: ".$e->getMessage(), $e->getTrace());
        }
    }

    public function belongsTo(ModelRelationshipInterface | string $class_name, $foreign_key = null, $local_key = null)
    {
        try {
            $parent_model = new $class_name;

            $foreign_key = $foreign_key ?? Str::lower($parent_model) . '_id';
            $local_key = $local_key ?? 'id';

            $parent_model = $parent_model->where($local_key, $this->{$foreign_key})->first();

            if (!$parent_model) {
                return null;
            }
            return $parent_model;
        } catch (Exception $e) {
            logger()->error("got the following error: ".$e->getMessage(), $e->getTrace());
        }
    }

    public function belongsToMany(ModelRelationshipInterface | string $class_name, $foreign_key = null, $local_key = null)
    {
        try {
            $parent_model = new $class_name;

            $foreign_key = $foreign_key ?? Str::lower($parent_model) . '_id';
            $local_key = $local_key ?? 'id';

            $parent_model = $parent_model->where($local_key, $this->{$foreign_key})->first();

            if (!$parent_model) {
                return null;
            }
            return $parent_model;
        } catch (Exception $e) {
            logger()->error("got the following error: ".$e->getMessage(), $e->getTrace());
        }
    }
}