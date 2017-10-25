## Development / Contributing

## Usage in Development

If you want to access the bridge in your modules you can do so easily:

### Access phpbb User
`System::getContainer()->get('phpbb_bridge.connector')->getUser("ctsmedia");`

## Development / Testing

We provide a set of development tools if you like to contribute or modify the bridge

### Docker

This bundle comes with a pre configured docker container which gives you a working development 
environment within minutes.

1. Run `docker-compose up -d` from the main directory
2. Run `docker exec contao_phpbb_bridge_php bash -c "php ../install-demo.php"`. You now have a contao managed installation with official demo content running at http://localhost/
3. Go to http://localhost/phpbb or on mac to http://docker.for.mac.localhost/phpbb and install the forum. Use following settings for db setup: 

    ```
    Host: db
    Db name: contao
    Db User: root
    DB Pass: contaodocker
    ```
    If you install a 3.1.x version you may get an error at the final stage. ignore it. 3.2 is fully compatible to php 7 and will not throw.  
 
4. You need (re)move the install dir after installation.  
Run `docker exec contao_phpbb_bridge_php mv /var/www/share/project/web/phpbb/install /var/www/share/project/web/phpbb/install123`  
You can access your phpbb installation now at http://localhost/phpbb/

5. Now follow the [installation guide](installation.md) for setting up the bridge. Continue / Start at step 5.  
You've at least to setup a website root and layout in Contao. Remember the admin user for contao is the one from the demo installation: k.jones

**Info:** You should not use localhost as domain, as this leads to problems that the php fpm container tries to connect to itself instead of the web container. 
On linux you can use the container web ip directly for example, or set a dev domain to the container web ip. On mac this is not possible. You need to work with the default available domain beside localhost: http://docker.for.mac.localhost/

### phpbb sources

We don't have phpbb as dev requirement in our composer.json. because phpbb-app repos and composer package is a mess and not well maintaned. 
most version are also incompatible because they rely on differen symfony versions for example than contao. 

If you want to check phpbb code during development etc just copy it out of the container to for example the vendor folder
`docker cp contao_phpbb_bridge_php:/var/www/share/project/web/phpbb ./vendor` 

### Load bundle as separate package
By default the dev-master version of the bundle is installed in the Docker container and then a mount to the sources is made. That works fine in most cases. 
But if you need to make changes to the composer.json they will be ignored by composer because the lock file is already written as well as the installed.json from composer. 

You could now modify the composer.lock and installed.json file or go a more clean way: 

1. in the docker-compose.yml active the seconnd mount entry and comment out the first one so that it looks like this:
```yml
...
    #This is what you want in general. You local source directly mounted into the vendor folder
    #- .:/var/www/share/project/ctsmedia/contao-phpbb-bridge-bundle

    # Use this if you need composer related changes (like extra section to be reloaded)
    # then remove in container the dep and set it new with a path repos set
    - .:/var/www/share/contao-phpbb-bridge-bundle
...
```

2. Now we add the external mount as repository to search for composer: (Access the container before via `docker exec -it contao_phpbb_bridge_php bash`)
```bash
composer config repositories.bridge path ../contao-phpbb-bridge-bundle
```

3. Add a version entry to the bundle composer.json (don't do this in the container, do this on the host system)
```yaml
{
    "name": "ctsmedia/contao-phpbb-bridge-bundle",
    "description": "phpbb 3.1 / 3.2 Bridge for Contao 4 Standard and Managed Edition",
    "type": "contao-bundle",
    "version": 2.0,
    "keywords": [
...
```

4. Load the new version into the system from the container:
```bash
composer require ctsmedia/contao-phpbb-bridge-bundle:2.0
chown -R www-data:www-data /var/www/share/project/
```

5. Done. After the post scripts are run by composer your changes are in use. 