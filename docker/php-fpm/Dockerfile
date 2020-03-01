FROM crunchgeek/php-fpm:7.3-r5
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo yes | pecl install yar && docker-php-ext-enable yar

RUN apt update -y

COPY php_ext/xhprof.so /usr/local/lib/php/extensions/no-debug-non-zts-20180731/
RUN docker-php-ext-enable xhprof && apt install graphviz -y

RUN apt install supervisor -y

RUN apt autoremove -y && apt clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*