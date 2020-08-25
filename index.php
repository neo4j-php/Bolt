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

    $bolt = new \Bolt\Bolt();
    $bolt->setProtocolVersions(4.1);

    if (!$bolt->init('MyClient/1.0', $user, $password)) {
        throw new Exception('Wrong login');
    }

    //test fields
    $res = $bolt->run('RETURN 1 AS num, 2 AS cnt');
    if (($res['fields'][0] ?? '') != 'num' || ($res['fields'][1] ?? '') != 'cnt') {
        throw new Exception('Wrong fields');
    }

    //test record
    $res = $bolt->pull();
    if (($res[0][0] ?? 0) != 1 || ($res[0][1] ?? 0) != 2) {
        throw new Exception('Wrong record');
    }


    //test node create
    $bolt->run('CREATE (a:Test) RETURN a, ID(a)');
    $created = $bolt->pull();
    if (!($created[0][0] instanceof \Bolt\structures\Node)) {
        throw new Exception('Unsuccussful node create');
    }

    //get neo4j version to use right placeholders
    $bolt->run('call dbms.components() yield versions unwind versions as version return version');
    $neo4jVersion = $bolt->pull()[0][0] ?? '';
    $t = version_compare($neo4jVersion, '4') == -1;

    //test delete created node
    $bolt->run('MATCH (a:Test) WHERE ID(a) = ' . ($t ? '{a}' : '$a') . ' DELETE a', [
        'a' => $created[0][1]
    ]);
    $res = $bolt->pull();
    if (($res[0]['stats']['nodes-deleted'] ?? 0) != 1) {
        throw new Exception('Unsuccussful node delete');
    }

    //transaction
    if ($bolt->getProtocolVersion() >= 3) {
        $bolt->begin();
        $bolt->run('CREATE (a:Test) RETURN a, ID(a)');
        $created = $bolt->pull();
        $bolt->rollback();

        $bolt->run('MATCH (a:Test) WHERE ID(a) = ' . ($t ? '{a}' : '$a') . ' RETURN COUNT(a)', [
            'a' => $created[0][1]
        ]);
        $res = $bolt->pull();
        if ($res[0][0] != 0)
            throw new Exception('Unsuccussful transaction rollback');
    }

    unset($bolt);

    echo '<br><br>Test successful';

} catch (Exception $e) {
    var_dump($e->getMessage());
}
