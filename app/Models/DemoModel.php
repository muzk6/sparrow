<?php

namespace App\Models;

use Core\BaseModel;

class DemoModel extends BaseModel
{
    protected $table = 'test';

    public function fetch()
    {
        return ['name' => 'X'];
    }

}