FROM brianlmoon/php:8.1

RUN mkdir /typesense-schema && \
    mkdir /app && \
    chown php:php /app /typesense-schema

COPY bin src composer.json composer.lock /app

USER php

RUN chdir /app && \
    composer install && \
    chdir /typesense-schema

WORKDIR /typesense-schema

CMD /app/bin/tsimport.php
