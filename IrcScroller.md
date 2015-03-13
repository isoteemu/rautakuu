# Irc Scroller #
Irc Scroller is an space-age product for joining two different worlds together; [Irc](http://en.wikipedia.org/wiki/Internet_Relay_Chat), messaging protocol from the time when god walked the earth, and [web 2.0](http://en.wikipedia.org/wiki/Web_2.0), ''the'' choice for dot com boomers.

Primary design use for Irc Scroller is to display irc-logs as real-time updating web pages. But it has been utilized for different purposes as well, eg. [wiki:HLstats displaying game-server ongoing status and events]. Great for communities. And good for your ego too!

## Features ##
  * Support for most common irc logs:
    * [Mirc](http://www.mirc.com/) and [compatibles](http://mel.sourceforge.net/).
    * [Eggdrop](http://www.eggheads.org/). Notice, that by default eggdrop flushes logs only on every 5 minutes. To get faster updates, add `set quick-logs 1` to eggdrop config. They're still flushed every minute or so, so using [mel](http://mel.sourceforge.net/) or some other logging mechanism is recommended.
    * [Irssi](http://www.irssi.org), the best irc client around.
    * [Pear DB](http://pear.php.net/DB) supported database. There is currently one known bot which produces log for native Irc Scroller DB format, and it's [MiniTeemu](http://svn.rautakuu.org/repos/homebrevcomputing/MiniTeemu/). And you don't wanna use it (seriously, it ''will'' make baby Jesus cry), so some degree of rocket science and/or voodoo magic is required. Alas, it is still only, heavily tested log format.
  * Javascript (Ajax, if you prefer fancy thing like electricity) powered, without graceful deprecation.  Uses more or less, more less sophisticated polling method to get instant updates.
    * Throttling. All thought it probably wastes more resources than saves, uses simple average calculation to delay updates fetch, if server is under a heavy load.
    * Smart (n+1) calculation in client side, when next update should be fetched.
    * [Mirc colors](http://www.mirc.net/newbie/colors.php).

## Demos, Documents and Links ##
  * '''Demos'''
    * [RSL webpages](http://rsl.sivut.rautakuu.org/index.php?menu=2). Uses MiniTeemus offered logging.
    * [RolleWeb](http://rolleweb.net/irc)
    * [Rautakuu frontpage](http://rautakuu.org/drupal/), where it has been operated over two years now.
  * IrcScrollerSetup -- Documentation for Irc Scroller setup
  * http://www.gnu.org/licenses/gpl.txt -- License text for Irc Scroller.