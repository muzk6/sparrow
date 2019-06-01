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

            return api_json(true);
        } catch (AppException $exception) {
            return api_json($exception);
        }
    }
}
