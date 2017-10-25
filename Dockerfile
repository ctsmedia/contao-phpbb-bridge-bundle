# Name ctsmedia/contao-phpbb-bridge
FROM ctsmedia/contao:4.4
MAINTAINER Daniel Schwiperich | ctsmedia <entwicklung@cts-media.eu>

ARG BRIDGE_COMPOSER_VERSION=dev-master
ARG PHPBB_VERSION=3.1.9
ARG CONTAO_VERSION=~4.4

# Add bridge Dependency
RUN composer require ctsmedia/contao-phpbb-bridge-bundle ${BRIDGE_COMPOSER_VERSION}

# Cleanup tmp dir because contaos post command do some unusal stuff which breaks the system if it was run as root
# https://github.com/contao/core-bundle/blob/master/src/Command/AbstractLockedCommand.php#L32
RUN rm -r /tmp/*
# Also clean up cache
RUN rm -r var/cache/*

# Preparing phpbb
RUN curl -L https://www.phpbb.com/files/release/phpBB-${PHPBB_VERSION}.zip -o phpbb.zip \
    && unzip phpbb.zip \
    && mv phpBB3 /var/www/share/project/web/phpbb \
    && chmod -R 777 /var/www/share/project/web/phpbb

RUN chmod -R 0777 .
RUN chown -R www-data:www-data .

# phpbb has no pdo support, so we install mysqli extension
RUN docker-php-ext-install mysqli




