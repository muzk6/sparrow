<?php

namespace App\Services;

use App\Models\DemoModel;
use Core\BaseService;

class DemoService extends BaseService
{
    protected $demoModel;

    public function __construct(DemoModel $demoModel)
    {
        $this->demoModel = $demoModel;
    }

    public function foo()
    {
        $ds = $this->demoModel->selectOne(null);
        return $ds;
    }

}
