# Bolt
Bolt protocol library over TCP socket. Bolt protocol is primary used for communication with [Neo4j](https://neo4j.com/) Graph database. The documentation is available at https://boltprotocol.org/v1/

## Requirements
PHP >= 7.1  
extensions:
- sockets https://www.php.net/manual/en/book.sockets.php
- mbstring https://www.php.net/manual/en/book.mbstring.php

## Exceptions
Throwing exceptions is default behaviour. If you want, you can assign own callable error handler to ``\Bolt\Bolt::$errorHandler``. It's called on error and methods (init, run, pullAll, ...) will return false.

## Author note
I really like Neo4j and I wanted to use it with PHP. But after I looked on official php library, I was really disappointed. Too much dependencies. I don't like if I need to install 10 things because of one. First I decided to use HTTP API for communication, but it wasn't fast enough. I went through bolt protocol documentation and I said to myself, why not to create own simpler library?

## Support
If you like this project and you want to support me, buy me a tea :)

[![Donate paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.me/MichalStefanak)
