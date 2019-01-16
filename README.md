Personal Library
================

The "Personal Library" is a test application for symfony framework learning.

Requirements
------------

  * PHP 7.2 or higher;
  * PHP XML, cURL, mbstring, ZIP extensions enabled;
  * and the [usual Symfony application requirements][1].

Installation
------------


```bash
$ cd personal-library/
$ composer install
$ yarn install
$ yarn encore dev
$ php bin/console make:migration
$ php bin/console doctrine:migrations:migrate
```

Usage
-----

```bash
$ cd personal-library/
$ php bin/console server:run
```

Tests
-----

```bash
$ cd personal-library/
$ ./bin/phpunit
```

[1]: https://symfony.com/doc/current/reference/requirements.html
