<?php


namespace App\Controllers;


use App\Events\DemoEvent;
use Core\AppException;
use Core\BaseController;

/**
 * @package App\Controllers
 */
class IndexController extends BaseController
{
    public function index(DemoEvent $demoEvent)
    {
        list($req, $err) = input('type:i', 'require')->collect();

        try {
            if ($err) {
                panic(10001000, $err);
            }

            return json_api(true);
        } catch (AppException $exception) {
            return json_api($exception);
        }
    }
}
