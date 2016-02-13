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
            foreach((array)$paths as $path) {
                // TODO: this is not correct actually... should work for most repos nevertheless
                // TODO: we have to take target-dir into account
                $resources[] = $this->buildNamespacePath($namespace, $path);
            }
        }

        return $resources;
    }

    /**
     * Returns all paths defined by PSR-4 block of the autoload configuration.
     *
     * @return string[]
     */
    public function getPsr4()
    {
        // If there is not a psr-4 listing, then just there are no related resources.
        // Return quickly, in this case.
        if (!isset($this->autoload['psr-4'])) {
            return array();
        }

        // Build out the list of resources based on each psr-4 entry.
        $resources = array();
        foreach($this->autoload['psr-4'] as $namespace => $paths) {
            // Normalize single-path configurations and multi-path configurations by casting to an array. Once
            // normalized, loop over the list and capture each path as a resource.
            foreach((array)$paths as $path) {
                // For psr-4 the path is the root for the namespace. List it as a resource, as is.
                $resources[] = $path;
            }
        }

        // Hand back the list of resources.
        return $resources;
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
