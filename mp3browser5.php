<?php

class MP3Browser5 {
  
  var $path, $homeName, $streamLib, $template, $charset, $securePath, $hideItems;

  /**
   * @param array $config Associative array with configuration
   */  
  function MP3Browser5($config) {
    if ($config['debug']) {
      ini_set('error_reporting', E_ALL);
      ini_set('display_errors', 1);
    } else {
      ini_set('display_errors', 0);
    }
    if (!function_exists('preg_match')) {
      $this->show_fatal_error('The preg_match function does not exist. Your PHP installation lacks the PCRE extension');
    }
    if (!is_readable($config['template'])) {
      $this->show_fatal_error("The \$config['template'] \"{$config['template']}\" isn't readable");
    }
    session_start();
    
    $this->resolve_config($config);    
    $this->workaround_missing_functions();    
    $this->streamLib = new StreamLib();
  }

  /**
   * Resolves the configuration.
   * @param array $config
   */
  function resolve_config($config) {
    foreach ($config as $key => $value) {
      switch ($key) {
        case 'path';
          $this->path = new Path($config['path'], $this->securePath);
          if ($this->path->full === NULL) {
            $this->show_fatal_error(Logger::pop());  //Exits
          }
          break;
        default:
          $this->$key = $value;
          break;
      }
    }
  }

  function workaround_missing_functions() {
    $message = "";
    if (!function_exists('utf8_encode')) {
      $message .= "Warning: Your PHP installation lacks the XML Parser Functions extension<br>\n";
      function utf8_encode($str) {
        return $str;
      }
    }
    if (!function_exists('mb_substr')) {
      $message .= "Warning: Your PHP installation lacks the Multibyte String Functions extension<br>\n";
      function mb_substr($str, $start, $length = 1024, $encoding = false) {
        return substr($str, $start, $length);      
      }
      function mb_convert_case($str, $mode, $encoding = false) {
        return ucwords($str);      
      }
      function mb_convert_encoding($entry , $toEncoding = null, $fromEncoding = null) {
        return utf8_encode($entry);
      }
      function mb_strlen($str, $encoding = false) {
        return strlen($str);      
      }  
    }
    if (!function_exists('normalizer_normalize')) {
      $message .= "Warning: Your PHP installation lacks the Internationalization Functions extension<br>\n";
      function normalizer_is_normalized($str) {
        return true;
      }
      function normalizer_normalize($str) {
        return $str;
      }
    }
    if (!empty($message) && $this->debug) {
      Logger::log($message);
    }
  }

  /**
   * Exit with error.
   * @param string $message Error message
   */
  function show_fatal_error($message) {
    echo "<html><body text=red>ERROR: $message</body></html>";
    exit(0);
  }
  
  /**
   * Display requested page, or deliver ajax content.
   */
  function show_page() {

	if (is_file($this->path->full)) {
      $this->streamLib->stream_file_auto($this->path->full);
	  exit(0); // Exits
    } 
	
    if (isset($_GET['content'])) {
      $this->show_content($this->path->full); // Exits
    }

    $search = array( "/%myplayer%/" );
    $replace = array( $this->html_myplayer() );

    $template = implode("", file($this->template));
    print preg_replace($search, $replace, $template);
    exit(0);
  }
			  	
  /**
   * Content for a page (JSON).  Exits.
   */
  function show_content($fullPath) {
      $entries = $this->list_folder($fullPath, $this->hideItems); 
      $byInitial = $this->items_by_initial($entries); 
	  $content = $this->html_folder($byInitial); 

      $result = array();
	  $result['itemtitle'] = $this->html_itemtitle();
	  $result['content'] = "<ul class='playlist' id='playlist'>" . $content . "</ul>"; 
	  $result['cover'] = $this->html_cover();
      $result['breadcrumb'] = $this->html_breadcrumb();
	  
      if (!empty($this->path->relative)) {
        $result['title'] = $this->homeName .": ". $this->path->relative;
      } else {
        $result['title'] = $this->homeName;
      }
      $result['error'] = Logger::pop();
      
      print $this->json_encode($result);
      exit(0);
  }
   
  /**
   * @return string myplayer for Bar-UI playlists (HTML).
   */
   function html_myplayer() {
	return "<div class='sm2-bar-ui fixed full-width flat'>
				<div class='bd sm2-main-controls'>
					<div class='sm2-inline-element sm2-button-element'>
						<div class='sm2-button-bd'>
							<a href='#clear' class='sm2-inline-button clear'>Clear</a>
						</div>
					</div>
					<div class='sm2-inline-element sm2-inline-status'>
						<div class='sm2-playlist'>
							<div class='sm2-playlist-target'>
								<noscript><p>JavaScript is required.</p></noscript>
							</div>
						</div>
						<div class='sm2-progress'>
							<div class='sm2-row'>
								<div class='sm2-inline-time'>0:00</div>
									<div class='sm2-progress-bd'>
										<div class='sm2-progress-track'>
											<div class='sm2-progress-bar'></div>
											<div class='sm2-progress-ball'></div>
										</div>
									</div>
								<div class='sm2-inline-duration'>0:00</div>
							</div>
						</div>
					</div>
					<div class='sm2-inline-element sm2-button-element'>
						<div class='sm2-button-bd'>
							<a href='#next' title='Next' class='sm2-inline-button next'>&gt; next</a>
						</div>
					</div>
					<div class='sm2-inline-element sm2-button-element sm2-menu'>
						<div class='sm2-button-bd'>
							<a href='#menu' class='sm2-inline-button menu'>menu</a>
						</div>
					</div>
				</div>
				<div class='bd sm2-playlist-drawer sm2-element'>
					<!-- playlist content is mirrored here -->
					<input type='hidden' value='0' id='theValue' />
					<div class='sm2-playlist-wrapper'>
						<ul id='userPlaylist' class='sm2-playlist-bd'>
						</ul>
					</div> 
				</div>
			</div>";
   }
  
  /**
   * Simple JSON encoder.
   * @param array $array Array to encode
   * @return string JSON encoded content
   */
  function json_encode($array) {
    $json = array();
    $search = array('|"|', '|/|', "/\n/", "/\r/");
    $replace = array('\\"', '\\/', '\\n', '\\r');
    foreach ($array as $key => $value) {
      $json[] = ' "' . preg_replace($search, $replace, $key)
              . '": "' . preg_replace($search, $replace, $value) . '"';
    }
    return "{\n" . implode($json, ",\n") . "\n}";
  }
  
  /**
   * Format music folder content as HTML.
   *
   * @return string Formatted HTML with folder content
   */
    function html_folder($byInitial) {
    $output = "";
    if (count($byInitial) > 0) {
		foreach ($byInitial as $first => $group) {
			$entry = "";
			$rows = count($group);
			for ($row = 0; $row < $rows; $row++) {
				$entry .= "";
				$item = @ $group[$row];
				if (is_object($item)) {
					$entry .= $item->show_link(); 
				} 
				$entry .= "";
			}  
			$output .= $entry;
		}
	}
	$output;
	return $output;
  }

  /**
   * @param array $entries array of entries
   * @param Url $url Url object
   * @param Path $path Path object
   * @param string $charset Character set
   * @param boolean $folderCovers Folder covers enabled
   * @return array Items grouped by initial ([initial][Item arrays])
   */
  function items_by_initial($entries) {
    $group = array();
    foreach ($entries as $plainItem) {
        $item = new Item($plainItem, $this->charset, $this->folderCovers, $this->path);
		$group[0][] = $item;
    }
    ksort($group);
    return $group;
  }

  /**
   * List folder content.
   * @return array Array with all allowed file and folder names
   */
  function list_folder($path, $hideItems) {
    $folderHandle = dir($path);
    $items = array();
    while (false !== ($item = $folderHandle->read())) {
      foreach ($hideItems as $hideItem) {
        if (preg_match($hideItem, $item)) continue 2; // to while loop
      }
      $fullPath = "$path/$item";
      if (is_dir($fullPath) || (is_file($fullPath) && $this->valid_suffix($item))) {
        $items[] = $item;
      }
    }
    $folderHandle->close();
	
	natcasesort($items);
	
	return $items;
  }

  /**
   * @return string Formatted HTML with cover image (if any)
   * Add function for add all album songs to playlist
   */
  function html_cover() {
    $link = $this->path->cover_image();
    if (!empty($link)) {
		return "<a href=javascript:addAlbum()><img class=cover src=\"$link\"></a>"; 
    }
    return "";
  }

   /**
   * @return string Formatted HTML with Home icon
   */
  function html_linkedtitle() {
    return "<a href=\"javascript:changeDir('')\" ><img class=icon src='images/home.png'></a>"; 
  }

  /**
   * @return string Formatted HTML with breadcrumbs for item
   */
  function html_itemtitle() {
    $path = $this->path->relative;
    $parts = explode("/", trim($path, "/"));
    if ($parts[0] == '') {
      $parts = array("<span class=title>{$this->homeName}</span>");
    }
    $items = array();
    $currentPathtitle = $encodedPathtitle = "";
    for ($i = 0; $i < count($parts); $i++) {
		$currentPathtitle = "/{$parts[$i]}";
		$encodedPathtitle = Util::path_encode($currentPathtitle);
		$displayItemtitle = Util::convert_to_utf8($parts[$i], $this->charset);
	}
	$items[] = "<span class=path>$displayItemtitle</span>";
	$output = implode($items, "&nbsp;&raquo;&nbsp;");
	return $output;
  }
  
  /**
   * @return string Formatted HTML with bread crumbs for folder
   */
  function html_breadcrumb() {
    $path = $this->path->relative;
	$link = $this->path->cover_image();
    $parts = explode("/", trim($path, "/"));
    if ($parts[0] == '') {
      $parts = array();
    }
    $items = array($this->html_linkedtitle());
    $currentPath = $encodedPath = "";
    for ($i = 0; $i < count($parts); $i++) {
      $currentPath .= "/{$parts[$i]}";
      $encodedPath = Util::path_encode($currentPath);
      $displayItem = Util::convert_to_utf8($parts[$i], $this->charset);

	  if ($i < count($parts)) { 
        $encodedPath = Util::js_url($encodedPath);
		$items[] = "<a href=\"javascript:changeDir('$encodedPath')\" ><img class=icon src='images/level.png'></a>";
      }
	}

    if (!empty($link)) {
		$coverImage = $this->path->cover_image();
		$items[$i] = "<a href=\"javascript:changeDir('$encodedPath')\" ><img class=iconcover src=\"$coverImage\"></a>";
	  }
	$output = implode("&nbsp;", $items);
    return $output;
  }
   
  /**
   * Checks if $entry has .mp3
   *
   * @return boolean True if valid.
   */
  function valid_suffix($entry) {
      if (preg_match("/\." . "mp3" . "$/i", $entry)) {
         return true;
      } else {
		 return false;
      }
  }

  /**
   * Find all items in a folder recursively.
   */
  function folder_items($folder, $allItems) {
    $fullPath = $this->path->root . "/$folder";
    $items = $this->list_folder($fullPath, $this->hideItems);
    foreach ($items as $item) {
      if (is_file("$fullPath/$item")) {
        $allItems[] = "$folder/$item";
      } else {
        $allItems = $this->folder_items("$folder/$item", $allItems);
      }
    }
    return $allItems;
  }

  /**
   * Delivers messages as JSON and exits.
   */
  function show_messages() {
    $result = array();
    $msg = Logger::pop();
    $result['error'] = $msg;
    print $this->json_encode($result);
    exit(0);
  }

  /**
   * MacOSX encodes filenames in UTF-8 on Normalization Form D (NFD), while
   * "everyone" else uses NFC. Normalizer is only available on PHP 5.3, or
   * with the PECL Internationalization extension ("intl").
   */
  function decode_ncd($str) {
    if (!normalizer_is_normalized($str)) {
      $str = normalizer_normalize($str);
    }
    return $str;
  }
}


class Logger {
    public static function log($msg) {
		if (!isset($_SESSION['message'])) {
			$_SESSION['message'] = $msg;
		} else {
			$_SESSION['message'] .= "<br>\n$msg";
		}
	}

    public static function pop() {
		if (isset($_SESSION['message'])) {
			$msg = $_SESSION['message'];
			unset($_SESSION['message']);
			return $msg;
		}
	}
}

class Path {

  var $root = NULL;     # e.g. /mnt/music
  var $relative = NULL; # e.g. Wilco/Walken.mp3
  var $full= NULL;      # e.g. /mnt/music/Wilco/Walken.mp3
  var $directFileAccess = false;

  /**
   * Try to resolve a safe path.
   */
  function Path($rootPath, $securePath) {
    
    if (!empty($rootPath) && !is_dir($rootPath)) {
      Logger::log("The \$config['path'] \"$rootPath\" isn't readable");
      return;
    } else if (empty($rootPath)) {
      $this->directFileAccess = true;
      $rootPath = getcwd();
    }
    
    $relPath = "";
    if (isset($_GET['path'])) {
      $getPath = stripslashes($_GET['path']);
      # Remove " (x22) and \ (x5c) and everything before ascii x20
      $getPath = Util::strip($getPath);
      $getPath = preg_replace(array("|%5c|", "|/\.\./|", "|/\.\.$|", "|^[/]*\.\.|"), 
                              array("", "", "", ""), $getPath);
      $getPath = trim($getPath, "/");
      if (is_readable("$rootPath/$getPath")) {
        $relPath = $getPath;
      } else {
        Logger::log("The path <i>$getPath</i> is not readable.");
      }
    }
    $fullPath = "$rootPath/$relPath";
    # Avoid funny paths
    $realFullPath = realpath($fullPath);
    $realRootPath = realpath($rootPath);
    
    if (($securePath && $realRootPath != substr($realFullPath, 0, strlen($realRootPath)))
         || !(is_dir($fullPath) || is_file($fullPath))) {
       $relPath = "";
       $fullPath = $rootPath;
    }
    $this->root = $rootPath;    # e.g. /mnt/music
    $this->relative = $relPath; # e.g. Wilco/Walken.mp3
    $this->full = $fullPath;    # e.g. /mnt/music/Wilco/Walken.mp3
  }

  function cover_image($addedPath = null) {
    $pathRelative = $this->relative;
    if ($addedPath) {
      $pathRelative .= "/$addedPath";
    }
    $covers = array( "cover.jpg", "Cover.jpg", "folder.jpg", "Folder.jpg" );
    foreach ($covers as $cover) {
      if (is_readable($this->root . "/$pathRelative/$cover")) {
        $coverPath = Util::path_encode("$pathRelative/$cover", false);
        if ($this->directFileAccess) {
		  return $coverPath;
        } else {
          return "index.php?path=" . $coverPath;
        }
      }
    }
    return "";
  }
}

class Url {

  var $root = NULL;     # e.g. http://mysite.com
  var $relative = NULL; # e.g. mp3browser5
  var $full = NULL;     # e.g. http://mysite.com/mp3browser5
  
  /**
   * Resolve the current URL into $root, $relative and $full.
   * @param string $rootUrl The input URL
   */
  function Url($rootUrl) {
    if (empty($rootUrl)) {
      $folder = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME);
      $this->root = Url::protocol() . $_SERVER['HTTP_HOST'] . $folder;
    } else {
      if (!preg_match('#^https?:/(/[a-z0-9]+[a-z0-9:@\.-]+)+#i', $rootUrl)) {
        Logger::log("The \$config['url'] \"{$this->root}\" is invalid");
        return;
      }
      $this->root = trim($rootUrl, '/');
    }
	
    $this->relative = Util::pathinfo_basename($_SERVER['SCRIPT_NAME']);
    $this->full = $this->root . "/" . $this->relative;
  }

  /**
   * @return string Current url scheme
   */
  function protocol() {
    if (isset($_SERVER["HTTPS"])) {
        return "https://";
    }
    return "http://";
  }
}

/**
 * A file or folder item.
 */
class Item {
  var $item;
  var $urlPath;
  var $charset;
  var $folderCovers;
  var $path;

  function Item($item, $charset, $folderCovers, $path) {
    $this->item = $item;  
    $this->charset = $charset;
    $this->folderCovers = $folderCovers;
    $this->path = $path;
  } 
  
  function url_path() {
    if (empty($this->urlPath)) {
      $this->urlPath = Util::path_encode($this->path->relative . "/" . $this->item);
    }
    return $this->urlPath;
  }

  function js_url_path() {
    return Util::js_url($this->url_path());    
  }
  
  function display_item() {
    $displayItem = Util::word_wrap($this->item, $this->charset);
    $displayItem = Util::convert_to_utf8($displayItem, $this->charset);
    return $displayItem;
  }
  
  function show_link() {
    if (empty($this->item)) {
      return "&nbsp;";
    } elseif (is_dir($this->path->full . "/" . $this->item)) {
	  return $this->dir_link();
	} else {
      return $this->file_link();
    }
  }
  
  function dir_link() {
    global $num; $num = 0;  //Counter of album songs
	$image = $this->show_folder_cover($this->item);
    $link = Util::play_url($this->url_path());
    $jsurl = $this->js_url_path();
    $item = $this->display_item();

	if (!empty ($image)) { // album folder
		$enlace = "<div class=discs >$image"; // Thumb cover
		$enlace .= "<li style='border:none'><a class=disctitle href=javascript:changeDir('$jsurl') >$item</a></li></div>"; // album title and link
		
	}	else {	 // directory folder
		$enlace = "<li><a href=javascript:changeDir('$jsurl') ><img class=foldericon src=images/folder.png></a>";
		$enlace .= "<a style='width:100%' class='enlace' href=javascript:changeDir('$jsurl') >$item</a></li>"; 
	}
	return $enlace;
  }
  
  function file_link() {
	global $num; $num++; //Counter of album songs
    $link = Util::play_url($this->url_path());
	$link = Util::js_url($link);
	$item = str_replace(".mp3" , "" , $this->display_item());
	$itemcode = str_replace(".mp3" , "" , preg_replace('/\s+/', '%20', $this->display_item())); // Encoding blank spaces for javascript string
	// Add link to Bar-UI playlist
	$enlace = "<li><a id='cancion{$num}' href=javascript:addCancion(\"{$link}\",\"{$itemcode}\") ><img class=addicon src=images/add.png></a>";
	// Item
	$enlace .= "<a class='songtitle'>$item</a>";
	// Download
	$enlace .= "<a href=javascript:downCancion(\"{$link}\") ><img class=addicon src=images/download.png></a></li>";
	return $enlace;	
  }
    
  function direct_link($urlPath) {
    return Util::path_encode($urlPath, false);
  }
  
  function show_folder_cover($addedPath) {
    $image = "";
    if ($this->folderCovers) {
      $coverImage = $this->path->cover_image($addedPath);
      if (!empty($coverImage)) {
        $jsUrlPath = Util::js_url(Util::path_encode($this->path->relative . "/$addedPath"));
        $image = "<a href=\"javascript:changeDir('" . $this->js_url_path() . "')\" >
                  <img class=disccover src=\"$coverImage\"></a>"; // Thumb cover and album link
      }
    }
    return $image;
  }  
}

/**
 * Common static utility functions.
 */
class Util {
  /**
   * Need to encode url entities twice in javascript calls.
   */
	public static function js_url($url) {
		return preg_replace("/%([0-9a-f]{2})/i", "%25\\1", $url);
	}
  
  /**
   * Encode a fairly readable path for the URL.
   */
    public static function path_encode($path, $encodeSpace = true, $fromCharset = null) {
		$search = array("|^%2F|", "|%2F|");
		$replace = array("", "/");
		if ($encodeSpace) {
			$search[] = "|%20|";
			$replace[] = "+";
		}
		if (!empty($fromCharset)) $path = Util::convert_to_utf8($path, $fromCharset);
		return preg_replace($search, $replace, rawurlencode($path)); 
	}
  
  /**
   * Play the URL
   */
	public static function play_url($urlPath) {
		return "index.php?path=" . $urlPath;
	}
 
    public static function strip($str) {
		return preg_replace('/[^\x20-\x21\x23-\x5b\x5d-\xff]/', "", $str);
	}

    public static function word_wrap($item, $charset) {
		//$pieces = split(" ", preg_replace("/_/", " ", $item)); DEPRECATED
		$pieces = explode(" ", preg_replace("/_/", " ", $item));
		$result = array();
		foreach ($pieces as $piece) {
			$current = $piece;
			while (mb_strlen($current) > 40) {
				$result[] = mb_substr($current, 0, 40, $charset);
				$current = mb_substr($current, 40, 2048, $charset);
			}
			$result[] = $current;
		}
		return implode(" ", $result);
	}

  /**
   * The MP3 player can only handle utf-8.
   */
	public static function convert_to_utf8($entry, $fromCharset) {
		if ($fromCharset != "utf-8") {
			$entry = mb_convert_encoding($entry , "utf-8", $fromCharset);
		}
		return $entry;
	}

  /**
   * @param string $file A file path
   * @return The last part of a path
   */
	public static function pathinfo_basename($file) {
		//return array_pop(explode("/", $file)); DEPRECATED
		$tmp = explode("/", $file);
		return array_pop($tmp);
	}
}

class StreamLib {
  /**
   * @param string $file Filename with full path
   */
  function stream_mp3($file) {
     $this->stream_file($file, "audio/mpeg");
  }
  function stream_jpg($file) {
     $this->stream_file($file, "image/jpeg", false);
  }

  /**
   * Streams a file, mime type is autodetected (Supported: mp3, jpg)
   *
   * @param string $file Filename with full path
   */
  function stream_file_auto($file) {
     $suffix = strtolower(pathinfo($file, PATHINFO_EXTENSION));
     switch ($suffix) {
        case "mp3":
          $this->stream_mp3($file);
          break;
        case "jpg":
          $this->stream_jpg($file);
          break;
     }
  }

  /**
   * @param string $file Filename with full path
   * @param string $mimetype Mime type
   * @param boolean $isAttachment Add "Content-Disposition: attachment" header (optional, defaults to true)
   */
  function stream_file($file, $mimetype, $isAttachment = true) {
     //$filename = array_pop(explode("/", $file)); //DEPRECATED
	 $tmp = explode("/", $file);
     $filename = array_pop($tmp);
     header("Content-Type: $mimetype");
     header("Content-Length: " . filesize($file));
     if ($isAttachment) header("Content-Disposition: attachment; filename=\"$filename\"", true);

     $this->readfile_chunked($file);
     exit(0);
  }
  
  /**
   * @see http://no.php.net/manual/en/function.readfile.php#54295
   */
  function readfile_chunked($filename, $retbytes = true) {
    $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
    $buffer = "";
    $cnt = 0;
    
    $handle = fopen($filename, "rb");
    if ($handle === false) {
      return false;
    }
    while (!feof($handle)) {
      $buffer = fread($handle, $chunksize);
      echo $buffer;
      @ob_flush();
      flush();
      if ($retbytes) {
        $cnt += strlen($buffer);
      }
    }
    $status = fclose($handle);
    if ($retbytes && $status) {
      return $cnt; // return num. bytes delivered like readfile() does.
    }
    return $status;
  }
}

?>