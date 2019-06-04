<?php


namespace Core;


abstract class BaseYar
{
    public function __call($name, $arguments)
    {
        $ref = new \ReflectionClass(static::class);

        foreach ($ref->getMethod($name)->getParameters() as $parameter) {
            $class = $parameter->getClass();
            if ($class) {
                $arguments[] = app($class->getName());
            }
        }

        return $this->$name(...$arguments);
    }

}
