#!/bin/sh

set -e

echo "***********************************"
printf "*** POST prepare project script\n"
echo    "---------------------------------------------------"
printf "| $(hostname -i) $DOCKER_DOMAIN                      | \n"
echo     "---------------------------------------------------"

### create database
printf "*** Creating database 'contao' and 'phpbb' if not already exists\n"
mysql -e "CREATE DATABASE IF NOT EXISTS contao CHARSET UTF8"
mysql -e "CREATE DATABASE IF NOT EXISTS phpbb CHARSET UTF8"