![Logo](https://repository-images.githubusercontent.com/198229221/c0edf6c1-8699-481d-85f7-17b6ea0ffb26)

# Bolt
Bolt protocol library over TCP socket. Bolt protocol is created and in use for communication with [Neo4j](https://neo4j.com/) Graph database. The Bolt documentation is available at [https://7687.org/](https://7687.org/). This library is aimed to be low level and keep up with protocol messages architecture and specifications.

![DB Tests](https://github.com/neo4j-php/Bolt/actions/workflows/db-tests.yml/badge.svg?branch=master) 
![No DB Tests PHP7](https://github.com/neo4j-php/Bolt/actions/workflows/no-db-test-php-7.yml/badge.svg?branch=master) 
![No DB Tests PHP8](https://github.com/neo4j-php/Bolt/actions/workflows/no-db-test-php-8.yml/badge.svg?branch=master) 

![](https://img.shields.io/github/stars/stefanak-michal/Bolt) 
![](https://img.shields.io/packagist/dt/stefanak-michal/bolt) 
![](https://img.shields.io/github/v/release/stefanak-michal/bolt) 
![](https://img.shields.io/github/commits-since/stefanak-michal/bolt/latest)

## Version support

We are trying to keep up and this library supports **Neo4j <= 4.4**.

[More info](https://github.com/neo4j-php/Bolt/wiki/Version-support)

## Requirements

It's hard to live without all new features which means we keep at **PHP >= 7.1**.

[More info](https://github.com/neo4j-php/Bolt/wiki/Requirements)

## Installation

You can use composer or download this repository.

`composer require stefanak-michal/bolt`

[More info](https://github.com/neo4j-php/Bolt/wiki/Installation)

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
