<?php

namespace App\Support\PdfGenerator;

use Closure;
use Illuminate\Support\Facades\Facade;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @method static \App\Support\PdfGenerator\Contracts\GeneratorInterface generator($name = null)
 * @method static string|Media outputFromHtml(string $html, string $path, array|Closure $options)
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
