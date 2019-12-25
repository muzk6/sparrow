<?php


namespace App\Services;


use Core\BaseService;

class DemoService extends BaseService
{
    /**
     * 示例
     * @return string
     * @throws \Core\AppException
     */
    public function foo()
    {
        $check = true;
        if (!$check) {
            panic(10001000);
        }

        return 'foo';
    }
}
