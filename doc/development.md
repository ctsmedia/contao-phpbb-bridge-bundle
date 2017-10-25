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
3. Go to http://localhost/phpbb and install the forum. Use following settings for db setup: 

    ```
    Host: db
    Db name: contao
    Db User: root
    DB Pass: contaodocker
    ```
    If you install a 3.1.x version you may get an error at the final stage. ignore it. 
 
4. You need (re)move the install dir after installation.  
Run `docker exec contao_phpbb_bridge_php mv /var/www/share/project/web/phpbb/install /var/www/share/project/web/phpbb/install123`  
You can access your phpbb installation now at http://localhost/phpbb/

5. Now follow the [installation guide](installation.md) for setting up the bridge. You've at least to setup a website root and layout in Contao.