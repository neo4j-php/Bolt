![Logo](https://repository-images.githubusercontent.com/198229221/fcf334aa-ef6b-4fe9-89ad-d03e4c7d89e3)

# Bolt
PHP library for communication with [Neo4j](https://neo4j.com/) graph database over TCP socket with Bolt protocol specification. The Bolt documentation is available at [https://www.neo4j.com/](https://www.neo4j.com/docs/bolt/current/). This library is aimed to be low level, support all available versions and keep up with protocol messages architecture and specifications.

![DB Tests PHP7](https://github.com/neo4j-php/Bolt/actions/workflows/db-test-php-7.yml/badge.svg?branch=master)
![DB Tests PHP8](https://github.com/neo4j-php/Bolt/actions/workflows/db-test-php-8.yml/badge.svg?branch=master)
![No DB Tests PHP7](https://github.com/neo4j-php/Bolt/actions/workflows/no-db-test-php-7.yml/badge.svg?branch=master)
![No DB Tests PHP8](https://github.com/neo4j-php/Bolt/actions/workflows/no-db-test-php-8.yml/badge.svg?branch=master)

[![](https://img.shields.io/github/stars/stefanak-michal/Bolt)](https://github.com/neo4j-php/Bolt/stargazers)
[![](https://img.shields.io/packagist/dt/stefanak-michal/bolt)](https://packagist.org/packages/stefanak-michal/bolt/stats)
[![](https://img.shields.io/github/v/release/stefanak-michal/bolt)](https://github.com/neo4j-php/Bolt/releases)
[![](https://img.shields.io/github/commits-since/stefanak-michal/bolt/latest)](https://github.com/neo4j-php/Bolt/releases/latest)

## Version support

We are trying to keep up and this library supports **Neo4j <= 4.4** with **Bolt <= 4.4**.

https://www.neo4j.com/docs/bolt/current/bolt-compatibility/

## Requirements

Keep up with [PHP supported versions](https://www.php.net/supported-versions.php) means we are at **PHP >= 7.4**.

_If you need support for PHP < 7.4 you can use latest v3.x release. Not all new features are implement backwards._

### Extensions

- [mbstring](https://www.php.net/manual/en/book.mbstring.php)
- [sockets](https://www.php.net/manual/en/book.sockets.php) (optional) - Required when you use Socket connection class
- [openssl](https://www.php.net/manual/en/book.openssl.php) (optional) - Required when you use StreamSocket connection class with enabled SSL
- [phpunit](https://phpunit.de/) >= 9 (development)

## Installation

You can use composer or download this repository from github and manually implement it.

### Composer

Run the following command in your project to install the latest applicable version of the package:

`composer require stefanak-michal/bolt`

[Packagist](https://packagist.org/packages/stefanak-michal/bolt)

### Manual

1. Download source code from [github](https://github.com/neo4j-php/Bolt/)
2. Unpack
3. Copy content of `src` directory into your project

## Usage

Concept of usage is based on Bolt messages. Available protocol methods depends on Bolt version.

https://www.neo4j.com/docs/bolt/current/bolt/message/

```php
// Create connection class and specify target host and port
$conn = new \Bolt\connection\Socket();
// Create new Bolt instance and provide connection object
$bolt = new \Bolt\Bolt($conn);
// Build and get protocol version instance which creates connection and executes handshake
$protocol = $bolt->build();
// Login to database with credentials
$protocol->hello(\Bolt\helpers\Auth::basic('neo4j', 'neo4j'));
// Execute query with parameters
$stats = $protocol->run('RETURN $a AS num, $b AS str', ['a' => 123, 'b' => 'text']);
// Pull records from last executed query
$rows = $protocol->pull();
```

Response from database (`$rows`) always contains n+1 rows because last entry are meta informations.

[More info](https://github.com/neo4j-php/Bolt/wiki/Usage)

### Transactions

Bolt from version 3 supports transactions and protocol contains these methods:

- begin
- commit
- rollback

_`run` executes query in auto-commit transaction if explicit transaction was not open._

### Cypher query parameters

| Neo4j | PHP |
| --- | --- |
| Null | null |
| Boolean | boolean |
| Integer | integer |
| Float | float |
| Bytes | [Bytes class](https://github.com/neo4j-php/Bolt/blob/master/src/structures/Bytes.php) |
| String | string |
| List | array with consecutive numeric keys from 0 |
| Dictionary | object or array which is not considered as list |
| Structure | [directory with structures](https://github.com/neo4j-php/Bolt/tree/master/src/structures) |

List or dictionary can be also provided as instance of class implementing `Bolt\PackStream\IPackListGenerator` or `Bolt\PackStream\IPackDictionaryGenerator`. This approach helps with memory management while working with big amount of data. To learn more you can check [performance test](https://github.com/neo4j-php/Bolt/blob/master/tests/PerformanceTest.php) or [packer test](https://github.com/neo4j-php/Bolt/blob/master/tests/PackStream/v1/PackerTest.php).

Structures Node, Relationship, UnboundRelationship and Path cannot be used as parameter. They are available only as received data from database.

### Neo4j Aura

Connecting to Aura requires encryption which is provided with SSL. To connect to Aura you have to use StreamSocket connection class and enable SSL.

```php
// url without neo4j+s protocol
$conn = new \Bolt\connection\StreamSocket('helloworld.databases.neo4j.io');
// enable SSL
$conn->setSslContextOptions([
    'verify_peer' => true
]);
$bolt = new \Bolt\Bolt($conn);
```

https://www.php.net/manual/en/context.ssl.php

## Another solutions

https://neo4j.com/developer/php/


## Support

<a href='https://ko-fi.com/Z8Z5ABMLW' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://cdn.ko-fi.com/cdn/kofi1.png?v=3' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>
