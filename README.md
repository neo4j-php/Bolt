![Logo](https://repository-images.githubusercontent.com/198229221/fcf334aa-ef6b-4fe9-89ad-d03e4c7d89e3)

# Bolt
PHP library for communication with graph database over TCP socket with Bolt protocol specification. Bolt protocol was created by [Neo4j](https://neo4j.com/) and documentation is available at [https://www.neo4j.com/](https://www.neo4j.com/docs/bolt/current/). This library is aimed to be low level, support all available versions and keep up with protocol messages architecture and specifications.

![DB Tests PHP8](https://github.com/neo4j-php/Bolt/actions/workflows/db-test-php-8.yml/badge.svg?branch=master)
![No DB Tests PHP8](https://github.com/neo4j-php/Bolt/actions/workflows/no-db-test-php-8.yml/badge.svg?branch=master)

[![](https://img.shields.io/github/stars/stefanak-michal/Bolt)](https://github.com/neo4j-php/Bolt/stargazers)
[![](https://img.shields.io/packagist/dt/stefanak-michal/bolt)](https://packagist.org/packages/stefanak-michal/bolt/stats)
[![](https://img.shields.io/github/v/release/stefanak-michal/bolt)](https://github.com/neo4j-php/Bolt/releases)
[![](https://img.shields.io/github/commits-since/stefanak-michal/bolt/latest)](https://github.com/neo4j-php/Bolt/releases/latest)

<a href='https://ko-fi.com/Z8Z5ABMLW' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://cdn.ko-fi.com/cdn/kofi1.png?v=3' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

## Version support

We are trying to keep up and this library supports **Neo4j <= 5.2** with **Bolt <= 5.2**.

https://www.neo4j.com/docs/bolt/current/bolt-compatibility/

## Requirements

Keep up with [PHP supported versions](https://www.php.net/supported-versions.php) means we are at **PHP >= 7.4**.

_If you need support for PHP < 7.4 you can use latest v3.x release. Not all new features are implement backwards and this readme is updated to latest released version._

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

Concept of usage is based on Bolt messages. Available protocol methods depends on Bolt version. Communication works in [pipeline](https://www.neo4j.com/docs/bolt/current/bolt/message/#pipelining) and you can chain multiple Bolt messages before fetching response from the server.

Main `Bolt` class serves as Factory design pattern and it returns instance of protocol class by requested Bolt version (default is 4 latest versions). Query execution and fetching response is split in two methods. First message `run` is for sending queries. Second message `pull` is for fetching response from last executed query on database. 
Response from database for Bolt message `pull` always contains n+1 rows because last entry is `success` message with meta informations.

More info about available Bolt messages: https://www.neo4j.com/docs/bolt/current/bolt/message/

### Example

```php
// Create connection class and specify target host and port.
$conn = new \Bolt\connection\Socket('127.0.0.1', 7687);
// Create new Bolt instance and provide connection object.
$bolt = new \Bolt\Bolt($conn);
// Build and get protocol version instance which creates connection and executes handshake.
$protocol = $bolt->build();
// Login to database with credentials.
$protocol->hello(\Bolt\helpers\Auth::basic('neo4j', 'neo4j'));

// Pipeline two messages. One to execute query with parameters and second to pull records.
$protocol
    ->run('RETURN $a AS num, $b AS str', ['a' => 123, 'b' => 'text'])
    ->pull();
    
// Fetch waiting server responses for pipelined messages.
foreach ($protocol->getResponses() as $response) {
    // $response is instance of \Bolt\protocol\Response.
    // First response is SUCCESS message for RUN message.
    // Second response is RECORD message for PULL message.
    // Third response is SUCCESS message for PULL message.
}
```

### Available methods

**Bolt class**

| Method / Property    | Description                                                                                        | Type          | Parameters              | Return    |
|----------------------|----------------------------------------------------------------------------------------------------|---------------|-------------------------|-----------|
| __construct          | Bolt constructor                                                                                   | public        | IConnection $connection | Bolt      |
| setProtocolVersions  | Set allowed protocol versions for connection                                                       | public        | int/float/string ...$v  | Bolt      |
| setPackStreamVersion | Set PackStream version                                                                             | public        | int $version = 1        | Bolt      |
| build                | Create protocol instance. Method creates connection, executes handshake and do a version request.  | public        |                         | AProtocol |
| $debug               | Print binary communication (as hex)                                                                | public static | bool                    |           |

**Protocol class**

| Method / Property | Description                                                                 | Parameters                                                         |
|-------------------|-----------------------------------------------------------------------------|--------------------------------------------------------------------|
| hello             | Connect to database (you can use helper to provide required extra argument) | array $extra                                                       |
| run               | Execute query. Response from database are meta informations.                | string $statement<br/>array $parameters = []<br/>array $extra = [] |
| pull              | Pull result from executed query                                             | array $extra = []                                                  |
| discard           | Discard result waiting for pull                                             | array $extra = []                                                  |
| begin             | Start transaction                                                           | array $extra = []                                                  |
| commit            | Commit transaction                                                          |                                                                    |
| rollback          | Rollback transaction                                                        |                                                                    |
| reset             | Send message to reset connection                                            |                                                                    |
| getVersion        | Get used protocol version                                                   |                                                                    |
| getResponse       | Get waiting response from server                                            |                                                                    |
| getResponses      | Get waiting responses from server                                           |                                                                    |
|                   |                                                                             |                                                                    |
| init              | @see hello                                                                  |                                                                    |
| pullAll           | @see pull                                                                   |                                                                    |
| discardAll        | @see discard                                                                |                                                                    |

Many methods accept argument called `$extra`. This argument can contain any of key-value by Bolt specification. This argument was extended during Neo4j development which means the content of it changed. You should keep in mind what version you are working with when using this argument. You can read more about extra parameter in Bolt documentation where you can look into your version and bolt message.

Annotation of methods in protocol classes contains direct link to specific version and message from mentioned documentation website.

### Transactions

Bolt from version 3 supports transactions and protocol contains these methods:

- begin
- commit
- rollback

_`run` executes query in auto-commit transaction if explicit transaction was not open._

### Cypher query parameters

| Neo4j      | PHP                                                                                                                               |
|------------|-----------------------------------------------------------------------------------------------------------------------------------|
| Null       | null                                                                                                                              |
| Boolean    | boolean                                                                                                                           |
| Integer    | integer                                                                                                                           |
| Float      | float                                                                                                                             |
| Bytes      | [Bytes class](https://github.com/neo4j-php/Bolt/blob/master/src/structures/Bytes.php)                                             |
| String     | string                                                                                                                            |
| List       | array with consecutive numeric keys from 0                                                                                        |
| Dictionary | object or array which is not considered as list                                                                                   |
| Structure  | Classes implementing `IStructure` by protocol version ([docs](https://www.neo4j.com/docs/bolt/current/bolt/structure-semantics/)) |

List or dictionary can be also provided as instance of class implementing `Bolt\packstream\IPackListGenerator` or `Bolt\PackStream\IPackDictionaryGenerator`. This approach helps with memory management while working with big amount of data. To learn more you can check [performance test](https://github.com/neo4j-php/Bolt/blob/master/tests/PerformanceTest.php) or [packer test](https://github.com/neo4j-php/Bolt/blob/master/tests/PackStream/v1/PackerTest.php).

Structures `Node`, `Relationship`, `UnboundRelationship` and `Path` cannot be used as parameter. They are available only as received data from database.

Server state is not available from server but we assume it. Library contains `\Bolt\helpers\ServerState` and you can get used instance of this class with `$bolt->serverState` or `$protocol->serverState` (after you call `build()`).

### Autoload

Directory `src` contains autoload file which accepts only Bolt library namespaces. Main Bolt namespace points to this directory. If you have installed this project with composer, you have to load `vendor/autoload.php`.

## Server state

If assumed server state is different than expected, library does not throw exception. This logic is silent but you can change it and if you would like to implement own logic when assumed server state is different than expected you can assign callable into class property `$serverState->expectedServerStateMismatchCallback`.

## Connection

Bolt class constructor accepts connection argument. This argument has to be instance of class which implements IConnection interface. Currently exists two predefined classes `Socket` and `StreamSocket`.

_We provide two connection classes. `Socket` was created first and it has better memory usage. `StreamSocket` was made because of need to accept TLS._

**\Bolt\connection\Socket**

This class use php extension sockets. More informations here: [https://www.php.net/manual/en/book.sockets.php](https://www.php.net/manual/en/book.sockets.php)

**\Bolt\connection\StreamSocket**

This class uses php stream functions. Which is a part of php and there is no extensions needed. More informations here: [https://www.php.net/manual/en/ref.stream.php](https://www.php.net/manual/en/ref.stream.php)

StreamSocket besides of implemented methods from interface has method to configure SSL. When you want to activate SSL you have to call method `setSslContextOptions`. This method accept array by php specification available here: [https://www.php.net/manual/en/context.ssl.php](https://www.php.net/manual/en/context.ssl.php).

_If you want to use it, you have to enable openssl php extension._

### Neo4j Aura

Connecting to Aura requires encryption which is provided with SSL. To connect to Aura you have to use `StreamSocket` connection class and enable SSL.

```php
$conn = new \Bolt\connection\StreamSocket('helloworld.databases.neo4j.io');
// enable SSL
$conn->setSslContextOptions([
    'verify_peer' => true
]);
$bolt = new \Bolt\Bolt($conn);
```

https://www.php.net/manual/en/context.ssl.php

### Example on localhost database with self-signed certificate:

```php
$conn = new \Bolt\connection\StreamSocket();
$conn->setSslContextOptions([
    'local_cert'=> 'd:\www\bolt\cert\public_cert.pem',
    'local_pk' => 'd:\www\bolt\cert\private_key.pem',
    'passphrase' => 'password',
    'allow_self_signed' => true,
    'verify_peer' => false,
    'verify_peer_name' => false
]);
$bolt = new \Bolt\Bolt($conn);
```

### Timeout

Connection class constructor contains `$timeout` argument. This timeout is for established socket connection. To set up timeout for establishing socket connection itself you have to set ini directive `default_socket_timeout`.

_Setting up ini directive isn't part of connection class because function `ini_set` can be disabled on production environments for security reasons._

## Another solutions

If you need simple class to cover basic functionality you can use: [neo4j-bolt-wrapper](https://packagist.org/packages/stefanak-michal/neo4j-bolt-wrapper)

When you are in need of enterprise level take a look on: [php-client](https://packagist.org/packages/laudis/neo4j-php-client)

More solutions can be found at: https://neo4j.com/developer/php/
