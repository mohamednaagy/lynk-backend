<?php

namespace App\Support\PdfGenerator;

use App\Support\PdfGenerator\Generators\BrowserlessGenerator;
use App\Support\PdfGenerator\Generators\FakeGenerator;

class PdfGeneratorManager
{
    protected $app;

    /**
     * Resolved generators
     *
     * @var \App\Support\PdfGenerator\Contracts\GeneratorInterface
     */
    protected $generators = [];

    /**
     * Create instance
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get the generator
     *
     * @param  string|null  $name
     * @return \App\Support\PdfGenerator\Contracts\GeneratorInterface
     */
    public function generator($name = null)
    {
        $name = $name ?: $this->getDefaultGenerator();

        return $this->generators[$name] = $this->get($name);
    }

    protected function getDefaultGenerator()
    {
        return $this->app['config']['pdf.default'];
    }

    /**
     * Attempt to get the disk from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function get($name)
    {
        return $this->generators[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given disk.
     *
     * @param  string  $name
     * @param  array|null  $config
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name, $config = null)
    {
        $config = $config ?? $this->getConfig($name);

        $driverMethod = 'create'.ucfirst($name).'Generator';

        if (! method_exists($this, $driverMethod)) {
            throw new \InvalidArgumentException("Generator [{$name}] is not supported.");
        }

        return $this->{$driverMethod}($config);
    }

    protected function createBrowserlessGenerator($config)
    {
        return new BrowserlessGenerator($config);
    }

    protected function createFakeGenerator($config)
    {
        return new FakeGenerator($config);
    }

    /**
     * Get the filesystem connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["pdf.generators.{$name}"] ?: [];
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->generator()->$method(...$parameters);
    }
}
