<?php
require_once 'Bolt.php';

$neo4j = new Bolt();
try {
    $res = $neo4j->init('MyClient/1.0', 'neo4j', 'heslo');
    $res = $neo4j->run('MATCH (n) RETURN n');
    var_dump($res);
    $res = $neo4j->pullAll();
    var_dump($res);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
