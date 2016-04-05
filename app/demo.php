<?php

require_once __DIR__.'/vendor/autoload.php';

use GraphAware\Neo4j\Client\ClientBuilder;
use GuzzleHttp\Client;
$host = getenv('DB_HOST');
$esHost = getenv('ES_HOST');
$client = ClientBuilder::create()
	->addConnection('default', $host)
	->build();

$esClient = new Client();

while (true) {
	try {
		$result = $client->run('CREATE (a:Test)-[:RELATES]->(b:EndTest) SET a.objectId = id(a) SET b.objectId = id(b)');
	} catch (\Exception $e) {
		echo $e->getMessage() . PHP_EOL;
		sleep(5);
	}
}
