<?php

namespace Basttyy\FxDataServer\libs\Traits;

trait ModelHelpers
{
    use ModelProperties;

    public function isSaved()
    {
        if ($this->child->{$this->child->primaryKey} === 0)
            return false;

        return true;
    }

    public function isNotSaved()
    {
        return !$this->isSaved();
    }
}