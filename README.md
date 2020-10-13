# Bolt
Bolt protocol library over TCP socket. Bolt protocol is primary used for communication with [Neo4j](https://neo4j.com/) Graph database. The documentation is available at [https://7687.org/](https://7687.org/).

## Supported version
Bolt <= 4.1

| Neo4j Version | Bolt 1 | Bolt 2 | Bolt 3 | Bolt 4.0 | Bolt 4.1 |
|:-------------:|:------:|:------:|:------:|:--------:|:--------:|
| 3.0           | x      |        |        |          |          |
| 3.1           | x      |        |        |          |          |
| 3.2           | x      |        |        |          |          |
| 3.3           | x      |        |        |          |          |
| 3.4           | (x)    | x      |        |          |          |
| 3.5           |        | (x)    | x      |          |          |
| 4.0           |        |        | (x)    | x        |          |
| 4.1           |        |        | (x)    | (x)      | x        |

<sup>The (x) denotes that support could be removed in next version of Neo4j.</sup>

## Requirements
PHP >= 7.1  
extensions:
- sockets https://www.php.net/manual/en/book.sockets.php
- mbstring https://www.php.net/manual/en/book.mbstring.php

## Installation via composer
Run the following command to install the latest applicable version of the package:

``composer require stefanak-michal/bolt``

## Usage
See ``index.php`` file. It contains few examples how you can use this library. Of course you need to set up your username and password. This repository contains simple `autoload.php` file.

### Main code example
```php
<?php
//Create new Bolt instance
$bolt = new \Bolt\Bolt();
//Set Bolt protocol version (default is newest 4.1)
$bolt->setProtocolVersions(4.1);
//Connect to database
$bolt->init('MyClient/1.0', 'username', 'password);
//Execute query
$bolt->run('RETURN 1 AS num, 2 AS cnt');
//Pull records from last query
$rows = $bolt->pull();
```

| Method    | Description                                                        |
|--------------------------|---------------------------------------------------------------------------------------|
| setProtocolVersions    | set requested protocol versions                                                        |
| getProtocolVersion     | get used protocol version (you have to establish connection with init() method first) |
| init                   | connect to database                                                                   |
| run                    | execute query                                                                         |
| pull / pullAll       | fetch records from last query                                                         |
| discard / discardAll | discard records from last query                                                       |
| begin                  | start transaction                                                                     |
| commit                 | commit transaction                                                                    |
| rollback               | rollback transaction                                                                  |
| reset                  | reset connection                                                                      |

## Exceptions
Throwing exceptions is default behaviour. If you want, you can assign own callable error handler to ``\Bolt\Bolt::$errorHandler``. It's called on error and methods (init, run, pullAll, ...) will therefore return false.

## Author note
I really like Neo4j and I wanted to use it with PHP. But after I looked on official php library, I was really disappointed. Too much dependencies. I don't like if I need to install 10 things because of one. First I decided to use HTTP API for communication, but it wasn't fast enough. I went through bolt protocol documentation and I said to myself, why not to create own simpler library?

## Another solutions
https://neo4j.com/developer/php/

## Support
If you like this project and you want to support me, buy me a tea :)

[![Donate paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.me/MichalStefanak)
