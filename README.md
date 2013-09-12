# clue/phar-composer [![Build Status](https://travis-ci.org/clue/phar-composer.png?branch=master)](https://travis-ci.org/clue/phar-composer)

Simple phar creation for any project managed via composer.

It takes your existing project's `composer.json` and builds an executable phar
for your project among with its bundled dependencies.

* Create a single executable phar archive, including its dependencies (i.e. vendor directory included)
* Automated build process
* Zero additional configuration 

> Note: This project is in beta stage! It's been tested against a wide range
of packages and we have yet to find any major issues.
Feel free to report any issues you encounter.

## Usage

Once clue/phar-composer is [installed](#install), you can use it via command line like this.

### phar-composer build

The `build` command can be used to build an executable single-file phar (php archive) for any project
managed by composer:

```bash
$ phar-composer build ~/path/to/your/project
```

The second argument can be pretty much everything that can be resolved to a valid project managed by composer.
Besides creating phar archives for locally installed packages like above, you can also easily download and
bundle packages from packagist.org like this:

```bash
$ phar-composer build d11wtq/boris
```

The above will download and install the latest stable tagged release (if any).
You can also specify a tagged version like this:

```bash
$ phar-composer build clue/phar-composer:0.3.*
```

Or you can specify to install the head of a given branch like this:

```bash
$ phar-composer build clue/phar-composer:dev-master
```

A similar syntax can be used to clone a package from any git URL. This is particularly
useful for private packages or temporary git clones not otherwise listed on packagist:

```bash
$ phar-composer build https://github.com/composer/composer.git
```

The above will clone the repository and check out the default branch.
Again, you can specify either a tag or branch name very similar to how composer works:

```bash
$ phar-composer build https://github.com/composer/composer.git:dev-master
```

### phar-composer install

The `install` command will both build the given package and then
install it into the system-wide bin directory `/usr/local/bin` (usually already
in your `$PATH`). This works for any package name or URL just like with the
`build` command, e.g.:

```bash
$ phar-composer install phpunit/phpunit
```

After some (lengthy) build output, you should now be able to run it by just issuing:

```bash
$ phpunit
```

> In essence, the `install` command will basically just issue a `build` and then
`sudo mv $target.phar /usr/local/bin/$target`. It will ask you for your sudo password
when necessary, so it's not needed (and in fact not *recommended*) to run the whole
comamnd via `sudo`.

### phar-composer search

The `search` command provides an interactive command line search.
It will ask for the package name and issue an search via packagist.org's API and
present a list of matching packages. So if you don't know the exact package name,
you can use the following command:

```bash
$ phar-composer search boris
```

It uses an interactive command line menu to ask you for the matching package name,
its version and will then offer you to either `build` or `install` it.

## Install

You can grab a copy of clue/phar-composer in either of the following ways.

### As a phar (recommended)

You can simply download a pre-compiled and ready-to-use version as a Phar
to any directory:

```bash
$ wget http://www.lueck.tv/phar-composer/phar-composer.phar
```

That's it. You can now verify everything works by running:

```bash
$ php phar-composer.phar --version
```

The above usage examples assume you've installed phar-composer system-wide to your $PATH (recommended),
so you have the following options:

1.  Only use phar-composer locally and adjust the usage examples: So instead of
    running `$ phar-composer --version`, you have to type `$ php phar-composer.phar --version`.

2.  Use phar-composer's `install` command to install itself to your $PATH by running:

    ```bash
    $ php phar-composer.phar install clue/phar-composer
    ```

3.  Or you can manually make the `phar-composer.phar` executable and move it to your $PATH by running:

   ```bash
   $ chmod 755 phar-composer.phar
   $ sudo mv phar-composer.phar /usr/local/bin/phar-composer
   ```

If you have installed phar-composer system-wide, you can now verify everything works by running:

```bash
$ phar-composer --version
```

#### Updating phar

There's no separate `update` procedure, simply overwrite the existing phar with the new version downloaded.

### Manual Installation from Source

This project requires PHP 5.3+ and Composer:

```bash
$ git clone https://github.com/clue/phar-composer.git
$ cd phar-composer
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```

You can now verify everything works by running phar-composer like this:

```bash
$ php bin/phar-composer --version
```

Optionally, you can now build the above mentioned `phar-composer.phar` yourself by issuing:

```bash
$ php bin/phar-composer build
```

Optionally, you can now follow the above instructions for a [system-wide installation](#as-a-phar-recommended).


#### Updating manually

```bash
$ git pull
$ php composer.phar install
```

## License

MIT

