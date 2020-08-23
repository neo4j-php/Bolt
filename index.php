<?php

/**
 * Bolt protocol library
 * This index.php file serve as usage preview and functional test. It can be removed.
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/Bolt
 */

require_once 'autoload.php';

set_time_limit(3);

$user = 'neo4j';
$password = 'nothing';

//\Bolt\Bolt::$debug = true;

try {

    $neo4j = new \Bolt\Bolt();
    $neo4j->setProtocolVersions(4.1);

    if (!$neo4j->init('MyClient/1.0', $user, $password)) {
        throw new Exception('Wrong login');
    }

    //test fields
    $res = $neo4j->run('RETURN 1 AS num, 2 AS cnt');
    if (($res['fields'][0] ?? '') != 'num' || ($res['fields'][1] ?? '') != 'cnt') {
        throw new Exception('Wrong fields');
    }

    //test record
    $res = $neo4j->pull();
    if (($res[0][0] ?? 0) != 1 || ($res[0][1] ?? 0) != 2) {
        throw new Exception('Wrong record');
    }


    //test node create
    $neo4j->run('CREATE (a:Test) RETURN a, ID(a)');
    $created = $neo4j->pull();
    if (!($created[0][0] instanceof \Bolt\structures\Node)) {
        throw new Exception('Unsuccussful node create');
    }

    //get neo4j version to use right placeholders
    $neo4j->run('call dbms.components() yield versions unwind versions as version return version');
    $neo4jVersion = $neo4j->pull()[0][0] ?? '';
    $t = version_compare($neo4jVersion, '4') == -1;

    //test delete created node
    $neo4j->run('MATCH (a:Test) WHERE ID(a) = ' . ($t ? '{a}' : '$a') . ' DELETE a', [
        'a' => $created[0][1]
    ]);
    $res = $neo4j->pull();
    if (($res[0]['stats']['nodes-deleted'] ?? 0) != 1) {
        throw new Exception('Unsuccussful node delete');
    }

    //transaction
    if ($neo4j->getProtocolVersion() >= 3) {
        $neo4j->begin();
        $neo4j->run('CREATE (a:Test) RETURN a, ID(a)');
        $created = $neo4j->pull();
        $neo4j->rollback();

        $neo4j->run('MATCH (a:Test) WHERE ID(a) = ' . ($t ? '{a}' : '$a') . ' RETURN COUNT(a)', [
            'a' => $created[0][1]
        ]);
        $res = $neo4j->pull();
        if ($res[0][0] != 0)
            throw new Exception('Unsuccussful transaction rollback');
    }

    unset($neo4j);

    echo '<br><br>Test successful';

} catch (Exception $e) {
    var_dump($e->getMessage());
}
