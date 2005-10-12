#!/usr/bin/perl
#
# HLstats Resolve - Resolve player IP addresses to hostnames
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
use IO::Socket;
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
$opt_regroup = 0;

$db_host = "localhost";
$db_user = "";
$db_pass = "";
$db_name = "hlstats";

$g_dns_timeout = 5;
$g_debug = 0;

# Usage message

$usage = <<EOT
Usage: hlstats-resolve.pl [OPTION]...
Resolve player IP addresses to hostnames.

  -h, --help                      display this help and exit
  -v, --version                   output version information and exit
  -d, --debug                     enable debugging output (-dd for more)
  -n, --nodebug                   disables above; reduces debug level
      --db-host=HOST              database ip:port
      --db-name=DATABASE          database name
      --db-password=PASSWORD      database password (WARNING: specifying the
                                    password on the command line is insecure.
                                    Use the configuration file instead.)
      --db-username=USERNAME      database username
      --dns-timeout=SEC           timeout DNS queries after SEC seconds  [$g_dns_timeout]
  -r, --regroup                   only re-group hostnames--don't resolve any IPs

Long options can be abbreviated, where such abbreviation is not ambiguous.

Most options can be specified in the configuration file:
  $opt_configfile
Note: Options set on the command line take precedence over options set in the
configuration file.

HLstats: http://www.hlstats.org
EOT
;

# Read Config File

if ($opt_configfile && -r $opt_configfile)
{
	$conf = ConfigReaderSimple->new($opt_configfile);
	$conf->parse();
	
	%directives = (
		"DBHost",			"db_host",
		"DBUsername",		"db_user",
		"DBPassword",		"db_pass",
		"DBName",			"db_name",
		"DNSTimeout",		"g_dns_timeout",
		"DebugLevel",		"g_debug"
	);
	
	&doConf($conf, %directives);
}
else
{
	print "-- Warning: unable to open configuration file '$opt_configfile'\n";
}

# Read Command Line Arguments

GetOptions(
	"help|h"			=> \$opt_help,
	"version|v"			=> \$opt_version,
	"debug|d+"			=> \$g_debug,
	"nodebug|n+"		=> \$g_nodebug,
	"db-host=s"			=> \$db_host,
	"db-name=s"			=> \$db_name,
	"db-password=s"		=> \$db_pass,
	"db-username=s"		=> \$db_user,
	"dns-timeout=i"		=> \$g_dns_timeout,
	"regroup|r"			=> \$opt_regroup
) or die($usage);

if ($opt_help)
{
	print $usage;
	exit(0);
}

if ($opt_version)
{
	print "hlstats-resolve.pl (HLstats) $g_version\n"
		. "Real-time player and clan rankings and statistics for Half-Life\n\n"
		. "Copyright (C) 2001  Simon Garner\n"
		. "This is free software; see the source for copying conditions.  There is NO\n"
		. "warranty; not even for MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.\n";
	exit(0);
}

$g_debug -= $g_nodebug;
$g_debug = 0 if ($g_debug < 0);

if ($g_debug >= 2)
{
	$opt_quiet = 0;
}
else
{
	$opt_quiet = 1;	# quiet name resolution
}

$g_dns_resolveip = 1;


# Startup

print "++ HLstats Resolve $g_version starting...\n\n";

# Connect to the database

print "-- Connecting to MySQL database '$db_name' on '$db_host' as user '$db_user' ... ";

$db_conn = DBI->connect(
	"DBI:mysql:$db_name:$db_host",
	$db_user, $db_pass
) or die ("Can't connect to MySQL database '$db_name' on '$db_host'\n" .
	"$DBI::errstr\n");

print "connected OK\n";

# Print configuration

print "-- DNS timeout is $g_dns_timeout seconds. Debug level is $g_debug.\n";


# Main data routine

if ($opt_regroup)
{
	my $result = &doQuery("
		SELECT
			id,
			hostname
		FROM
			hlstats_Events_Connects
		WHERE
			hostname != ''
	");
	
	my $total = $result->rows;
	print "\n++ Re-grouping hosts (total $total hostnames) ... ";
	
	my $resultHG = &queryHostGroups();
	
	if ($g_debug > 0)
	{
		print "\n\n";
	}
	else
	{
		print "    ";
	}
	
	my $p = 1;
	while( my($id, $hostname) = $result->fetchrow_array )
	{
		my $percent = ($p / $total) * 100;
		
		my $hostgroup = &getHostGroup($hostname, $resultHG);
		
		&doQuery("
			UPDATE
				hlstats_Events_Connects
			SET
				hostgroup='" . &quoteSQL($hostgroup) . "'
			WHERE
				id='$id'
		");
	
		if ($g_debug > 0)
		{
			printf("-> (%3d%%) %50s  =  %s\n", $percent, $hostname, $hostgroup);
		}
		else
		{
			printf("\b\b\b\b%3d%%", $percent);
		}
		
		$p++;
	}
	
	print "\n" unless ($g_debug > 0);
}
else
{
	my $result = &doQuery("
		SELECT
			DISTINCT ipAddress,
			hostname
		FROM
			hlstats_Events_Connects
	");
	
	my $total = $result->rows;
	print "\n++ Resolving IPs and re-grouping hosts (total $total connects) ... ";
	
	my $resultHG = &queryHostGroups();
	
	if ($g_debug > 0)
	{
		print "\n\n";
	}
	else
	{
		print "    ";
	}
	
	my $p = 1;
	while( my($ipAddress, $hostname) = $result->fetchrow_array )
	{
		my $percent = ($p / $total) * 100;
		
		if ($hostname eq "")
		{
			$hostname = &resolveIp($ipAddress, $opt_quiet);
		}
		
		my $hostgroup = &getHostGroup($hostname, $resultHG);
		
		&doQuery("
			UPDATE
				hlstats_Events_Connects
			SET
				hostname='$hostname',
				hostgroup='" . &quoteSQL($hostgroup) . "'
			WHERE
				ipAddress='$ipAddress'
		");
		
		if ($g_debug > 0)
		{
			printf("-> (%3d%%) %15s  =  %50s  =  %s\n", $percent, $ipAddress, $hostname, $hostgroup);
		}
		else
		{
			printf("\b\b\b\b%3d%%", $percent);
		}
		
		$p++;
	}
	
	print "\n" unless ($g_debug > 0);
}


print "\n++ Operation complete.\n";
exit(0);
