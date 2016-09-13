var currentFolder = '';
var currentHash = '###';
var prefix = 'index.php?path=';
var boxTimeout;

var opts = { 
  lines: 11, // The number of lines to draw: 13
  length: 10, // The length of each line: 20
  width: 7, // The line thickness: 10
  radius: 15, // The radius of the inner circle: 30
  corners: 1, // Corner roundness (0..1): 1
  trail: 60, // Afterglow percentage: 60

  rotate: 0, // The rotation offset
  direction: 1, // 1: clockwise, -1: counterclockwise
  color: '#FFF', // #rgb or #rrggbb or array of colors
  speed: 1, // Rounds per second
  shadow: false, // Whether to render a shadow
  hwaccel: false, // Whether to use hardware acceleration
  className: 'spinner', // The CSS class to assign to the spinner
  zIndex: 2e9, // The z-index (defaults to 2000000000)
  top: '50%', // Top position relative to parent
  left: '50%' // Left position relative to parent
};

window.onload = function() {
  pollHash();
  setInterval(pollHash, 1000);
  document.getElementById('myplayer').style.visibility = "hidden";

  alertify.set({ labels: {
    ok     : "Ok",
    cancel : "Cancel"
	} });
  alertify.set({ delay: 3000 });
}

/**
 * Utils
 */
/* On/Off Events */
function handleTouch(e) {
	e.preventDefault();
}
 function disableClick(id) {
	document.getElementById(id).addEventListener('click', handleTouch, false); 
}
function enableClick(id) {
    document.getElementById(id).removeEventListener('click', handleTouch); 
}

/**
 * Add song to the bar-ui playlist
 */
function addCancion(link, item) {
	document.getElementById('myplayer').style.visibility = "visible";
	
	var liIdNum = (document.getElementById('theValue').value -1)+ 2;
	document.getElementById('theValue').value = liIdNum;
	
	var node = document.createElement("LI");                
	var item = item.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '');
	
	node.setAttribute('id',liIdNum);
	node.innerHTML = "<a class=deletesong onclick='removeCancion("+liIdNum+",\""+item+"\")' ><img class=deleteicon src=images/delete.png></a>"
				   + "<a href="+link+" class=exclude>"+item+"</a>";
	
	document.getElementById('userPlaylist').appendChild(node);

	alertify.success("<b>\""+item+"\"</b></br>added to the playlist");
	
	// Updating playlist drawer (open/close-close/open)
	window.sm2BarPlayers[0].actions.menu();
	window.sm2BarPlayers[0].actions.menu();
}

/**
 * Delete song from the bar-ui playlist
 */
function removeCancion(liIdNum, item) {
	var node = document.getElementById(liIdNum);
	
	if (node.parentNode) {
		node.parentNode.removeChild(node);

		alertify.error("<b>\""+item+"\"</b></br>removed from the playlist");

		// Expose to global in bar-ui.js:
		// Ej. action: window.sm2BarPlayers[0].actions.pause()
		// Ej. data: window.sm2BarPlayers[0].playlistController.data.selectedIndex
		if (liIdNum <= window.sm2BarPlayers[0].playlistController.data.selectedIndex) { // Reasigned index 
			window.sm2BarPlayers[0].playlistController.data.selectedIndex--;
		}
	}
	
	// Check any element in playlist and renumber the dom list 
	var node = document.getElementById('userPlaylist');
	var list = node.getElementsByTagName("li");
	
	if (list[0]==null) {
		document.getElementById('player_container').style.height = 50 + 'px'; 
		document.getElementById('myplayer').style.visibility = "hidden";
	} else {  // Split and Join the innerHTML string 
			i=0;  
			do {
				liIdNum = i+1;
				list[i].setAttribute('id',liIdNum);
				
				var enlace1 = list[i].innerHTML.split('(');
				var preenlace = "";
				for (var j = 1; j < enlace1.length; j++)
					{ preenlace = preenlace+"("+enlace1[j];	}
				
				var enlace2 = preenlace.split(','); 
				var restoenlace = "";
				for (var j = 1; j < enlace2.length; j++)
					{ restoenlace = restoenlace+","+enlace2[j];	}
				
				list[i].innerHTML = enlace1[0]+"("+liIdNum+restoenlace;
				
				i++;
				} while (list[i]!=null);	
			
			document.getElementById('theValue').value = liIdNum;
			}
}

/**
 * Poll the url hash regularly for changes.
 */
function pollHash() {
  var locationHash = window.location.hash.replace(/^#/, '');
  if (locationHash == currentHash) {
    return; // Nothing's changed since last polled. 
  }
  // Firefox and Safari don't agree on hash encoding
  if (decodeURIComponent(currentHash) == locationHash) {
    return;
  }
  currentHash = locationHash;
  switch (currentHash.substr(0, 2)) {
      case 'p=':
        updateDirectory(currentHash.replace(/^p=/, ''));
        break;
      case 's=':
        search(currentHash.replace(/^s=/, ''));
        break;
      default:
        updateDirectory('');
  }
}

/**
 * Change directory.
 */
function changeDir(path) {
  updateDirectory(path);
  updateHash('p', path);
}

/**
 * Update content tag with specified content from specified path.
 */
function updateDirectory(path) {
  document.getElementById('itemtitle').style.visibility = "visible";
  document.getElementById('cover').innerHTML = "";
  document.getElementById('content').innerHTML = "<div class=informa>Reading data from server...</div><div id='spin'></div>";
  var spinner = new Spinner(opts).spin(); // Spinner/Loading js
  document.getElementById('spin').appendChild(spinner.el);
  currentFolder = path;
  fetchContent(path.replace('&', '%26'));
}

/**
 * HTTP GET content from path.
 */
function fetchContent(path) {  
  var http = httpGet(prefix + path + "&content");
  http.onreadystatechange = function() {
    if (http.readyState == 4) {
      var result = jsonEval(http.responseText);
      if (!result) {
        document.getElementById('content').innerHTML = "<div class=error>Error.</div>";
      } else {
        if (result.error != '') {
		  alertify.alert('<div class=error><b>' + result.error + '</b></div>');
        }
        document.title = result.title;
        document.getElementById('cover').innerHTML = result.cover;
		document.getElementById('itemtitle').innerHTML = result.itemtitle;
        document.getElementById('breadcrumb').innerHTML = result.breadcrumb;
        document.getElementById('content').innerHTML = result.content;
      }
    }
  }
  http.send(null);
}

/**
 * HTTP GET.
 * @return HTTP object
 */
function httpGet(fullPath) {
  var http = false;
  if (navigator.appName.indexOf('Microsoft') != -1) {
    http = new ActiveXObject("Microsoft.XMLHTTP");
  } else {
    http = new XMLHttpRequest();
  }
  http.open("GET", fullPath, true);
  return http;
}

/**
 * Update hash in url field.
 * @param func Function, either 's' (search) or 'p' (path)
 * @param content Value in hash
 */
function updateHash(func, content) {
  if (content) {
    var tempHash = func + '=' + content;
    // Firefox and Safari don't agree on hash encoding
    if (encodeURIComponent(tempHash) == currentHash) {
      return;
    }
    currentHash = tempHash;
    window.location.hash = '#' + currentHash;
  } else {
    currentHash = '';
    window.location.hash = '#';
  }
}

/**
 * @return keynum from keypress
 */
function getKeyNum(e) {
  var keynum;
  if (window.event) { // IE
    keynum = e.keyCode;
  } else if (e.which) { // Firefox/Opera
    keynum = e.which;
  }
  return keynum;
}

/**
 * Crude json evaluator.  Returns false if input isn't json.
 */
function jsonEval(text) {
  if (text.substr(0, 1) != '{') {
	alertify.alert('<div class=error>Could not parse content.<br>' + escapeHTML(text.substr(0,180)) + '...</div>');		  
    return false;
  } else {
    return eval("(" + text + ")");
  }
}

/**
 * Escape HTML string.
 */
function escapeHTML(str) {
   var div = document.createElement('div');
   var text = document.createTextNode(str);
   div.appendChild(text);
   return div.innerHTML;
}
