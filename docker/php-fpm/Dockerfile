FROM crunchgeek/php-fpm:7.3-r5
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo yes | pecl install yar && docker-php-ext-enable yar
