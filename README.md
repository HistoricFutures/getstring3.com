Requirements
============

* PHP 5.5+
* php5-sqlite: via PECL, apt-get etc.

Installation
============

Clone the repository: you might need to switch to the `develop` branch while work is in progress. Then run:

`composer install`

And copy the `.env.sample` file to `.env` and tweak for your own preferences.

Testing
=======

Uncomment the `DEBUG` line in `.env` to improve debugging in the browser. Also, the following from within the site root will run unit tests:

`vendor/bin/phpunit`
