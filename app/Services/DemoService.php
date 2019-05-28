<?php

namespace App\Services;

use App\Models\DemoModel;
use Core\BaseService;

class DemoService extends BaseService
{
    protected $model;

    public function __construct(DemoModel $demoModel)
    {
        $this->model = $demoModel;
    }


    public function foo()
    {
        return $this->model->db()->selectAll('id', null);
        return 'bar';
    }

}