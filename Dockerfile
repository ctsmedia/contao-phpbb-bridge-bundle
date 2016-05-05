FROM ctsmedia/baseimage-web:2.4.0
MAINTAINER Daniel Schwiperich | ctsmedia <entwicklung@cts-media.eu>

ENV DOCKER_DOMAIN phpbbbridge.contao.local
ENV CONTAO_COMPOSER_VERSION ^4.1.0
ENV BRIDGE_COMPOSER_VERSION dev-master
ENV PHPBB_VERSION 3.1.9

# Install Contao
RUN composer create-project --prefer-dist --no-dev --no-scripts --no-interaction \
        contao/standard-edition /var/www/share/${DOCKER_DOMAIN}/contao ${CONTAO_COMPOSER_VERSION} \
    && chmod -R 777 /var/www/share/${DOCKER_DOMAIN}/contao
# Add bridge Dependency
RUN composer --working-dir=/var/www/share/${DOCKER_DOMAIN}/contao --no-update \
        require ctsmedia/contao-phpbb-bridge-bundle ${BRIDGE_COMPOSER_VERSION} \
    && composer --working-dir=/var/www/share/${DOCKER_DOMAIN}/contao --no-scripts --prefer-dist --no-dev \
        update ctsmedia/contao-phpbb-bridge-bundle
# Setting Document Root
RUN cd /var/www/share/${DOCKER_DOMAIN}/ \
    && ln -s ./contao/web /var/www/share/${DOCKER_DOMAIN}/htdocs
# Preparing phpbb
RUN curl -L https://www.phpbb.com/files/release/phpBB-${PHPBB_VERSION}.zip -o phpbb.zip \
    && unzip phpbb.zip \
    && mv phpBB3 /var/www/share/${DOCKER_DOMAIN}/contao/web/phpbb



# Overwrite default project init script
ADD bin/prepare-project.sh /etc/my_init.d/00_prepare-project.sh
RUN chmod +x /etc/my_init.d/00_prepare-project.sh

ADD bin/post-run.sh /etc/my_init.d/99_post-run.sh
RUN chmod +x /etc/my_init.d/99_post-run.sh





