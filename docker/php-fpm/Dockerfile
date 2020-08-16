FROM crunchgeek/php-fpm:7.3-r5
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo yes | pecl install yar && docker-php-ext-enable yar

COPY php_ext/xhprof.so /usr/local/lib/php/extensions/no-debug-non-zts-20180731/

RUN apt update -y \
    && docker-php-ext-enable xhprof && apt install graphviz -y \
    && apt install supervisor -y \
    && apt autoremove -y && apt clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*