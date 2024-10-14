<?php
namespace App\Traits;

trait HandlesFractal
{
    protected function getFieldsForRole($role, $controller, $action)
    {
        return config("fractal_fields.$controller.$action.$role", []);
    }

    protected function formatResponse($data, $transformer, $fields)
    {
        return fractal($data, $transformer)
            ->parseIncludes($fields)
            ->respond();
    }
}