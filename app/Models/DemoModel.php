<?php

namespace App\Models;

use Core\BaseModel;

/**
 * 样例模型
 * @package App\Models
 */
class DemoModel extends BaseModel
{
    public const DB_NAME = 'test';
    public const TABLE_NAME = 'test';

    public function sharding($index)
    {
        return [
            'section' => static::SECTION . "_{$index}",
            'db_name' => static::DB_NAME . "_{$index}",
            'table_name' => static::TABLE_NAME . "_{$index}",
        ];
    }

}
