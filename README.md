# Contao-phpbbBridge Bundle ![logo_c2bb-36](https://cloud.githubusercontent.com/assets/14332656/14010600/eee4b534-f196-11e5-8cc4-a2c9146698c3.png)
## A bridge for integrating phpbb with a contao website / portal 

The contao-phpbbBridge Bundle connects your **Contao 4** CMS with a **phpbb 3.1** Forum and provides a tight, seamless integration between those systems.
It allows you to create portals, communities, intranet solutions without the usual problems of having either a bad cms integration in forum software or on the other side when using a cms the hassle with bad,insufficient forum modules. 
Just use the best systems of both worlds. 

## Features

### User, Auth and Session Synchronization

The bridge syncs your forum users with contao automatically and provides single sign on solution. 
If the user logs into the forum, he is automatically logged in to contao and vice versa. 

As a site admin you can configure which contao member group phpbb user get added, so you still can have non forum members. 

### Layout Sharing

For a real seamless integration the forum has to have the same look and feel like the rest of the website. 
The bridge acts as special contao page type, providing all the layout options you're used to. You can put a header, left column, footer around the forum and also put your theme style on to it.
When the forum is accessed it knows about the contao layout and strips it around appropriate.
But you don't have to. 

### Insert Tags

The bridge provides also a lot of insert tags ready to use

| Insert Tag | Description |
| ---------- | ----------- |
| {{phpbb_bridge::page_profile}} | Generates the link to the phpbb profile of the current logged in user (or to the Anonymous user if not logged in) |
| {{phpbb_bridge::page_login}} | Link to the phpbb login page |
| {{phpbb_bridge::page_logout}} | Link the phpbb logout page (uses the session id which is needed) |
| {{phpbb_bridge::page_register}} | Link the register phpbb page (uses the session id which is needed) |
| {{phpbb_bridge::page_resetpassword}} | Link to the phpbb send password page |
| {{phpbb_bridge::page_ucp}} | Link to the phpbb user control panel. |
| {{phpbb_bridge_user_profile::*}} | Generates the link to the phpbb user profile page. Accepts user id or username |


1. The bridge is not compatible to the contao dev mode (only in the forum area)
2. phpbb does allow for **multiple autologin sessions**. For example you can use autologin on your work and home pc.
Contao does _not_. Contao always uses only the last autologin session. Others are dropped (not really, there can only one be created)
The behavior on contao side is fixed implemented in the core and not changeable as of 4.1. So the bridge adapts the contao behavior and will drop all
autologin sessions other than the current one. 
We're already working on it with the contao core devs https://github.com/contao/core-bundle/issues/454

### References 

You want to see the bridge in action? 

 - http://www.racing4fun.de/ Germany's biggest motorcycle racer forum. Uses all features of the bridge and it's API with custom developed modules like a marketplace, laptimes and raceevents module, advertising module and so on   

## Installation 

The bridge can be setup within 15 minutes. Just like installing a Contao 4 and phgbb Module.  
It's offering lots of configuration for individual setups and optimization for those who want to,
but comes with good defaults and takes care you're not doing anything wrong.
You can find the [installation guide here](doc/installation.md)

## FAQs 

Before opening an issue or contact us for support please take a look in the [FAQs](doc/faq-troubleshooting.md)

## Development / Contributing

We provide a set of development tools to get you quickly started if you like to contribute or modify the bridge.
See the [development guide](doc/development.md) how to setup a local docker development and test environment.

Before spending a lot of time on a Pull Request please open a issue on github and discuss with the core devs.  

## Support / Customization / Extensions / Licensing
 
The bridge is itself is completely free to use. If you encounter a bug or miss a feature you can open always open an issue on github and ask for help there.
We do our best to continuously improve the bridge.
  
If you require support, are in need of additional functionality etc drop us a message at sales@cts-media.eu   