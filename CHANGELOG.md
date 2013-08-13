# CHANGELOG

This file is a manually maintained list of changes for each release. Feel free
to add your changes here when sending pull requests. Also send corrections if
you spot any mistakes.

## 0.2.0 (2013-xx-xx)

* Feature: Packages can now also be cloned from any git URLs (#9), like this:

```bash
$ phar-composer build https://github.com/clue/phar-composer.git
```

The above will clone the repository and check out the default branch.
You can also specify either a tag or branch name very similar to how composer works:

```bash
$ phar-composer build https://github.com/clue/phar-composer.git:dev-master
```

## 0.1.0 (2013-08-12)

* Feature: Packages listed on packagist.org can now automatically be downloaded and installed
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

* Feature: Bundle complete project directories

## 0.0.1 (2013-05-18)

* First tagged release

