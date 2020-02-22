FROM crunchgeek/php-fpm:7.3-r5
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo yes | pecl install yar && docker-php-ext-enable yar

RUN apt update -y
RUN apt install supervisor -y

RUN apt autoremove -y && apt clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*