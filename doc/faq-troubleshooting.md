## FAQs - Problem solving

### *Q:* Bridge is not functioning correct. Log states: `IPs did not match. clientIP: xxx.xxx.xxx.xxx| EnvClientIp xxx.xxx.xxx.xxx| EnvServerIp yyy.yyy.yyy.yyy.`
*A:* Most likely you run a setup with PHP FPM running on a different host then the webserver. Add the following entry to you parameters.yml  
```yml
phpbb_bridge.allow_external_ip_access: true
```
This allows to access the bridge connector from a different IP. By default it's off because it could be considered a security risk. 

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
*A:* You may have found a bug. Open a Issue and be as descriptive as possible and attach relevant error logs if possible.  
You can find the phpbb bridge logs in the `phpbbforumdir/ext/ctsmedia/contaophpbbbridge/`