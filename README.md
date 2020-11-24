# Bolt
Bolt protocol library over TCP socket. Bolt protocol is primary used for communication with [Neo4j](https://neo4j.com/) Graph database. The documentation is available at [https://7687.org/](https://7687.org/).

![](https://img.shields.io/badge/phpunit-passed-success) ![](https://img.shields.io/badge/coverage-77%25-green) ![](https://img.shields.io/github/stars/stefanak-michal/Bolt) ![](https://img.shields.io/packagist/dt/stefanak-michal/bolt) ![](https://img.shields.io/github/v/release/stefanak-michal/bolt) ![](https://img.shields.io/github/commits-since/stefanak-michal/bolt/latest)

## Version support
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
| 4.2           |        |        | (x)    | (x)      | (x)      |

<sup>The (x) denotes that support could be removed in next version of Neo4j.</sup>

## [Requirements](https://github.com/stefanak-michal/Bolt/wiki/Requirements)
## [Installation](https://github.com/stefanak-michal/Bolt/wiki/Installation)
## [Usage](https://github.com/stefanak-michal/Bolt/wiki/Usage)
## [Errors](https://github.com/stefanak-michal/Bolt/wiki/Errors)

## Author note
I really like Neo4j and I wanted to use it with PHP. But after I looked on official php library, I was really disappointed. Too much dependencies. I don't like if I have to install 10 things because of one. First I decided to use HTTP API for communication, but it wasn't fast enough. I went through bolt protocol documentation and I said to myself, why not to create own simpler library?

[Speed comparison](https://github.com/stefanak-michal/Bolt/wiki/Speed-comparison)

## Another solutions
https://neo4j.com/developer/php/
