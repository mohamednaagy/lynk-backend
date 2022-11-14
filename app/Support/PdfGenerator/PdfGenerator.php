<?php

namespace App\Support\PdfGenerator;

use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Support\PdfGenerator\Contracts\GeneratorInterface generator($name = null)
 * @method static mixed outputFromHtml(string $html, string $path, array|Closure $options)
 *
 * @see \App\Support\PdfGenerator\PdfGeneratorManager
 */
class PdfGenerator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}
