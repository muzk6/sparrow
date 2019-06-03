<?php


namespace App\Events;


use App\Models\DemoModel;
use Core\BaseEvent;

/**
 * 样例事件
 * @package App\Events
 */
class DemoEvent extends BaseEvent
{
    public function listen(string $name, DemoModel $demoModel)
    {
        return $demoModel->selectOne(['name like ?', "{$name}%"]);
    }
}
