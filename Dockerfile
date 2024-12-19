FROM brianlmoon/php:8.1

RUN mkdir /typesense-schema && \
    mkdir /app && \
    chown php:php /app /typesense-schema

COPY --chown=php:php bin /app/bin
COPY --chown=php:php src /app/src
COPY --chown=php:php composer.json /app
COPY --chown=php:php composer.lock /app

USER php

RUN chdir /app && \
    composer install && \
    chdir /typesense-schema

WORKDIR /typesense-schema

CMD ["/app/bin/tsimport.php"]
