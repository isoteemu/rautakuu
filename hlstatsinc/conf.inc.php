<?php

if(!defined("_HLSTATS")) die("Direct access denied.");

///
/// Database Settings
///

// DB_NAME - The name of the database
define("DB_NAME", "hlds");

// DB_USER - The username to connect to the database as
define("DB_USER", "hlds");

// DB_PASS - The password for DB_USER
define("DB_PASS", "hlds");

// DB_ADDR - The address of the database server, in host:port format.
//           (You might also try setting this to e.g. ":/tmp/mysql.sock" to
//           use a Unix domain socket, if your mysqld is on the same box as
//           your web server.)
define("DB_ADDR", "localhost");

// DB_TYPE - The database server type. Only "mysql" is supported currently
define("DB_TYPE", "mysql");

// DB_PCONNECT - Set to 1 to use persistent database connections. Persistent
//               connections can give better performance, but may overload
//               the database server. Set to 0 to use non-persistent
//               connections.
define("DB_PCONNECT", 0);


///
/// General Settings
///

// DELETEDAYS - How many days the Event History covers. Must match the value
//              of DeleteDays in hlstats.conf.
define("DELETEDAYS", 21);

define("MINACTIVITY", 86400 * DELETEDAYS);

// MODE - Sets the player-tracking mode. Must match the value of Mode in
//        hlstats.conf. Possible values:
//           1) "Normal"    - Recommended for public Internet server use.
//                            Players will be tracked by Unique ID.
//           2) "NameTrack" - Useful for shared-PC environments, such as
//                            Internet cafes, etc. Players will be tracked
//                            by nickname. EXPERIMENTAL!
//           3) "LAN"       - Useful for LAN servers where players do not
//                            have a real Unique ID. Players will be tracked
//                            by IP Address. EXPERIMENTAL!
define("MODE", "Normal");

// PLATFORM - Sets the operating system being used. Recognised values:
//               "POSIX"    - Any variant of Linux or Unix.
//               "Windows"  - Any variant of Microsoft Windows.
//            Most parts of HLstats should work on any platform, but this
//            setting allows for some OS-specific workarounds etc.
define("PLATFORM", "POSIX"); 

define("GEOIPDAT", dirname(__FILE__)."/../GeoIP.dat");

define(CACHE_STORAGE_CLASS, 'mdb');

?>