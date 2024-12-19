# Typesense Collection Management

This application will help manage collections in Typesense.

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
