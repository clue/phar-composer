# clue/phar-composer

[![CI status](https://github.com/clue/phar-composer/workflows/CI/badge.svg)](https://github.com/clue/phar-composer/actions) 
[![downloads on GitHub](https://img.shields.io/github/downloads/clue/phar-composer/total?color=blue&label=downloads%20on%20GitHub)](https://github.com/clue/phar-composer/releases)
[![installs on Packagist](https://img.shields.io/packagist/dt/clue/phar-composer?color=blue&label=installs%20on%20Packagist)](https://packagist.org/packages/clue/phar-composer)  

Simple phar creation for any project managed via Composer.

It takes your existing project's `composer.json` and builds an executable phar
for your project among with its bundled dependencies.

* Create a single executable phar archive, including its dependencies (i.e. vendor directory included)
* Automated build process
* Zero additional configuration

**Table of contents**

* [Support us](#support-us)
* [Usage](#usage)
  * [phar-composer](#phar-composer)
  * [phar-composer build](#phar-composer-build)
  * [phar-composer install](#phar-composer-install)
  * [phar-composer search](#phar-composer-search)
* [Install](#install)
  * [As a phar (recommended)](#as-a-phar-recommended)
  * [Installation using Composer](#installation-using-composer)
* [Development](#development)
* [Tests](#tests)
* [License](#license)

## Support us

We invest a lot of time developing, maintaining and updating our awesome
open-source projects. You can help us sustain this high-quality of our work by
[becoming a sponsor on GitHub](https://github.com/sponsors/clue). Sponsors get
numerous benefits in return, see our [sponsoring page](https://github.com/sponsors/clue)
for details.

Let's take these projects to the next level together! 🚀

## Usage

Once clue/phar-composer is [installed](#install), you can use it via command line like this.

### phar-composer

This tool supports several sub-commands. To get you started, you can now use the following simple command:

```bash
$ phar-composer
```

This will actually execute the `search` command that allows you to interactively search and build any package
listed on packagist (see below description of the [search command](#phar-composer-search) for more details).

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
$ phar-composer build clue/phar-composer:~1.0
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
>
> Windows limitation: Note that this subcommand is not available on Windows.
  Please use the `build` command and place Phar in your `$PATH` manually.

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

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 8+.
It's *highly recommended to use the latest supported PHP version* for this project.

### As a phar (recommended)

You can simply download a pre-compiled and ready-to-use version as a Phar
to any directory.
You can simply download the latest `phar-composer.phar` file from our
[releases page](https://github.com/clue/phar-composer/releases).
The [latest release](https://github.com/clue/phar-composer/releases/latest) can
always be downloaded like this:

```bash
$ curl -JOL https://clue.engineering/phar-composer-latest.phar
```

That's it already. Once downloaded, you can verify everything works by running this:

```bash
$ cd ~/Downloads
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

There's no separate `update` procedure, simply download the latest release again
and overwrite the existing phar.

Again, if you have already installed phar-composer system-wide, updating is as easy as
running a self-installation like this:

```bash
$ phar-composer install clue/phar-composer
```

### Installation using Composer

Alternatively, you can also install phar-composer as part of your development dependencies.
You will likely want to use the `require-dev` section to exclude phar-composer in your production environment.

You can either modify your `composer.json` manually or run the following command to include the latest tagged release:

```bash
$ composer require --dev clue/phar-composer
```

Now you should be able to invoke the following command in your project root:

```bash
$ vendor/bin/phar-composer --version
```

> Note: You should only invoke and rely on the main phar-composer bin file.
Installing this project as a non-dev dependency in order to use its
source code as a library is *not supported*.

To update to the latest release, just run `composer update clue/graph-composer`.

## Development

clue/phar-composer is an [open-source project](#license) and encourages everybody to
participate in its development.
You're interested in checking out how clue/phar-composer works under the hood and/or want
to contribute to the development of clue/phar-composer?
Then this section is for you!

The recommended way to install clue/phar-composer is to clone (or download) this repository
and use [Composer](https://getcomposer.org/) to download its dependencies.
Therefore you'll need PHP, Composer, git and curl installed.
For example, on a recent Ubuntu/Debian-based system, simply run:

```bash
$ sudo apt install php-cli git curl

$ git clone https://github.com/clue/phar-composer.git
$ cd phar-composer

$ curl -s https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer

$ composer install
```

You can now verify everything works by running clue/phar-composer like this:

```bash
$ php bin/phar-composer --version
```

If you want to distribute clue/phar-composer as a single standalone release file, you may
compile the project into a single `phar-composer.phar` file like this:

```bash
$ composer build
```

> Note that compiling will temporarily install a copy of this project to the
  local `build/` directory and install all non-development dependencies
  for distribution. This should only take a second or two if you've previously
  installed its dependencies already.
  The build script optionally accepts the version number (`VERSION` env) and
  an output file name or will otherwise try to look up the last release tag,
  such as `phar-composer-1.0.0.phar`.

You can now verify the resulting `phar-composer.phar` file works by running it
like this:

```bash
$ php phar-composer.phar --version
```

To update your development version to the latest version, just run this:

```bash
$ git pull
$ composer install
```

Made some changes to your local development version?

Make sure to let the world know! :shipit:
We welcome PRs and would love to hear from you!

Happy hacking!

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org/):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ vendor/bin/phpunit
```

## License

This project is released under the permissive [MIT license](LICENSE).

This project bundles the `StubGenerator` and `Extract` classes with minor changes
from the original herrera-io/box v1.6.1 licensed under MIT which no longer has
an installable candidate. Copyright (c) 2013 Kevin Herrera.

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
