# CakePHP Acl Plugin

[![Build Status](https://img.shields.io/travis/cakephp/acl/master.svg?style=flat-square)](https://travis-ci.org/cakephp/acl)
[![Coverage Status](https://img.shields.io/codecov/c/github/cakephp/acl.svg?style=flat-square)](https://codecov.io/github/cakephp/acl)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)

A plugin for managing ACL in CakePHP applications.

## Installing via composer

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require cakephp/acl
```

Then in your `src/Application.php`:
```php
public function bootstrap()
{
    parent::bootstrap();
    $this->addPlugin('Acl');
}
```

## Creating tables

To create the ACL requires tables, run the following `Migrations` command:

```
bin/cake migrations migrate -p Acl
```

## Attaching the behavior

Add the `Acl` behavior to your table so it will automatically create an `aco` whenever a new record is saved:

```php
public function initialize(array $config)
{
    parent::initialize($config);

    $this->addBehavior('Acl.Acl', ['controlled']);
}
```

## Updating the entity

Before you can start using the behavior, you MUST add the `parentNode()` method to the corresponding `Entity` file or the `AclBehavior` will not be able to determine the parent->child relationships. Also make make sure the method returns either null or a parent Model reference.

```php
public function parentNode() {
    return null;
}
```

> If things went well you should now see an entry appearing in the
> `acos` database table whenever you save a new record.

## Running tests

Assuming you have PHPUnit installed system wide using one of the methods stated
[here](http://phpunit.de/manual/current/en/installation.html), you can run the
tests for the Acl plugin by doing the following:

1. Copy `phpunit.xml.dist` to `phpunit.xml`
2. Add the relevant database credentials to your phpunit.xml if you want to run tests against
   a non-SQLite datasource.
3. Run `phpunit`
