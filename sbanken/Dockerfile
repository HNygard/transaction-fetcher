FROM php:7-cli

RUN curl --silent --show-error https://getcomposer.org/installer | php

# Need git for composer installs
RUN apt-get update && apt-get install -y \
       git

CMD cd /src && \
    php ../composer.phar update && \
    php ../composer.phar install && \
    php /src/fetcher.php
