<?php

namespace App\Support\PdfGenerator\Contracts;

interface GeneratorInterface
{
    /**
     * output from html file
     *
     * @param  string  $html
     * @param  string  $path
     * @param  array  $options
     * @return string
     */
    public function outputFromHtml($html, $path, $options);
}
