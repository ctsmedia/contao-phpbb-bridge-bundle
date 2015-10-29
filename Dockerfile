FROM ctsmedia/baseimage-web:latest
MAINTAINER Daniel Schwiperich | ctsmedia <entwicklung@cts-media.eu>

ENV DOCKER_DOMAIN phpbbbridge.contao.local
ENV CONTAO_COMPOSER_VERSION ^4.0.4

RUN install_php 5.4

RUN curl -LsS http://symfony.com/installer -o /usr/local/bin/symfony \
    && chmod a+x /usr/local/bin/symfony

RUN composer create-project --prefer-dist --no-dev --stability=beta --no-scripts --no-interaction --quiet \
        contao/standard-edition /var/www/share/${DOCKER_DOMAIN}/contao ${CONTAO_COMPOSER_VERSION} \
    && chmod -R 777 /var/www/share/${DOCKER_DOMAIN}/contao \
    && ln -s /var/www/share/${DOCKER_DOMAIN}/contao/web /var/www/share/${DOCKER_DOMAIN}/htdocs

# Overwrite default project init script
ADD bin/prepare-project.sh /etc/my_init.d/00_prepare-project.sh
RUN chmod +x /etc/my_init.d/00_prepare-project.sh

ADD bin/post-run.sh /etc/my_init.d/99_post-run.sh
RUN chmod +x /etc/my_init.d/99_post-run.sh





