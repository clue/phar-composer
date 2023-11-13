# Changelog

## 1.4.0 (2022-02-14)

*   Feature: Windows support, improve package path detection, and retry rename to fix slow network drives.
    (#129 and #130 by @clue)

*   Feature / Fix: Escape binary path when executing system binaries (git, php, composer).
    (#128 by @clue)

*   Drop legacy HHVM support due to lack of support.
    (#127 by @clue)

## 1.3.0 (2021-12-29)

*   Feature: Support Symfony 6 and PHP 8.1 release.
    (#122 and #123 by @clue)

*   Feature: Bundle `StubGenerator` and `Extract` from legacy herrera-io/box v1.6.1.
    (#119 by @clue)

*   Feature / Fix: Fix check for valid package URL.
    (#117 by @icedream)

*   Update project setup, use PSR-4 autoloading, drop `composer.lock` and instead lock PHP version when building phar.
    (#120 and #124 by @clue and #118 by @PaulRotmann)

*   Improve test suite and add `.gitattributes` to exclude dev files from exports.
    Update test suite to support PHPUnit 9 and test against PHP 8.1 release.
    (#121 and #125 by @clue)

## 1.2.0 (2020-12-11)

*   Feature: Support Composer 2.0!
    (#114 by @thojou and @clue)

*   Minor documentation improvements and simplify install instructions.
    (#98 and #99 by @clue)

*   Use GitHub actions for continuous integration (CI).
    (#112 by @SimonFrings and #113 by @szepeviktor)

## 1.1.0 (2019-11-22)

*   Feature: Update all dependencies and improve forward compatibility with symfony/console v5 through legacy v2.5.
    (#87 by @clue)

*   Feature: Significantly improve performance when adding phar contents.
    (#90 by @clue)

*   Feature: Support cloning projects from git SSH URLs.
    (#96 by @clue)

*   Feature: Ignore packages without autoload definition and missing vendor directory.
    (#94 by @clue)

*   Feature: Write phar to temporary file to support any extension and overwriting.
    (#93 by @clue)

*   Feature / Fix: Disable install subcommand on Windows.
    (#95 by @clue)

*   Improve test suite by adding PHPUnit to require-dev and support legacy PHP 5.3 through PHP 7.2 and HHVM,
    add tests for all commands and perform some minor code cleanup/maintenance,
    minor internal refactoring to clean up some unneeded code duplication and unneeded references and
    remove dedicated bundler classes, always bundle complete package.
    (#85, #86, #89 and #92 by @clue)

*   Add build script removing unneeded files and update development docs.
    (#91 by @clue)

## 1.0.0 (2015-11-15)

*   First stable release, now following SemVer.

*   Feature: Can now be installed as a `require-dev` Composer dependency and
    supports running as `./vendor/bin/phar-composer`.
    (#36 by @radford)

*   Fix: Actually exclude `vendor/` directory. This prevents processing all
    vendor files twice and reduces build time by 50%.
    (#38 by @radford)

*   Fix: Fix error reporting when processing invalid project paths.
    (#56 by @staabm and @clue)

*   Fix: Fix description of `phar-composer install` command.
    (#47 by @staabm)

*   Updated documentation, tests and project structure.
    (#54, #57, #58 and #59 by @clue)

## 0.5.0 (2014-07-10)

*   Feature: The `search` command is the new default if you do not pass any command
    ([#13](https://github.com/clue/phar-composer/pull/13)).
    You can now use the following command to get started:

    ```bash
    $ phar-composer
    ```

*   Fix: Pass through STDERR output of child processes instead of aborting
    ([#33](https://github.com/clue/phar-composer/pull/33))

*   Fix: Do not timeout when child process takes longer than 60s.
    This also helps users with slower internet connections.
    ([#31](https://github.com/clue/phar-composer/pull/31))

*   Fix: Update broken dependencies
    ([#18](https://github.com/clue/phar-composer/pull/18))

*   Fix: Fixed an undocumented config key
    ([#14](https://github.com/clue/phar-composer/pull/14), thanks @mikey179)

## 0.4.0 (2013-09-12)

*   Feature: New `install` command will now both build the given package and then
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

*   Feature: New `search` command provides an interactive command line search.
    It will ask for the package name and issue an search via packagist.org's API and
    present a list of matching packages. So if you don't know the exact package name,
    you can now use the following command:

    ```bash
    $ phar-composer search boris
    ```

*   Feature: Both `build` and `install` commands now also optionally accept an
    additional target directory to place the resulting phar into.

## 0.3.0 (2013-08-21)

*   Feature: Resulting phar files can now be executed on systems without
    ext-phar (#8). This vastly improves portability for legacy setups by including
    a small startup script which self-extracts the current archive into a temporary
    directory.

*   Feature: Resulting phar files can now be executed without the phar file name
    extension. E.g. this convenient feature now allows you to move your `~demo.phar`
    to `/usr/bin/demo` for easy system wide installations.

*   Fix: Resolving absolute paths to `vendor/autoload.php`

## 0.2.0 (2013-08-15)

*   Feature: Packages can now also be cloned from any git URLs (#9), like this:

    ```bash
    $ phar-composer build https://github.com/clue/phar-composer.git
    ```

    The above will clone the repository and check out the default branch.
    You can also specify either a tag or branch name very similar to how composer works:

    ```bash
    $ phar-composer build https://github.com/clue/phar-composer.git:dev-master
    ```

## 0.1.0 (2013-08-12)

*   Feature: Packages listed on packagist.org can now automatically be downloaded and installed
    prior to generating phar (#7), like this:

    ```bash
    $ phar-composer build clue/phar-composer
    ```

    The above will download and install the latest stable tagged release (if any).
    You can also specify a tagged version like this:

    ```bash
    $ phar-composer build clue/phar-composer:0.1.*
    ```

    Or you can specify to install the head of a given branch like this:

    ```bash
    $ phar-composer build clue/phar-composer:dev-master
    ```

## 0.0.2 (2013-05-25)

*   Feature: Bundle complete project directories

## 0.0.1 (2013-05-18)

*   First tagged release

