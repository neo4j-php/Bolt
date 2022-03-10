![Logo](https://repository-images.githubusercontent.com/198229221/c0edf6c1-8699-481d-85f7-17b6ea0ffb26)

# Bolt
Bolt protocol library over TCP socket. Bolt protocol is created and in use for communication with [Neo4j](https://neo4j.com/) Graph database. The Bolt documentation is available at [https://7687.org/](https://7687.org/). This library is aimed to be low level and keep up with protocol messages architecture and specifications.

![DB Tests PHP7](https://github.com/neo4j-php/Bolt/actions/workflows/db-test-php-7.yml/badge.svg?branch=master)
![DB Tests PHP8](https://github.com/neo4j-php/Bolt/actions/workflows/db-test-php-8.yml/badge.svg?branch=master)
![No DB Tests PHP7](https://github.com/neo4j-php/Bolt/actions/workflows/no-db-test-php-7.yml/badge.svg?branch=master)
![No DB Tests PHP8](https://github.com/neo4j-php/Bolt/actions/workflows/no-db-test-php-8.yml/badge.svg?branch=master)

![](https://img.shields.io/github/stars/stefanak-michal/Bolt)
![](https://img.shields.io/packagist/dt/stefanak-michal/bolt)
![](https://img.shields.io/github/v/release/stefanak-michal/bolt)
![](https://img.shields.io/github/commits-since/stefanak-michal/bolt/latest)

## Version support

We are trying to keep up and this library supports **Neo4j <= 4.4** with **Bolt <= 4.4**.

https://7687.org/#bolt-protocol-and-neo4j-compatibility

## Requirements

Keep up with [supported versions](https://www.php.net/supported-versions.php) means we are at **PHP >= 7.4**.

### Extensions

- mbstring https://www.php.net/manual/en/book.mbstring.php
- sockets https://www.php.net/manual/en/book.sockets.php (optional)
- openssl https://www.php.net/manual/en/book.openssl.php (optional)
- phpunit >= 9 https://phpunit.de/ (development)

## Installation

You can use composer or download this repository.

### Composer

Run the following command to install the latest applicable version of the package:

`composer require stefanak-michal/bolt`

### Manual

1. Download [latest release](https://github.com/neo4j-php/Bolt/releases/latest) or [master](https://github.com/neo4j-php/Bolt)
2. Unpack
3. Copy content of ```src``` directory to your project

## Usage

Concept of usage is based on Bolt messages and mostly you need just these.

```php
<?php
$bolt = new \Bolt\Bolt(new \Bolt\connection\Socket());
$protocol = $bolt->build();
$protocol->hello(\Bolt\helpers\Auth::basic('neo4j', 'neo4j'));
$stats = $protocol->run('RETURN 1 AS num, 2 AS cnt');
$rows = $protocol->pull();
```

[More info](https://github.com/neo4j-php/Bolt/wiki/Usage)

## Another solutions

https://neo4j.com/developer/php/
