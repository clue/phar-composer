<?php

namespace Clue\PharComposer\Package;

/**
 * Provides access to pathes defined in package autoload configuration.
 *
 * @see Package
 */
class Autoload
{
    /**
     * package autoload definition
     *
     * @type  array
     */
    private $autoload;

    public function __construct(array $autoload)
    {
        $this->autoload = $autoload;
    }

    /**
     * returns all pathes defined by PSR-0
     *
     * @return  string[]
     */
    public function getPsr0()
    {
        if (!isset($this->autoload['psr-0'])) {
            return array();
        }

        $resources = array();
        foreach ($this->autoload['psr-0'] as $namespace => $paths) {
            foreach($this->correctSinglePath($paths) as $path) {
                // TODO: this is not correct actually... should work for most repos nevertheless
                // TODO: we have to take target-dir into account
                $resources[] = $this->buildNamespacePath($namespace, $path);
            }
        }

        return $resources;
    }

    /**
     * corrects a single path into a list of pathes
     *
     * PSR autoloader may define a single or multiple paths.
     *
     * @param   string|string[]  $paths
     * @return  string[]
     */
    private function correctSinglePath($paths)
    {
        if (is_array($paths)) {
            return $paths;
        }

        return array($paths);
    }

    /**
     * builds namespace path from given namespace and given path
     *
     * @param   string  $namespace
     * @param   string  $path
     * @return  string
     */
    private function buildNamespacePath($namespace, $path)
    {
        if ($namespace === '') {
            return $path;
        }

        $namespace = str_replace('\\', '/', $namespace);
        if ($path === '') {
            // namespace in project root => namespace is path
            return $namespace;
        }

        // namespace in sub-directory => add namespace to path
        return rtrim($path, '/') . '/' . $namespace;
    }

    /**
     * returns list of class file resources
     *
     * @return  string[]
     */
    public function getClassmap()
    {
        if (!isset($this->autoload['classmap'])) {
            return array();
        }

        return $this->autoload['classmap'];
    }

    /**
     * returns list of files defined in autoload
     *
     * @return  string[]
     */
    public function getFiles()
    {
        if (!isset($this->autoload['files'])) {
            return array();
        }

        return $this->autoload['files'];
    }
}
