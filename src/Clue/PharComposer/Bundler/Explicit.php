<?php

namespace Clue\PharComposer\Bundler;

use Herrera\Box\Box;

class Explicit extends Base
{
    protected function bundle()
    {
        foreach ($this->package->getBins() as $bin) {
            $this->addFile($bin);
        }

        $autoload = $this->package->getAutoload();

        if ($autoload !== null) {
            if (isset($autoload['psr-0'])) {
                foreach ($autoload['psr-0'] as $namespace => $paths) {
                    if (!is_array($paths)) {
                        // PSR autoloader may define a single or multiple paths
                        $paths = array($paths);
                    }
                    foreach($paths as $path) {
                        if ($namespace !== '') {
                            // namespace given, add it to the path
                            $namespace = str_replace('\\', '/', $namespace);

                            if ($path === '') {
                                // namespace in project root => namespace is path
                                $path = $namespace;
                            } else {
                                // namespace in sub-directory => add namespace to path
                                $path = rtrim($path, '/') . '/' . $namespace;
                            }
                        }

                        // TODO: this is not correct actually... should work for most repos nevertheless
                        // TODO: we have to take target-dir into account

                        $this->addDirectory($this->package->getAbsolutePath($path));
                    }
                }
            }

            if (isset($autoload['classmap'])) {
                foreach($autoload['classmap'] as $path) {
                    $path = $this->package->getAbsolutePath($path);
                    if (is_dir($path)) {
                        $this->addDirectory($path);
                    } else {
                        $this->addFile($path);
                    }
                }
            }

            if (isset($autoload['files'])) {
                foreach($autoload['files'] as $path) {
                    $this->addFile($this->package->getAbsolutePath($path));
                }
            }
        }
    }
}
