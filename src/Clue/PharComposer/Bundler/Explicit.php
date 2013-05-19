<?php

namespace Clue\PharComposer\Bundler;

use Herrera\Box\Box;

class Explicit extends Base
{
    protected function bundle()
    {
        $main = $this->pharcomposer->getMain();
        if ($main !== null) {
            $this->addFile($main);
        }

        $autoload = $this->pharcomposer->getPackageAutoload();

        if ($autoload !== null) {
            if (isset($autoload['psr-0'])) {
                foreach ($autoload['psr-0'] as $namespace => $path) {
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

                    $this->addDirectory($this->pharcomposer->getAbsolutePathForComposerPath($path));
                }
            }

            if (isset($autoload['classmap'])) {
                foreach($autoload['classmap'] as $path) {
                    $path = $this->getAbsolutePathForComposerPath($path);
                    if (is_dir($path)) {
                        $this->addDirectory($path);
                    } else {
                        $this->addFile($path);
                    }
                }
            }

            if (isset($autoload['files'])) {
                foreach($autoload['classmap'] as $path) {
                    $this->addFile($this->pharcomposer->getAbsolutePathForComposerPath($path));
                }
            }
        }

        $this->addDirectory($this->pharcomposer->getPathVendor());
    }
}
