#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use DealNews\Console\Console;
use DealNews\TypesenseSchema\Updater;

$cli = new Console(
    [
        'help' => [
            'header' => 'Pushes schema changes into Typesense',
        ],
    ],
    [
        'test' => [
            'optional'    => Console::OPTIONAL,
            'description' => 'Test mode. No changes are made.',
        ],
        'collection' => [
            'optional'    => Console::REQUIRED,
            'param'       => 'COLLECTION',
            'description' => 'Collection name to update.',
        ],
        'env' => [
            'optional'    => Console::REQUIRED,
            'param'       => 'ENVIRONMENT',
            'description' => 'Environment to update (production, development, etc.)',
        ],
        'update-alias' => [
            'optional'    => Console::OPTIONAL,
            'param'       => 'ALIAS',
            'description' => 'Updates the alias to point to the provided collection.',
        ],
        'ini' => [
            'optional'    => Console::OPTIONAL,
            'param'       => 'INI_FILE',
            'description' => 'Optional ini file containing environment credentials. Defaults to ts.ini in working directory.',
        ],
    ]
);

$cli->run();

$ini_file = $cli->ini;

if(empty($ini_file)) {
    $ini_file = getcwd() . '/ts.ini';
}

if(!file_exists($ini_file)) {
    fwrite(STDERR, "File $ini_file not found.\n");
    exit(1);
}

try {
    $updater = new Updater($cli->collection, $cli->env, (bool)$cli->test, $ini_file);

    $alias = $cli->getOpt('update-alias');

    if ($alias) {
        $updater->updateAlias($alias, $cli->collection);
    } else {
        $updater->update();
    }

} catch (\Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
