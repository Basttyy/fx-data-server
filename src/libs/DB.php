<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Basttyy\FxDataServer\libs;

/**
 * Hybridauth storage manager
 */
class DB
{
    public static function beginTransaction()
    {
        mysqly::beginTransaction();
        $_SESSION['transaction_mode'] = true;
    }

    public static function commit()
    {
        mysqly::commit();
        $_SESSION['transaction_mode'] = false;
    }

    public static function rollback()
    {
        mysqly::rollback();
        $_SESSION['transaction_mode'] = false;
    }
}