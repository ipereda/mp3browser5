<?php
require('mp3browser5.php');

$config = array(
  # Where your MP3 music is available on the file system.
  # Leave empty to use the current directory.  
  #	'path' => "/mnt/media/music", // Linux path sample
  #	'path' => "F:\MUSIC", // Windows path sample
  'path' => "./music", // Current directory sample
  
  # The template file
  'template' => "template.inc",
  
  # Name of root entry in header
  'homeName' => "MP3 Web Browser with HTML5 Player",

  # Array of regular expression (regexp) matches for files/directories to hide
  'hideItems' => array("/^lost\+found/", "/^\./"),
  
  # Filename character set.  Typical values are utf-8 (linux) or iso-8859-1 (windows).
  'charset' => "utf-8",

  # Fetch and show cover images inside folders when listing folders?
  # Cover pics: Cover.jpg, cover.jpg, Folder.jpg, folder.jpg.
  'folderCovers' => true,

  # 'securePath' => false allows symlinks to folders outside the 'path' folder, but might be less secure.
  'securePath' => true,    
  
  # Set to true to display PHP errors and notices.  Should be set to false.
  'debug' => false,
);

$mp3Browser5 = new MP3Browser5($config);
$mp3Browser5->show_page();
?>
