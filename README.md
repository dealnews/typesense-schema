# Typesense Collection Management

This application will help manage collections in Typesense.

## Configuration

To configure your environments, copy the `ts.ini-example` file to `ts.ini`. Update it with the host, port, and api key for each of your environments. These will be the environment names used when running the `update.sh` script.

## Running with Docker

```sh
docker run --rm -it \
    -v path/to/schema:/typesense-schema/collections \
    -v path/to/ts.ini:/typesense-schema/ts.ini
    dealnews/typesense-schema \
        --environment envname \
        --collection mycollection
```

### Updating an alias

```sh
docker run --rm -it \
    -v path/to/schema:/typesense-schema/collections \
    -v path/to/ts.ini:/typesense-schema/ts.ini
    dealnews/typesense-schema \
        --environment envname \
        --collection mycollection \
        --update-alias myalias
```
