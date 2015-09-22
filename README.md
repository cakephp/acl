# CakePHP Acl Plugin

[![Build Status](https://api.travis-ci.org/cakephp/acl.png)](https://travis-ci.org/cakephp/acl)
[![License](https://poser.pugx.org/cakephp/acl/license.svg)](https://packagist.org/packages/cakephp/acl)

A plugin for managing ACL in CakePHP applications.

Note:
This is a non-stable plugin for CakePHP 3.0 at this time.
It is currently under development and should be considered experimental.

## Installing via composer

You can install this plugin into your CakePHP application using
[composer](http://getcomposer.org). For existing applications you can add the
following to your `composer.json` file:

```javascript
"require": {
	"cakephp/acl": "dev-master"
}
```

And run `php composer.phar update`

In your `config\bootstrap.php`:
```php
Plugin::load('Acl', ['bootstrap' => true]);
```

## Creating tables

To create ACL related tables, run the following `Migrations` command:

```
bin/cake migrations migrate -p Acl
```

## Running tests

Assuming you have PHPUnit installed system wide using one of the methods stated
[here](http://phpunit.de/manual/current/en/installation.html), you can run the
tests for the Acl plugin by doing the following:

1. Copy `phpunit.xml.dist` to `phpunit.xml`
2. Add the relevant database credentials to your phpunit.xml if you want to run tests against
   a non-SQLite datasource.
3. Run `phpunit`
