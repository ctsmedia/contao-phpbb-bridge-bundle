# contao-phpbbBridge
phpbb 3.1 Bridge for Contao 4 

**not stable yet**

## Known Issues \ Limitations

1. The bridge is not compatible to the contao dev mode (only in the forum area)

## FAQs - Problem solving

### *Q:* Layout is not generated in Forum area
*A:* Make sure you've setup the phpbb site in contao correctly 
and *that the path to the layout are writeable*, meanging the `phpbbroot/ext/ctsmedia/contaophpbbbridge/styles/all/template/event`

### *Q:* Coming back after some time I'm logged in to the forum but not contao (or vice versa)
*A:* Make sure the expire times for login and login are synced. See 4.1 of the installation dialog. Adjust Session Expire and Autologin Expire to ypur likings
 
### *Q:* Login is not working
*A:* 
1. Make sure you've setup the phpbb site in contao correctly
2. Make sure the Cookie Domain setting in phpbb is matching the contao domain. See Config Section below.
3. clear the caches on both sides. Often the problems are caused by outdated config files which are cached by both systems


### *Q:* It's still not working
*A:* You may have found a bug. Open a Issue and be as descriptive as possible and attach relevant error logs if possible


## Installation 

### Prerequisites

You need to have a contao 4.X and phpbb 3.1 installation. phpbb can be put below or beside / above contao
  
For example:  
```
|_contao
  |...
  |_web
|_phpbb
```

or:

```
|_contao
  |...
  |_web
    |_phpbb
```

### Install Bridge

1. Add the bridge as dependency in your contao installation:
`composer require ctsmedia/contao-phpbb-bridge-bundle`

2. Modify the **AppKernel.php** and add the following to the registerBundles Method:
    `new Ctsmedia\\Phpbb\\BridgeBundle\\CtsmediaPhpbbBridgeBundle(),`

    For Example:  
    ```php
    public function registerBundles()
      {
          $bundles = [
              new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
              new Symfony\Bundle\SecurityBundle\SecurityBundle(),
         ...
              new Contao\NewsBundle\ContaoNewsBundle(),
              new Contao\NewsletterBundle\ContaoNewsletterBundle(),
              new Ctsmedia\Phpbb\BridgeBundle\CtsmediaPhpbbBridgeBundle(),
          ];
    
          ...
    
          return $bundles;
      }
    ```
    
    Add the phpbb Bridge routes to the contao installation. **Make sure this is added before the contao route bundle: ContaoCoreBundle:**
    ```yml
        CtsmediaPhpbbBridgeBundle:
            resource: "@CtsmediaPhpbbBridgeBundle/Resources/config/routing.yml" 
    ```

3. Go to module subfolder `contaoroot/vendor/ctsmedia/contao-phpbb-bridge-bundle/src/Resources/phpBB/ctsmedia/contaophpbbbridge` and run `composer install`
This will install a needed http library to communicate with contao. phpbb is currently not able to install module dependencies by itself. 
In release versions we will pre compile the dependencies for phpbb 
    
4. Login to the Contao Backend and create a Page of type 'PhpBB Forum Site' and configure it appropriate. You'll get some log messages of something fails / succeeds.
Important is the alias and path to the forum. The Bridge module will create a symlink to it so you can access the forum right on. 

    4.1 Go to the settings section and save once. The session expire and autologin time now get synced. You should get 2 confirmation messages. Always change those values from the contao side. 

5. Once Contao has made the link to forum login to the admin panel of phpbb and activate the contao extension under `Customize -> Manage Extensions`
If the module doesn't appear, purge the cache (`General -> Find 'Purge the cache' and click Run now`)

6. At this moment the bridge is already capable of synching your countao frontend login with phpbb. To sync also the logins made via the phpbb forums you've to enable the bridge auth provider.
Goto `General -> Authentication` in the Adminpanel and choose *Contao* in the select feld. 
If this entry doesn't appear yet, purge the cache (`General -> Find 'Purge the cache' and click Run now`)

### Optimization
The bridge is doing some internal http requests at some points. To increase performance you may want to add a local dns entry 
for your domain on your server. So the requests never leave the server.
Especially if you use dynamic layout rendering

### Configuration

#### Contao 

##### Forum Page Type

* TODO: Explain alias
* TODO: Explain path
* TODO: css class
* TODO: dynamic_layout


#### phpBB

See step 5 and 6 of installation process

 * Cookie Settings -> **Make sure the cookie domain is matching the contao website domain** Otherwise login sync will not work 
 * Security Settings -> **Maximum number of login attempts per IP address:** set to 0 (could possibly lockout the bridge logins)
 * Security Settings -> **Validate X_FORWARDED_FOR header:** set to NO (could possibly lockout the bridge)
 * *During Development:* Load Settings -> **Recompile stale style components:** to YES
 

#### Files

##### phpBB DB
If phpBB is installed in it's own DB the bridge needs to know this.
Add the db connection and make the bridge aware in config.yml:
```yml
# Doctrine configuration
doctrine:
    dbal:
        default_connection: default
        connections:
            default:
                driver:   pdo_mysql
                host:     "%database_host%"
                port:     "%database_port%"
                user:     "%database_user%"
                password: "%database_password%"
                dbname:   "%database_name%"
                charset:  UTF8
            phpbb:
                driver:   pdo_mysql
                host:     "%database_host_phpbb%"
                port:     "%database_port_phpbb%"
                user:     "%database_user_phpbb%"
                password: "%database_password_phpbb%"
                dbname:   "%database_name_phpbb%"
                charset:  UTF8
                
services:
    phpbb_bridge.connector:
        class: Ctsmedia\Phpbb\BridgeBundle\PhpBB\Connector
        arguments: 
            - "@doctrine.dbal.phpbb_connection"                
```

## Usage in Development

### Access phpbb User
`System::getContainer()->get('phpbb_bridge.connector')->getUser("ctsmedia");`

## Development / Testing

We provide a set of development tools if you like to contribute or modify the bridge

### Docker

This bundle comes with a pre configured docker container which gives you a working development 
environment within minutes.
You get a: 
 - full equiped LAMP Server 
 - contao/standard-edition
 - already enabled bridge modul
 - phpbb @TODO
 - phpmyadmin

1. Build the container: `docker build -t phpbbBridge:latest ./`

2. You can now run the container:
`docker run --name phpbb_bridge phpbb_bridge:latest /sbin/my_init --enable-insecure-key`

3. The Container is now running. Access it via IP: `http://containerip/install.php`
or via the host entry: `http://phpbbbridge.contao.local/install.php`


#### Access your container
We've ssh enabled for deployment and access.  
You can add your own key or use the default key which is already enabled.  
For development purpose using the *insecure* is perfectly fine as long as the container runs on you own machine.

Download the key and put it where you like (right here or at ~/.ssh/)  
```
curl -o insecure_key -fSL https://github.com/phusion/baseimage-docker/raw/master/image/services/sshd/keys/insecure_key
chmod 600 insecure_key
```  
Create a local gulp config (is ignored by git)  
```
touch gulp.config.js
echo "docker.sftp.key='PATH_TO_KEY/insecure_key';" >> gulp.config.js 
```  

You can now use the gulp task `docker:watch` which automatically uploads all you changes made to the code (under src/).  
For sure you can use normal SFTP and connect to you container if you like. The gulp thing is completely optional

The gulp task will also start your container if stopped and modify your /etc/hosts if it's writable so you can access the container not 
only via ip (which is shown on docker run or if you connect)

#### gulp
To use gulp you need to have node installed. If so, just call `npm install` and all dependencies will be loaded.
You then can run `gulp docker:watch`
