# clue/phar-composer [![Build Status](https://travis-ci.org/clue/phar-composer.png?branch=master)](https://travis-ci.org/clue/phar-composer)

Simple phar creation for your projects managed via composer.

It takes your existing project's `composer.json` and builds an executable phar
for your project among with its bundled dependencies.

* Create a single executable phar archive, including its dependencies
* Automated build process
* Zero additional configuration 

> Note: This project is in early alpha stage! It's been tested against a wide range
of packages and we have yet to find any major issues. Given the current lack of unit
tests, it's likely we're missing some edge cases though. Feel free to report any issues you encounter.

## Usage

Once clue/phar-composer is [installed](#install), you can simply invoke it via command line like this:

```bash
$ php phar-composer.phar build ~/path/to/your/project
```

## Install

You can grab a copy of clue/phar-composer in either of the following ways.

### As a phar (recommended)

You can simply download a pre-compiled and ready-to-use version as a Phar
to any directory:

```bash
$ wget http://www.lueck.tv/phar-composer/phar-composer.phar
```


> If you prefer a global (system-wide) installation without having to type the `.phar` extension
each time, you may simply invoke:
> 
> ```bash
> $ chmod 0755 phar-composer.phar
> $ sudo mv phar-composer.phar /usr/local/bin/phar-composer`
> ```
>
> You can verify everything works by running:
> 
> ```bash
> $ phar-composer --version
> ```

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

#### Updating manually
```bash
$ git pull
$ php composer.phar update
```

## License

MIT

