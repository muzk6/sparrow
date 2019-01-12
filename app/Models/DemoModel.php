<?php

namespace App\Models;

use Core\BaseModel;

class DemoModel extends BaseModel
{
    protected $table = 'test';

    public function fetch()
    {
        $ds = app_pdo()->query('select * from test where id = 1')->fetch(2);
        return $ds;
    }

}