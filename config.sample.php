<?php

define("POW_DIFICULTY", 4);
define("SEED_PEER_IP", "127.0.0.1");
define("SEED_PEER_PORT", "8080");
define("PORT_API", 8080);
define("PORT_WEB", 8081);
define("BIND_ADDRESS", "127.0.0.1");
define("MASTER_NODE", true);

if (!file_exists($file = __DIR__ . '/vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run this script.');
}

$loader = require_once $file;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Predis\Client;


$builder = new DI\ContainerBuilder();
$builder->useAnnotations(true);
$builder->addDefinitions([
    Doctrine\ODM\MongoDB\DocumentManager::class => DI\factory(function () use ($loader) {

        $loader->add('Documents', __DIR__ . '/src');

        AnnotationRegistry::registerLoader([$loader, 'loadClass']);

        $connection = new Connection();

        $config = new Configuration();
        $config->setProxyDir(__DIR__ . '/src/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/src/Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setDefaultDB('doctrine_odm');
        $config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/src'));

        return DocumentManager::create($connection, $config);
    }),
    Predis\Client::class => DI\factory(function () {
        return new Client();
    })
]);
$container = $builder->build();