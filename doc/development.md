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
You get a: 
 - full equiped LAMP Server 
 - contao/standard-edition
 - already enabled bridge modul
 - phpbb
 - phpmyadmin

1. Build the container: `docker build -t phpbb_bridge:latest .`

2. Running the Container: 

2.1 If you just want to run and test the container
`docker run -d --name phpbb_bridge ctsmedia/phpbb_bridge:latest /sbin/my_init --enable-insecure-key`
2.2 If you want do develop on the bridge mount the src folder appropriate:
```
docker run -d \
    -v $(pwd):/var/www/share/phpbbbridge.contao.local/contao/vendor/ctsmedia/contao-phpbb-bridge-bundle \
    --name phpbb_bridge ctsmedia/phpbb_bridge:latest \
    /sbin/my_init --enable-insecure-key
```
So what this does is, it starts the container and mounts your current directory (the bridge repos) into the vendor folder of contao, overwriting
(more exactly just overlaying) the original bridge src which where loaded from packagist defined by the version number in the Dockerfile 

3. The Container is now running. Access it via IP: `http://containerip/install.php`
or via the host entry: `http://phpbbbridge.contao.local/install.php` and follow the install setup described in the [installation guide](installation.md)
You've 

Additional Info: 
- You can use mysql root user with no password. There are two dbs created already: `contao` and `phpbb`. You can either one db for both or seperate them.
The path to phpbb is /var/www/share/${DOCKER_DOMAIN}/phpbb where ${DOCKER_DOMAIN} is set to whatever you've set in the Dockerfile. 
By default it is: /var/www/share/phpbbbridge.contao.local/phpbb
- You may want to change your dir permission settings so the brigde can create some needed files. They get automatically ignored via .gitignore. Just run
`find . -type d -exec chmod 0777 {} \;` so the bridge is able to write those files. This is only needed if you mount the git repos into the container. 


#### Access your container
We've ssh enabled for access. But you can also login via docker easily using:
  
  `docker exec -it phpbb_bridge bash`
  
For ssh access you can add your own key or use the default key which is already enabled.  
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

#### Starting your Container

The gulp task can start your container if stopped and modify your /etc/hosts if it's writable so you can access the container not 
only via ip (which is shown on docker run or if you connect).
It's easy as it gets. Just run the gulp task: `docker:init`

#### gulp
To use gulp you need to have node installed. If so, just call `npm install` and all dependencies will be loaded.
You then can run `gulp docker:watch`