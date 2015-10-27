#!/bin/sh

set -e

echo "***********************************"
printf "*** PRE prepare project script\n"
echo    "---------------------------------------------------"
printf "| $(hostname -i) $DOCKER_DOMAIN                      | \n"
echo     "---------------------------------------------------"
if [ ! -e "project-initialized.flag" ]
then

  ### create project dir
  printf "*** creating project structure for domain ${DOCKER_DOMAIN} \n"
  #mkdir -p /var/www/share/${DOCKER_DOMAIN}/htdocs
  #mkdir -p /var/www/share/${DOCKER_DOMAIN}/repos
  mv /var/www/share/info.php /var/www/share/${DOCKER_DOMAIN}/htdocs/

  printf "*** setting vhost\n"
  sed -i s/DOCKER_DOMAIN/${DOCKER_DOMAIN}/g /etc/apache2/sites-available/000-default.conf

  ### create cert
  printf "*** creating ssl cert\n"
  openssl req -x509 -sha256 -newkey rsa:2048 \
    -keyout /etc/apache2/certs/${DOCKER_DOMAIN}.local.key \
    -out /etc/apache2/certs/${DOCKER_DOMAIN}.cert.pem -days 1240 -nodes \
    -subj "/C=DE/ST=NRW/L=COLOGNE/O=CTS GmbH/OU=IT/CN=${DOCKER_DOMAIN}"

  printf "*** Modifying Contao config\n"
  printf "**  Register CtsmediaPhpbbBridgeBundle\n"
  sed -i '/ContaoNewsletterBundle/a new Ctsmedia\\Phpbb\\BridgeBundle\\CtsmediaPhpbbBridgeBundle(),' \
    /var/www/share/${DOCKER_DOMAIN}/contao/app/AppKernel.php
  printf "**  Adding Composer Dependency for ctsmedia/contao-phpbb-bridge-bundle\n"
  rm -r /var/www/share/${DOCKER_DOMAIN}/contao/app/cache/* # Current Workaround for composer fail
  composer --working-dir=/var/www/share/${DOCKER_DOMAIN}/contao require ctsmedia/contao-phpbb-bridge-bundle dev-master

  printf "*** creating project initialized flag\n"
  touch project-initialized.flag
else
  printf "*** initialized flag found. Nothing to be done here\n"
fi
printf "\n"