<?php

namespace Basttyy\FxDataServer\libs\Traits;

use Basttyy\FxDataServer\libs\Arr;
use Basttyy\FxDataServer\libs\mysqly;
use DateTime;
use Exception;
use PDO;

trait UserAwareQueryBuilder
{
    public function findByUsername($name, $is_protected = true)
    {
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
        }
        $query_arr['username'] = $name;

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$user = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        if (count( $user ) < 1) {
            $this->resetInstance();
            return false;
        }

        $this->resetInstance();
        return $this->fill($user[0]);
    }

    public function findByEmail(string $email, $is_protected = true)
    {
        $query_arr = $this->bind_or_filter === null ? [] : $this->bind_or_filter;

        if ($this->child->softdeletes) {
            $query_arr['deleted_at'] = "IS NULL";
        }
        $query_arr['email'] = $email;

        $fields = $is_protected ? \array_diff($this->fillable, $this->guarded) : $this->fillable;
        if (!$user = mysqly::fetch($this->table, $query_arr, $fields, $this->operators, $this->or_ands)) {
            $this->resetInstance();
            return false;
        }
        if (count( $user ) < 1) {
            $this->resetInstance();
            return false;
        }

        $this->resetInstance();
        return $this->fill($user[0]);
    }
}