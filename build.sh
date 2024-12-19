#!/bin/bash

git fetch -t
TAG=`git for-each-ref --sort=creatordate --format '%(refname:lstrip=2)' refs/tags | tail -n 1`

docker buildx build \
    --platform linux/arm/v6,linux/amd64 \
    -t dealnews/typesense-schema:$TAG \
    -t dealnews/typesense-schema:latest \
    --push \
    .
