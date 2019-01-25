<?php

namespace App\Services;

use App\Models\DemoModel;
use Core\BaseService;

class DemoService extends BaseService
{
    public function foo()
    {
        return DemoModel::instance()->fetch();
    }

}