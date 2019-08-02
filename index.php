<?php

require_once 'Bolt.php';
require_once 'Packer.php';
require_once 'Unpacker.php';
require_once 'structures' . DIRECTORY_SEPARATOR . 'Node.php';
require_once 'structures' . DIRECTORY_SEPARATOR . 'Relationship.php';
require_once 'structures' . DIRECTORY_SEPARATOR . 'Path.php';
require_once 'structures' . DIRECTORY_SEPARATOR . 'UnboundRelationship.php';

set_time_limit(3);

$user = 'neo4j';
$password = 'nothing';

try {
    $neo4j = new \Bolt\Bolt();
    if (!$neo4j->init('MyClient/1.0', $user, $password)) {
        throw new Exception('Wrong login');
    }

    //test fields
    $res = $neo4j->run('RETURN 1 AS num, 2 AS cnt');
    if (($res['fields'][0] ?? '') != 'num' || ($res['fields'][1] ?? '') != 'cnt') {
        throw new Exception('Wrong fields');
    }

    //test record
    $res = $neo4j->pullAll();
    if (($res[0][0] ?? 0) != 1 || ($res[0][1] ?? 0) != 2) {
        throw new Exception('Wrong record');
    }


    //test node create
    $neo4j->run('CREATE (a:Test) RETURN a, ID(a)');
    $created = $neo4j->pullAll();
    if (!($created[0][0] instanceof \Bolt\structures\Node)) {
        throw new Exception('Unsuccussful node create');
    }

    //test delete created node
    $neo4j->run('MATCH (a:Test) WHERE ID(a) = {a} DELETE a', [
        'a' => $created[0][1]
    ]);
    $res = $neo4j->pullAll();
    if (($res[0]['stats']['nodes-deleted'] ?? 0) != 1) {
        throw new Exception('Unsuccussful node delete');
    }


    echo 'Test successful';
} catch (Exception $e) {
    var_dump($e->getMessage());
}
