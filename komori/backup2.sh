#!/bin/bash

##
## * PATHS
##

DIRS="/etc /srv/www /srv/ftp /var/lib/ldap /var/lib/imap/ /var/spool/imap"
DATABASES="drupal amavisd horde mysql phpmyadmin terraintra"
#DATABASES="test"
#DIRS="/home/god"

BACKUP_DIR="/var/backup"

##
## * Commands
##
## LinkStation doens't support owner and group
RSYNC="/usr/bin/rsync -rlptDH --delete"
CP="cp -dPRl --preserve=mode,timestamps,links"
MYSQLDUMP="mysqldump -uroot -p"

##
## * Functions
##

function backupError() {
	cat $LOGFILE | mail -s "varmuuskopiointi errori: `date`" root
}

## Backup directory
function backupDir() {
	DIR=${1}
	PARENTDIR=`dirname $DIR`

	echo -n "   - Checking backup dir ${LATESTDIR}/fs${DIR}"
	test -d "${LATESTDIR}/fs${DIR}" && (
		echo " [A-OK]"

		echo -n "   - Creating incremental dir ${OLDDIR}/fs${PARENTDIR}"
	
		test -d "${OLDDIR}/fs${DIR}" && (
			echo " ... Exists? [WTF?]"
		) || (
			mkdir -p "${OLDDIR}/fs${PARENTDIR}" && (
				echo " [A-OK]"
			) || (
				echo " [FAIL]"
				return 1
			)
		)

		echo -n "   - Copying previous backup"
		## No ownership preservation on LinkStation
		$CP "${LATESTDIR}/fs${DIR}" "${OLDDIR}/fs${PARENTDIR}" && echo " [A-OK]" || echo " [FAIL]"

	) || (
		echo " [FAIL]"
		echo -n "      - Creating"
		mkdir -p "${LATESTDIR}/fs${DIR}" && (
			echo " [A-OK]"
		) || (
			echo " [FAIL]"
			return 1
		)
	)

	## Real backup here
	echo " = PAUSE FOR PORN ="
	echo "About to exec:"
	echo $RSYNC "$DIR" "${LATESTDIR}/fs${PARENTDIR}"
	sleep 10
	echo -n "   - Running rsync backup"
	$RSYNC -v "$DIR" "${LATESTDIR}/fs${PARENTDIR}" && echo " [A-OK]" || echo " [FAIL]"
}

function mysqlBackup() {
	DB=${1}
	DBFILE="$DB.mysql.sql"

	echo -n "   - Checking old database backup $DB"
	test -f "${LATESTDIR}/${DBFILE}" && (
		echo " ... Exists [A-OK]"
		echo -n "      - Moving old database file to ${OLDDIR}"
		mv "${LATESTDIR}/${DBFILE}" "${OLDDIR}" && echo " [A-OK]" || echo " [FAIL]"
	) || (
		echo " [A-OK]"
	)

	## Create temporary dump file
	echo -n "   - Creating temporary file"
	TMPDBDUMP=`mktemp -p "${LATESTDIR}"`
	if [ $? -gt 1 ]; then
		echo " [FAIL]"
		TMPDBDUMP="${LATESTDIR}/${DBFILE}"
		echo -n "   - Trying direct dump to $TMPDBDUMP"
	else
		echo " [A-OK]"
		echo -n "   - Dumping database"
	fi

	$MYSQLDUMP "$DB" > $TMPDBDUMP && (
		echo " [A-OK]"
		if [ "${LATESTDIR}/${DBFILE}" != "$TMPDBDUMP" ]; then
			echo -n "   - Renaming temp dump to ${LATESTDIR}/${DBFILE}"
			mv "$TMPDBDUMP" "${LATESTDIR}/${DBFILE}" && echo " [A-OK]" || echo " [FAIL]"
		fi
	) || (
		echo " [FAIL]"
		return 1
	)
}

##
## * MAIN
##

## Log
LOGFILE=`mktemp`
echo -n "BACKUP LOG @ " >> $LOGFILE
date >> $LOGFILE
echo "" >> $LOGFILE

LATESTDIR="$BACKUP_DIR/latest"
OLDDIR="$BACKUP_DIR/`date +%FT%H-%M-%S`"

mount | grep -q "on $BACKUP_DIR" || (
	echo "No backup dir mounted. Won't continue"
	backupError
	exit 1
)

echo -n " - Checking backup dir" >> $LOGFILE
test -d "$LATESTDIR" && echo " [A-OK]"  >> $LOGFILE || (
	mkdir -p "$LATESTDIR" && echo " [A-OK]" || (echo " [FAIL]"; exit 1)
) >> $LOGFILE

echo -n " - Checking old backup" >> $LOGFILE
test -d "$OLDDIR" && echo " [A-OK]" >> $LOGFILE || (
	mkdir -p "$OLDDIR" && echo " [A-OK]" || echo " [FAIL]"
) >> $LOGFILE

## Backup dirs
echo "" >> $LOGFILE
echo "DIR BACKUP:" >> $LOGFILE
for DIR in $DIRS; do
	echo -n " * $DIR" >> $LOGFILE
	test -d "$DIR" && (
		echo " [A-OK]:" >> $LOGFILE
		backupDir $DIR >> $LOGFILE 2>&1
	) || (
		echo " [FAIL]" >> $LOGFILE
	)
done

## Backup databases
echo "" >> $LOGFILE
echo "DATABASE BACKUP:" >> $LOGFILE
for DB in $DATABASES; do
	mysqlBackup "$DB" >> $LOGFILE 2>&1
done

exit 0
if [ `grep "\\[FAIL\\]" $LOGFILE` ]; then
	# Errors? Send log
	backupError
	exit 1
fi

cat $LOGFILE
rm $LOGFILE
exit 0
