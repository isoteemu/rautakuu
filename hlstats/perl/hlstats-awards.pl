#!/usr/bin/perl
#
# HLstats Awards - Run daily from crontab to generate awards
# http://sourceforge.net/projects/hlstats/
#
# Copyright (C) 2001  Simon Garner
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#


##
## Settings
##

# $opt_configfile - Absolute path and filename of configuration file.
$opt_configfile = "./hlstats.conf";

# $opt_libdir - Directory to look in for local required files
#               (our *.plib, *.pm files).
$opt_libdir = "./";


##
##
################################################################################
## No need to edit below this line
##


use Getopt::Long;
use DBI;

require "$opt_libdir/ConfigReaderSimple.pm";
do "$opt_libdir/HLstats.plib";

$|=1;
Getopt::Long::Configure ("bundling");



##
## MAIN
##

# Options

$opt_help = 0;
$opt_version = 0;
$opt_numdays = 1;

$db_host = "localhost";
$db_user = "";
$db_pass = "";
$db_name = "hlstats";

# Usage message

$usage = <<EOT
Usage: hlstats-awards.pl [OPTION]...
Generate awards from Half-Life server statistics.

  -h, --help                      display this help and exit
  -v, --version                   output version information and exit
      --numdays                   number of days in period for awards
      --db-host=HOST              database ip:port
      --db-name=DATABASE          database name
      --db-password=PASSWORD      database password (WARNING: specifying the
                                    password on the command line is insecure.
                                    Use the configuration file instead.)
      --db-username=USERNAME      database username

Long options can be abbreviated, where such abbreviation is not ambiguous.

Most options can be specified in the configuration file:
  $opt_configfile
Note: Options set on the command line take precedence over options set in the
configuration file.

HLstats: http://www.hlstats.org
EOT
;

# Read Config File

if (-r $opt_configfile)
{
	$conf = ConfigReaderSimple->new($opt_configfile);
	$conf->parse();
	
	%directives = (
		"DBHost",			"db_host",
		"DBUsername",		"db_user",
		"DBPassword",		"db_pass",
		"DBName",			"db_name",
	);
	
	&doConf($conf, %directives);
}
else
{
	print "-- Warning: unable to open configuration file $opt_configfile\n";
}

# Read Command Line Arguments

GetOptions(
	"help|h"			=> \$opt_help,
	"version|v"			=> \$opt_version,
	"numdays=i"			=> \$opt_numdays,
	"db-host=s"			=> \$db_host,
	"db-name=s"			=> \$db_name,
	"db-password=s"		=> \$db_pass,
	"db-username=s"		=> \$db_user
) or die($usage);

if ($opt_help)
{
	print $usage;
	exit(0);
}

if ($opt_version)
{
	print "hlstats-awards.pl (HLstats) $g_version\n"
		. "Real-time player and clan rankings and statistics for Half-Life\n\n"
		. "Copyright (C) 2001  Simon Garner\n"
		. "This is free software; see the source for copying conditions.  There is NO\n"
		. "warranty; not even for MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.\n";
	exit(0);
}


# Startup

print "++ HLstats Awards $g_version starting...\n\n";

# Connect to the database

print "-- Connecting to MySQL database '$db_name' on '$db_host' as user '$db_user' ... ";

$db_conn = DBI->connect(
	"DBI:mysql:$db_name:$db_host",
	$db_user, $db_pass
) or die ("Can't connect to MySQL database '$db_name' on '$db_host'\n" .
	"$DBI::errstr\n");

print "connected OK\n";


# Main data routine

$resultAwards = &doQuery("
	SELECT
		hlstats_Awards.awardId,
		hlstats_Awards.game,
		hlstats_Awards.awardType,
		hlstats_Awards.code
	FROM
		hlstats_Awards
	LEFT JOIN hlstats_Games ON
		hlstats_Games.code = hlstats_Awards.game
	WHERE
		hlstats_Games.hidden='0'
	ORDER BY
		hlstats_Awards.game,
		hlstats_Awards.awardType
");

$result = &doQuery("
	SELECT
		value,
		DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)
	FROM
		hlstats_Options
	WHERE
		keyname='awards_d_date'
");

if ($result->rows > 0)
{
	($awards_d_date, $awards_d_date_new) = $result->fetchrow_array;
	
	&doQuery("
		UPDATE
			hlstats_Options
		SET
			value='$awards_d_date_new'
		WHERE
			keyname='awards_d_date'
	");
	
	print "\n++ Generating awards for $awards_d_date_new (previous: $awards_d_date)...\n\n";
}
else
{
	&doQuery("
		INSERT INTO
			hlstats_Options
			(
				keyname,
				value
			)
		VALUES
		(
			'awards_d_date',
			DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)
		)
	");
}

&doQuery("
	REPLACE INTO
		hlstats_Options
		(
			keyname,
			value
		)
	VALUES
	(
		'awards_numdays',
		$opt_numdays
	)
");


while( ($awardId, $game, $awardType, $code) = $resultAwards->fetchrow_array )
{
	print "$game ($awardType) $code";

	if ($awardType eq "O")
	{
		$table = "hlstats_Events_PlayerActions";
		$join  = "LEFT JOIN hlstats_Actions ON hlstats_Actions.id = $table.actionId";
		$matchfield = "hlstats_Actions.code";
		$playerfield = "$table.playerId";
	}
	elsif ($awardType eq "W")
	{
		$table = "hlstats_Events_Frags";
		$join  = "";
		$matchfield = "$table.weapon";
		$playerfield = "$table.killerId";
	}
	
	$result = &doQuery("
		SELECT
			$playerfield,
			COUNT($matchfield) AS awardcount
		FROM
			$table
		LEFT JOIN hlstats_Players ON
			hlstats_Players.playerId = $playerfield
		$join
		WHERE
			$table.eventTime < CURRENT_DATE()
			AND $table.eventTime > DATE_SUB(CURRENT_DATE(), INTERVAL $opt_numdays DAY)
			AND hlstats_Players.game='$game'
			AND hlstats_Players.hideranking='0'
			AND $matchfield='$code'
		GROUP BY
			$playerfield
		ORDER BY
			awardcount DESC,
			hlstats_Players.skill DESC
		LIMIT 1
	");
	
	($d_winner_id, $d_winner_count) = $result->fetchrow_array;
	
	if (!$d_winner_id || $d_winner_count < 1)
	{
		$d_winner_id = "NULL";
		$d_winner_count = "NULL";
	}
	
	print "  - $d_winner_id ($d_winner_count)\n";
	
	&doQuery("
		UPDATE
			hlstats_Awards
		SET
			d_winner_id=$d_winner_id,
			d_winner_count=$d_winner_count
		WHERE
			awardId=$awardId
	");
}

print "\n++ Awards generated successfully.\n";
exit(0);
