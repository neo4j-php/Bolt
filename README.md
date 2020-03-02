# Bolt
Bolt protocol library over TCP socket. Bolt protocol is primary used for communication with [Neo4j](https://neo4j.com/) Graph database. The documentation is available at https://boltprotocol.org/v1/

## Supported version
Bolt < 4.0

Neo4j version 4.0 is out for some time and I'm sorry to tell you, but this software won't work with it. Reason is outdated documentation for Bolt protocol handled by Neo4j team, which is still available only for version 1.0. Suddenly Neo4j 4.0 drops support for Bolt V1.

## Requirements
PHP >= 7.1  
extensions:
- sockets https://www.php.net/manual/en/book.sockets.php
- mbstring https://www.php.net/manual/en/book.mbstring.php

## Usage
See ``index.php`` file. It contains few examples how you can use this library. All files are loaded with require_once at the beginning of file, because this example doesn't contain autoloader. Of course you need to set up your username and password.

## Exceptions
Throwing exceptions is default behaviour. If you want, you can assign own callable error handler to ``\Bolt\Bolt::$errorHandler``. It's called on error and methods (init, run, pullAll, ...) will return false.

## Author note
I really like Neo4j and I wanted to use it with PHP. But after I looked on official php library, I was really disappointed. Too much dependencies. I don't like if I need to install 10 things because of one. First I decided to use HTTP API for communication, but it wasn't fast enough. I went through bolt protocol documentation and I said to myself, why not to create own simpler library?

## Support
If you like this project and you want to support me, buy me a tea :)

[![Donate paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.me/MichalStefanak)
