<?php

require_once __DIR__ . "/config.php";

$node = $container->get(Jarenal\Node::class);
$node->start();
