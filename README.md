# contao-phpbbBridge
phpbb 3.1 Bridge for Contao 4 

**not stable yet**

## Installation 

Add the bridge as dependency in your contao installation:
`composer require ctsmedia/contao-phpbb-bridge-bundle`

Modify the AppKernel.php and add the following to the registerBundles Method:
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

### Configuration

#### phpBB DB
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
