<?php

namespace Basttyy\FxDataServer\libs\Interfaces;

interface UserModelInterface
{
    /**
     * Find a user by the username
     * @param string $name 'username to find in user table'
     * @param bool $is_protected 'wether to hide or show protected values'
     * 
     * @param string $name
     * @return self|false
     */
    public function findByUsername($name, $is_protected = true);

    /**
     * Find a user by the email
     * @param string $email 'email to find in user table'
     * @param bool $is_protected 'wether to hide or show protected values'
     * 
     * @param string $email
     * @return self|false
     */
    public function findByEmail(string $email, $is_protected = true);
}