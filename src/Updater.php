<?php

namespace DealNews\TypesenseSchema;

use DealNews\Console\Interact;
use DealNews\GetConfig\GetConfig;
use Sarhan\Flatten\Flatten;
use Symfony\Component\HttpClient\HttplugClient;
use Typesense\Client;

class Updater {

    protected Client $client;
    protected string $collection_schema;

    public function __construct(
        protected string $collection,
        protected string $environment,
        protected bool $test,
        string $ini_file
    ) {

        $config = new GetConfig($ini_file);

        $api_key = $config->get("typesense.{$this->environment}.api_key");
        $host    = $config->get("typesense.{$this->environment}.host");
        $port    = $config->get("typesense.{$this->environment}.port");

        if (empty($api_key) || empty($host)) {
            throw new \InvalidArgumentException("Collection {$this->collection} not found in $ini_file.");
        }

        $this->client = new Client(
            [
                'api_key' => $api_key,
                'nodes' => [
                    [
                        'host' => $host,
                        'port' => $port,
                        'protocol' => 'https',
                    ],
                ],
                'client' => new HttplugClient(),
            ]
        );
    }

    public function update() {

        $this->collection_schema = getcwd() . "/collections/{$this->collection}.json";
        if (!file_exists($this->collection_schema)) {
            throw new \InvalidArgumentException("Schema for collection {$this->collection} not found.");
        }

        $schema = json_decode(file_get_contents($this->collection_schema), true);

        $aliases = $this->client->aliases->retrieve();

        $real_collection = $this->collection;

        foreach ($aliases['aliases'] as $alias) {
            if ($alias['name'] == $this->collection) {
                $real_collection = $alias['collection_name'];
                break;
            }
        }

        if (!$this->client->collections[$real_collection]->exists()) {
            $this->createCollection($schema, $this->collection);
        } else {
            $current_schema = $this->client->collections[$real_collection]->retrieve();
            $patch = $this->buildPatch($schema, $current_schema);

            if (empty($patch)) {
                echo "Schema is up to date\n";
                return;
            }

            if (Interact::confirm("Would you like to continue?")) {
                try {
                    $result = $this->client->collections[$real_collection]->update(['fields' => $patch]);
                } catch (\Throwable $e) {
                    echo "Failed to update existing collection {$real_collection}.\n";
                    echo "Message: " . $e->getMessage() . "\n";
                    if (Interact::confirm("Would you like to create a new collection?")) {
                        $this->createCollection($schema, $this->collection);
                    }
                }
            }
        }
    }

    public function updateAlias(string $collection_name, string $new_collection_name) {
        $alias = $this->client->aliases->upsert($collection_name, ['collection_name' => $new_collection_name]);
        if (!empty($alias['name'])) {
            echo "Created/updated alias {$collection_name} to point to collection {$new_collection_name}\n";
        }
    }

    protected function createCollection(array $schema, string $collection_name) {
        $new_collection_name = $collection_name . "-" . gmdate('YmdHis');
        $schema['name'] = $new_collection_name;
        $result = $this->client->collections->create($schema);
        if (!empty($result['name'])) {
            echo "Created new collection {$new_collection_name}\n";
            if (Interact::confirm("Would you like to point the alias `{$collection_name}` at the new collection?")) {
                $this->updateAlias($collection_name, $new_collection_name);
            }
        }
    }

    protected function buildPatch(array $schema, array $current_schema): array {
        $local_fields  = $this->sortArray($schema['fields']);
        $server_fields = $this->sortArray($current_schema['fields']);

        $new_fields      = [];
        $modified_fields = [];

        foreach ($local_fields as $local_field) {
            $found = false;
            foreach ($server_fields as $server_field) {
                if ($local_field['name'] == $server_field['name']) {

                    // server fields may have more properties than
                    // the local config so add any missing ones before comparing
                    foreach ($server_field as $prop => $value) {
                        if (!array_key_exists($prop, $local_field)) {
                            $local_field[$prop] = $value;
                        }
                    }

                    if ($local_field != $server_field) {
                        echo "Updating field: {$local_field['name']}\n";
                        $modified_fields[$local_field['name']] = $local_field;
                    }
                    $found = true;
                }
            }
            // the server does not return the id field
            if (!$found && $local_field['name'] != 'id') {
                echo "New field:      {$local_field['name']}\n";
                $new_fields[$local_field['name']] = $local_field;
            }
        }

        $patch = array_values($new_fields);

        foreach ($server_fields as $server_field) {
            $found = false;
            foreach ($local_fields as $local_field) {
                if ($local_field['name'] == $server_field['name']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo "Dropping field: {$server_field['name']}\n";
                $patch[] = ['name' => $server_field['name'], 'drop' => true];
            }
        }

        foreach ($modified_fields as $name => $field) {
            $patch[] = ['name' => $name, 'drop' => true];
            $patch[] = $field;
        }

        return $patch;
    }

    protected function sortArray(array $arr): array {
        $flatten = new Flatten();
        $arr = $flatten->flattenToArray($arr);
        ksort($arr);
        $arr = $flatten->unflattenToArray($arr);
        return $arr;
    }
}
