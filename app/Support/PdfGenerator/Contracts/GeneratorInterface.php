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
     * @return mixed
     */
    public function outputFromHtml($html, $path, $options);
}
