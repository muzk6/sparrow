<?php

namespace App\Models;

use Core\BaseModel;

class DemoModel extends BaseModel
{
    protected static $dd = null;
    protected $table = 'test';

    public function __construct()
    {
        if (!self::$dd) {
            var_dump('static');
            self::$dd = 'aa';
        }
        var_dump('model');
    }


}