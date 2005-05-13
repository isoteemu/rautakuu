/*
 *
 *  This program is free software; you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License as published by the
 *  Free Software Foundation; either version 2 of the License, or (at
 *  your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful, but
 *  WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 *  General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software Foundation,
 *  Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 *  In addition, as a special exception, the author gives permission to
 *  link the code of this program with the Half-Life Game Engine ("HL
 *  Engine") and Modified Game Libraries ("MODs") developed by Valve,
 *  L.L.C ("Valve"). You must obey the GNU General Public License in all
 *  respects for all of the code used other than the HL Engine and MODs
 *  from Valve. If you modify this file, you may extend this exception
 *  to your version of the file, but you are not obligated to do so. If
 *  you do not wish to do so, delete this exception statement from your
 *  version.
 */

#include <amxmodx>
#include <amxmisc>
#include <dbi>

new Author[] = "Rautakuu [dot] org"
new Plugin[] = "RQ_redirPlayers"
new Version[] = "0.1.1"

public plugin_init() {
    register_plugin(Plugin, Version, Author)
    register_cvar("rq_redirPlayers_version", Version, FCVAR_SERVER|FCVAR_SPONLY)
    server_cmd("localinfo rq_redirPlayers_version %s", Version)

    register_srvcmd("amx_redirplayers","redirPlayers")
}

public redirPlayers() {
    new Sql:sql
    new host[64],user[32],pass[32],db[32],error[127]

    get_cvar_string("amx_sql_host",host,63)
    get_cvar_string("amx_sql_user",user,31)
    get_cvar_string("amx_sql_pass",pass,31)
    get_cvar_string("amx_sql_db",db,31)

    sql = dbi_connect(host,user,pass,db,error,127)
    if (sql <= SQL_FAILED) {
        log_amx("Ei tietokantayhteytta. Ongelmia tiedossa: %s",error)
    }

    new myIP[16]
    get_cvar_string("ip",myIP,15)

    new Result:Res = dbi_query(sql,"SELECT `name`, `publicaddress` AS `addr`, `port` FROM `hlstats_Servers` WHERE `game` = 'cstrike' AND `publicaddress` != '%s' ORDER BY RAND() LIMIT 0, 1", myIP)

    if (Res == RESULT_FAILED) {
        dbi_error(sql,error,127)
        log_amx("Virhe haettaessa servereita: %s",error)
        dbi_free_result(Res)
        dbi_close(sql)

        return PLUGIN_HANDLED
    }
    else if (Res == RESULT_NONE) {
        log_amx("Ei muita servereita? Vahan turhaa sitten minua kayttaa.")

        dbi_free_result(Res)
        dbi_close(sql)

        return PLUGIN_HANDLED
    }

    new redirName[32], redirSrv[32], redirPort[6]
    while( dbi_nextrow(Res) > 0 ) {
        dbi_result(Res, "name", redirName, 31)
        dbi_result(Res, "addr", redirSrv, 31)
        dbi_result(Res, "port", redirPort, 6)

        dbi_free_result(Res)
        dbi_close(sql)
    }

    log_amx("ohjataan serverille %s:%s",redirSrv,redirPort)

    new Players[32]
    new playerCount, i

    get_players(Players, playerCount)

    for (i=0; i<playerCount; i++) {
        if ( !is_user_connected(Players[i]) && !is_user_connecting(Players[i]) ) {
            continue
        }

        client_print(Players[i],print_console,"======================================================")
        client_print(Players[i],print_console," Sinut ohjataan toiselle serverille:")
        client_print(Players[i],print_console," > %s",redirName)
        client_print(Players[i],print_console," > %s:%s",redirSrv,redirPort)
        client_print(Players[i],print_console,"======================================================")

        client_cmd(Players[i],"echo;disconnect; connect %s:%s",redirSrv,redirPort)
    }
    return PLUGIN_HANDLED
}
