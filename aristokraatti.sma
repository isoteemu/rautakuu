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

new Sql:sql
new error[128]

new aristokraatit[32];

public plugin_init() {
    register_plugin("Aristokraatti","0.1","Rautakuu [dot] org")
    register_cvar("amx_reservation","1")

    new host[64],user[32],pass[32],db[32]

    get_cvar_string("amx_sql_host",host,63)
    get_cvar_string("amx_sql_user",user,31)
    get_cvar_string("amx_sql_pass",pass,31)
    get_cvar_string("amx_sql_db",db,31)

    sql = dbi_connect(host,user,pass,db,error,127)
    if (sql <= SQL_FAILED) {
        log_amx("Ei tietokantayhteytta. Ongelmia tiedossa: %s",error)
    }
}

public plugin_end() {
    dbi_close(sql)
}

public client_authorized(id) {

    new status[32]
    new isAristokraatti = isKnownPlayer(id, status)

    // Lisätään reserver flagi (b) jos aristokraatti
    if ( isAristokraatti == 1 ) {
        aristokraatit[id] = 1;
    }

    new maxplayers = get_maxplayers()
    new players = get_playersnum( 1 )
    new limit = maxplayers - get_cvar_num("amx_reservation")

    if ( players <= limit ) {
        new bool:allowIn = true
        if ( limit == players ) {
            allowIn = false
            if ( isAristokraatti == 1 ) {
                // Etsitään monotettava
                new monotettu = monotaPingein()

                if ( monotettu == 1 ) {
                    allowIn = true
                } else {
                    client_print(id,print_console,"Ei ketaan monotettavaa :(")
                }
            }
        }

        if (allowIn == true) {
            new pname[32]
            get_user_name(id, pname, 31)
            client_print(0,print_chat,"%s %s yhdistyy serverille", status, pname)
        }
        else {
            redirectPlayer(id)
        }
    }
    else {
        log_amx("Ei vapaita paikkoja serverillä")
    }
    return PLUGIN_HANDLED
}

public client_disconnect(id)
{
    if( aristokraatit[id] == 1 ) {
        log_amx("Poistetaan aristokraatti merkinta idx:%s", id);
        aristokraatit[id] = 0
    }
    return PLUGIN_CONTINUE
}


public isKnownPlayer(id, status[32]) {

    copy(status,8,"Pelaaja")

    if (sql <= SQL_FAILED) {
        log_amx("Ei tietokantayhteytta. Ei voida tarkistaa aatelismia: %s", error)
        return 0
    }

    // Haetaan pelaajan STEAMID/WONID
    new userauthid[32]
    get_user_authid(id,userauthid,31)

    // On perkele köyhän miehen hax hax hax.
    // Liian tiukka lauseiden pituudesta niin täytyy erillisillä hauilla tehä

    new Result:Res = dbi_query(sql,"SELECT 'Aristokraatti' AS status FROM hlstats_PlayerUniqueIds INNER JOIN aristokraatit ON aristokraatit.uniqueId=hlstats_PlayerUniqueIds.uniqueId LEFT JOIN hlstats_Players ON  hlstats_Players.playerId=hlstats_PlayerUniqueIds.playerId  WHERE aristokraatit.uniqueId='%s'",userauthid)

    if (Res == RESULT_FAILED) {
        dbi_error(sql,error,127)
        log_amx("Ei voitu ladata aristokraatteja: %s",error)
        dbi_free_result(Res)
    }
    else if (Res == RESULT_NONE) {
        dbi_free_result(Res)
    }
    else {
        while( dbi_nextrow(Res) > 0 ) {
            // Pelaaja on aristokraatti.
            dbi_result(Res, "status", status, 31)
            dbi_free_result(Res)
            log_amx("Pelaaja idx:%s sai statuksen %s", id, status);
            return 1
        }
    }

    new Result:Res2 = dbi_query(sql,"SELECT 'Statuskraatti' AS status FROM hlstats_PlayerUniqueIds LEFT JOIN hlstats_Players ON hlstats_Players.playerId=hlstats_PlayerUniqueIds.playerId  WHERE `skill`>=( SELECT skill FROM hlstats_Players ORDER BY skill DESC LIMIT 3,1) AND `hideranking`=0 AND `uniqueId`='%s'",userauthid)

    if (Res2 == RESULT_FAILED) {
        dbi_error(sql,error,127)
        log_amx("Ei voitu ladata aristokraatteja: %s",error)
        dbi_free_result(Res2)
    }
    else if (Res2 == RESULT_NONE) {
        dbi_free_result(Res2)
    }
    else {
        while( dbi_nextrow(Res) > 0 ) {
            // Pelaaja on aristokraatti.
            dbi_result(Res2, "status", status, 31)
            dbi_free_result(Res2)
            log_amx("Pelaaja idx:%s sai statuksen %s", id, status);
            return 1
        }
    }

    return 0
}

public monotaPingein ( ) {
    new Players[32]
    new playerCount, i
    new bigPing = 0
    new myPing, myLoss, bigPingOwner
    new bool:toKick = false

    get_players(Players, playerCount)

    for (i=0; i<playerCount; i++) {
        // Hypätään aristokraattien yli tietty
        if (access(Players[i],ADMIN_RESERVATION)) {
            continue
        }
        else if ( aristokraatit[i] == 1 ) {
            log_amx("Loytyi aristokraatti idx:%s")
            continue
        }
        else {

            get_user_ping(Players[i], myPing, myLoss)
            if ( myPing > bigPing ) {
                toKick = true
                bigPingOwner = Players[i]
            }
        }
    }

    // Onko ketään potkittavaa?
    if ( bigPingOwner && toKick == true ) {
        redirectPlayer(bigPingOwner)
        return 1
    }
    else {
        log_amx("Ei ketaan joka voitaisiin poistaa.")
    }
    return 0
}

public redirectPlayer(id) {
    new myIP
    myIP = get_cvar_num("ip")

    new Result:Res = dbi_query(sql,"SELECT `name`, `publicaddress`, `port` FROM `hlstats_Servers` WHERE `game` = 'cstrike' AND `publicaddress` != '%s' ORDER BY RAND() LIMIT 0, 1", myIP)

    if (Res == RESULT_FAILED) {
        dbi_error(sql,error,127)
        log_amx("Virhe haettaessa serverita: %s",error)
        dbi_free_result(Res)
        dbi_close(sql)

        new id_str[3]
        num_to_str(id, id_str, 3)
        kickPlayer(id_str)

        return
    }
    else if (Res == RESULT_NONE) {
        log_amx("Ei muita serverita? Vahan turhaa sitten minua kayttaa.")
        // Ei serveritä? Monota sitten
        dbi_free_result(Res)
        dbi_close(sql)

        new id_str[3]
        num_to_str(id, id_str, 3)
        kickPlayer(id_str)

        return
    }

    while( dbi_nextrow(Res) > 0 ) {
        new redirName[32], redirAddr[32], redirPort[5]
        dbi_result(Res, "name", redirName, 31)
        dbi_result(Res, "publicaddress", redirAddr, 31)
        dbi_result(Res, "port", redirPort, 31)

        dbi_free_result(Res)
        dbi_close(sql)

        client_print(id,print_console,"======================================================")
        client_print(id,print_console," %s",redirName)
        client_print(id,print_console," Kyseinen serveri on pelaajalimitissa")
        client_print(id,print_console," Sinut ohjataan toiselle serverille: %s:%s",redirAddr,redirPort)
        client_print(id,print_console,"======================================================")

        client_cmd(id,"echo;disconnect; connect %s:%s",redirAddr,redirPort)
        break
    }

    // Varmistetaan viela etta häipyy
    new id_str[3]
    num_to_str(id, id_str, 3)
    set_task(2.0, "kickPlayer",0,id_str)
}

public kickPlayer(id_str[3]) {
    new player_id
    str_to_num(id_str,player_id,3)
    new userid = get_user_userid(player_id)
    server_cmd("kick #%d Serveri ei vastaanota pelaajia enempaa",userid)
    return PLUGIN_CONTINUE
}

