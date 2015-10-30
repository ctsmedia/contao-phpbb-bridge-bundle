# contao-phpbbBridge
phpbb 3.1 Bridge for Contao 4 

**not stable yet**

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
