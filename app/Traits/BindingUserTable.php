<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2019-04-11
 * Time: 18:37
 */

namespace App\Traits;

trait BindingUserTable
{
    protected $connection = null;

    public function bind(string $connection)
    {
        $this->setConnection($connection);
        $this->setTable(config('app.db.user_table'));
    }

    public function newInstance($attributes = [], $exists = false)
    {
        // Overridden in order to allow for late table binding.

        $model = parent::newInstance($attributes, $exists);
        $model->setTable(config('app.db.user_table'));

        return $model;
    }

}

