
function efendiContextMenu() {
  // If no text is selected, hide item.
  if(!gContextMenu.isTextSelected) {
    gContextMenu.showItem("efendi-menuitem-separator", false);
    gContextMenu.showItem("efendi-menuitem", false);
  } else {
    gContextMenu.showItem("efendi-menuitem-separator", true);
    gContextMenu.showItem("efendi-menuitem", true);
  }
}

function efendiHaku() {
  var baseurl='http://www.virtuaaliyliopisto.fi/?node=vy_sanakirja&efendi_word=';

  var focusedWindow = document.commandDispatcher.focusedWindow;

  var selectedText = focusedWindow.getSelection();

  var url = baseurl + escape(selectedText);

  openAndReuseOneTabPerURL(baseurl, url); 
}

/**
 * add event for showing/hiding context menu entry
 */
function initEfendiOverlay() {
  var menu = document.getElementById("contentAreaContextMenu");
  menu.addEventListener("popupshowing", efendiContextMenu, false);
}

/**
 * From mozilla code sniplets, with minor adjustments.
 */
function openAndReuseOneTabPerURL(baseurl, url) {
  var wm = Components.classes["@mozilla.org/appshell/window-mediator;1"]
                     .getService(Components.interfaces.nsIWindowMediator);
  var browserEnumerator = wm.getEnumerator("navigator:browser");

  // Check each browser instance for our URL
  var found = false;
  while (!found && browserEnumerator.hasMoreElements()) {
    var browserInstance = browserEnumerator.getNext().getBrowser();

    // Check each tab of this browser instance
    var numTabs = browserInstance.tabContainer.childNodes.length;

    for(var index=0; index<numTabs; index++) {
      var currentBrowser = browserInstance.getBrowserAtIndex(index);

      if (currentBrowser.currentURI.spec.substr(0, baseurl.length) == baseurl) {
        // The URL is already opened. Select this tab.
        browserInstance.selectedTab = browserInstance.tabContainer.childNodes[index];

        // Focus *this* browser
        browserInstance.focus();

        // If seeked uri is same, don't bother reloading page.
        if( currentBrowser.currentURI.spec != url ) {
          browserInstance.loadURI(url);
        }
        found = true;
        break;
      }
    }
  }

  // Our URL isn't open. Open it now.
  if (!found) {
    var recentWindow = wm.getMostRecentWindow("navigator:browser");
    if (recentWindow) {
      // Use an existing browser window
      recentWindow.delayedOpenTab(url, null, null, null, null);
    }
    else {
      // No browser windows are open, so open a new one.
      window.open(url);
    }
  }
}

window.addEventListener("load", initEfendiOverlay, false);
