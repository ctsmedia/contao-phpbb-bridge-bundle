# Install and Setup 

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

### Configuration

#### Contao 

##### Forum Page Type

* TODO: Explain alias
* TODO: Explain path
* TODO: css class
* TODO: dynamic_layout


#### phpBB

See step 5 and 6 of installation process

 * Cookie Settings -> **Make sure the cookie domain is matching the contao website domain** Otherwise login sync will not work. 
 * Security Settings -> **Validate X_FORWARDED_FOR header:** set to NO. This is enforced by the bridge on every config change. So don't change that config value
 * *During Development:* Load Settings -> **Recompile stale style components:** to YES
 

#### Config Files

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