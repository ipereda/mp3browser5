## About

**mp3browser5** is a light-weight web-based browser and streamer for mp3 files with two HTML5/Javascript sound players provided by soundmanager2.
+ [Soundmanager2 Page Player Demo][1]
+ [Soundmanager2 Bar-UI Player Demo][2]
+ [**mp3browser5** Demo][3] 

## Features

+ Responsive Web Design for any mobile device.
+ Configurable (via index.php).
+ Cover images (folder.jpg, cover.jpg etc.) are shown in folders if available.
+ Playlist (add/remove) available with Bar-UI player.
+ Small size (180K download).
+ Open source: Feel free to edit the program to fit your needs.

## Requirements

+ A webserver with PHP
+ Most platforms are supported
+ With Lighttpd web server works great

## Installation

1. Extract the distribution archive to a web accessible path (e.g. /var/www/mp3browser5)
2. Optional: Edit path in index.php to match your site (e.g. /mnt/media/music)
3. Enjoy your music collection through a browser (e.g. http://myserver/mp3browser5

## Files

+ index.php : A few configurable options.
+ template.inc: The main HTML template with soundmanager API setup.
+ mp3browser5.php: The PHP code (Object-oriented programming).
+ /css: The CSS styles folder.
+ /images: The image files folder.
+ /scripts: The Javascript code folder.

## Troubleshooting

+ I only get a blank screen!: Set 'debug' => true in index.php, you might see some helpful info. Also look into your web server's error.log.
+ Only 130 seconds of my songs are played!: Set max_execution_time = 0 in php.ini.
+ I need password controlled access: Your web server can provide this. Google for ".htaccess" if you are using Apache, and "auth.backend.plain.userfile" if you are using Lighttpd.
+ Unicode characters (asian etc.) look weird: Try replacing "iso-8859-1" with "utf-8" in the 'charset' configuration in index.php

[1]: http://www.schillmania.com/projects/soundmanager2/demo/page-player/
[2]: http://www.schillmania.com/projects/soundmanager2/demo/bar-ui/
[3]: http://mp3browser5.ipereda.com

## Detected Issues

