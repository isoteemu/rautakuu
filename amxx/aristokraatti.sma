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

// If more than 1 reserved slot, show only one extraslot.
// Less whine from players "why can't i connect"
#define HIDE_EXRTARESERVEDSLOTS

// Debuggin
#define NOISY

// Use Cheating-Death
#define CHEATIN_DEATH

new Sql:dbc
new dbcError[128]

new aristokraatit[32] = 0
new statukset[4][] = {"Alokas", "Kersantti", "Luuntnantti", "Kenraali"}

// Lista vipin oikista
new vipAdmins[] = "j"

new Author[] = "Rautakuu [dot] org"
new Plugin[] = "RQ_Aristokraatti"
new Version[] = "0.5.3"

public plugin_init() {
    register_plugin(Plugin, Version, Author)
    register_cvar("rq_aristokraatti_version", Version, FCVAR_SERVER|FCVAR_SPONLY) // For GameSpy/HLSW and such
    server_cmd("localinfo rq_aristokraatti_version %s", Version) // For Statsme/AMX Welcome

    register_cvar("amx_reservation","1")
    register_cvar("amx_rq_redircount","3")

    register_cvar("amx_sql_host","127.0.0.1")
    register_cvar("amx_sql_user","hlds")
    register_cvar("amx_sql_pass","hlds")
    register_cvar("amx_sql_db","hlds")

    new configsDir[64]
    get_configsdir(configsDir, 63)
    server_cmd("exec %s/sql.cfg", configsDir)

    #if defined HIDE_EXRTARESERVEDSLOTS
        if (get_cvar_num("amx_reservation") >= 2) {
            set_cvar_num("sv_visiblemaxplayers", get_maxplayers() - get_cvar_num("amx_reservation")+1 )
        }
    #endif

    // Hieman aikaa ett�asetukset etc ehdit�n lukea
    set_task(0.1,"sqlInit")
}

public plugin_modules() {
   require_module("DBI")
}

public sqlInit() {

    new host[64],user[32],pass[32],db[32],dbType[7]

    get_cvar_string("amx_sql_host",host,63)
    get_cvar_string("amx_sql_user",user,31)
    get_cvar_string("amx_sql_pass",pass,31)
    get_cvar_string("amx_sql_db",db,31)

    dbi_type(dbType, 6)
    dbc = dbi_connect(host,user,pass,db,dbcError,127)
    if (dbc <= SQL_FAILED) {
        log_amx("Ei tietokantayhteytta. Ongelmia tiedossa: %s",dbcError)
        #if defined NOISY
            log_amx("host,user,pass,db,dbcError: %s %s %s %s %s",host,user,pass,db,dbcError)
        #endif
    }
}

public plugin_end() {
    dbi_close(dbc)
}

public client_authorized(id) {

    if(is_user_bot(id) || is_user_hltv(id)) return PLUGIN_HANDLED

    aristokraatit[id] = isKnownPlayer(id)

    new maxplayers = get_maxplayers()
    new players = get_playersnum( 1 )
    new limit = maxplayers - get_cvar_num("amx_reservation")

    if ( players <= limit ) {
        new bool:allowIn = true
        if ( limit == players ) {
            allowIn = false
            if ( aristokraatit[id] >= 1 ) {
                // Etsit�n monotettava
                new monotettu = monotaPingein(aristokraatit[id])

                if ( monotettu >= 1 ) {
                    log_amx("Monotettu pelaajaa scorella: %d", monotettu)
                    allowIn = true
                } else {
                    client_print(id,print_console,"Ei ketaan monotettavaa :(")
                }
            }
        }

        if (allowIn == false) {
            #if defined NOISY
                new pName[32]
                get_user_name(id,pName,31)
                log_amx("Ohjataaan %s %s muualle",statukset[aristokraatit[id]], pName)
            #endif
            redirectPlayer(id)
        } else if( aristokraatit[id] >= 1 ) {
            if (get_user_flags(id) & ADMIN_CHAT) {
                new pName[32]
                get_user_name(id,pName,31)
                log_amx("%s:lla %s on jo ADMIN_CHAT oikat",statukset[aristokraatit[id]],pName)
            } else if(!adminsExists()) {
                #if defined NOISY
                    log_amx("Ei admineita, annetaan %s:lle %s oikat", statukset[aristokraatit[id]], vipAdmins)
                #endif

                new ivipAdmins = read_flags(vipAdmins)
                set_user_flags(id,ivipAdmins)
            }

        #if defined CHEATIN_DEATH
        } else {
            new id_str[3]
            num_to_str(id,id_str,3)
            set_task(120.0,"delayedCDCheck",2,id_str,3,"b")
        #endif
        }
    }
    else {
        log_amx("Serverilla enempi pelaajia kuin vapaita slotteja")
        redirectPlayer(id)
    }
    return PLUGIN_HANDLED
}

public client_putinserver(id) {
        /*
        new id_str[3]
        num_to_str(id, id_str, 2)
        #if defined NOISY
            log_amx("Passaan arvot announcePlayerille s:%s d:%d", id_str, id)
        #endif
        set_task(1.0, "announcePlayer",1,id_str)
        */
    announcePlayer(id)
}

public client_disconnect(id) {
    aristokraatit[id] = 0
    return PLUGIN_HANDLED
}


public isKnownPlayer(id) {

    if (dbc <= SQL_FAILED) {
        log_amx("Ei tietokantayhteytta. Ei voida tarkistaa aatelismia: %s", dbcError)
        return 0
    }

    // Haetaan pelaajan STEAMID/WONID
    new userauthid[32]
    get_user_authid(id,userauthid,31)

    // On perkele k�h� miehen hax hax hax.
    // Liian tiukka lauseiden pituudesta niin t�tyy erillisill�hauilla teh�

    // Aristokraatti haku
    new Result:Res = dbi_query(dbc,"SELECT '3' AS status FROM hlstats_PlayerUniqueIds INNER JOIN aristokraatit ON aristokraatit.uniqueId=hlstats_PlayerUniqueIds.uniqueId LEFT JOIN hlstats_Players ON  hlstats_Players.playerId=hlstats_PlayerUniqueIds.playerId  WHERE aristokraatit.uniqueId='%s'",userauthid)

    if (Res == RESULT_FAILED) {
        dbi_error(dbc,dbcError,127)
        log_amx("Ei voitu ladata aristokraatteja: %s",dbcError)
        dbi_free_result(Res)
    }
    else if (Res == RESULT_NONE) {
        dbi_free_result(Res)
    }
    else {
        while( dbi_nextrow(Res) > 0 ) {
            // Pelaaja on aristokraatti.
            dbi_free_result(Res)
            return 3
        }
    }

    // TOP3 haku
    new Result:Res2 = dbi_query(dbc,"SELECT '2' AS status FROM hlstats_PlayerUniqueIds LEFT JOIN hlstats_Players ON hlstats_Players.playerId=hlstats_PlayerUniqueIds.playerId  WHERE `skill`>=( SELECT skill FROM hlstats_Players ORDER BY skill DESC LIMIT 3,1) AND `hideranking`=0 AND `uniqueId`='%s'",userauthid)

    if (Res2 == RESULT_FAILED) {
        dbi_error(dbc,dbcError,127)
        log_amx("Ei voitu ladata statuskraatteja: %s",dbcError)
        dbi_free_result(Res2)
    }
    else if (Res2 == RESULT_NONE) {
        dbi_free_result(Res2)
    }
    else {
        while( dbi_nextrow(Res2) > 0 ) {
            // Pelaaja on statuskraatti
            dbi_free_result(Res2)
            return 2
        }
    }

    // Rekister�tyneiden haku
    new Result:Res3 = dbi_query(dbc,"SELECT '1' AS status FROM drupal_steamids WHERE `steamid` LIKE '%s'",userauthid)
    if (Res3 == RESULT_FAILED) {
        dbi_error(dbc,dbcError,127)
        log_amx("Ei voitu ladata rekisteroityneita: %s",dbcError)
        dbi_free_result(Res3)
    }
    else if (Res3 == RESULT_NONE) {
        dbi_free_result(Res3)
    }
    else {
        while( dbi_nextrow(Res3) > 0 ) {
            // Pelaaja on statuskraatti
            dbi_free_result(Res3)
            return 1
        }
    }

    // Pelaaja on n00bi
    return 0
}

public adminsExists() {
    new Players[32]
    new playerCount = 0, i = 0

    get_players(Players, playerCount)

    for (i=0; i<playerCount; i++) {
        if (get_user_flags(Players[i]) & ADMIN_CHAT) {
            clearVipAdmins()
            return 1
        }
    }
    return 0
}

public clearVipAdmins() {
    new Players[32]
    new playerCount = 0, i = 0
    get_players(Players, playerCount)

    new ivipAdmins = read_flags(vipAdmins)

    for (i=0; i<playerCount; i++) {
        if(get_user_flags(Players[i]) == ivipAdmins) {
            #if defined NOISY
                log_amx("Poistetaan oikat (idx:%i) (%i) %s:lta.", Players[i],ivipAdmins,statukset[aristokraatit[Players[i]]])
            #endif
            set_user_flags(Players[i],ADMIN_USER)
        }
    }
}

public monotaPingein ( aristoLevel ) {
    new Players[32]
    new playerCount, i, id

    new bigPing = 0
    new myPing, myLoss, bigPingOwner, pingMultiply

    #if defined NOISY
        new aName[32]
    #endif

    get_players(Players, playerCount)

    for (i=0; i<playerCount; i++) {
        id=Players[i]
        if ( !is_user_connected(Players[i]) && !is_user_connecting(Players[i]) ) {
            continue
        }
        else if (id == 0) {
            // Serveri iha ite
            continue
        }
        else if (access(id,ADMIN_RESERVATION)) {
            log_amx("ADMIN_RESERVATION (idx:%d)", id);
            continue
        }
        else if ( aristokraatit[id] >= aristoLevel ) {
            // Hyp��n saman tasoisten tai korkearvoisempien yli

            #if defined NOISY
                get_user_name(id,aName,31)
                log_amx("Passataan korkearvoisempi %s (%d >= %d)", aName, aristokraatit[id], aristoLevel)
            #endif

            continue
        }
        else {
            if (is_user_hltv(Players[i])) {
                #if defined NOISY
                    log_amx("HLTV, skipataan")
                #endif
                continue
            } else if (is_user_bot(Players[i])) {
                bigPingOwner = Players[i]
                myPing = 1000
            }
            else {
                // Haetaan Pingi
                get_user_ping(id, myPing, myLoss)
            }

            // Lis��n levelOffset*100 pingiin
            if( aristokraatit[id] > 0 ) {
                pingMultiply = ((aristoLevel-aristokraatit[id])*100)
                #if defined NOISY
                    log_amx("pingMultiply: ( %d - %d ) * 100 = %d",aristoLevel,aristokraatit[id],pingMultiply)
                #endif
            }
            else {
                pingMultiply = (aristoLevel*100);
            }

            #if defined NOISY
                get_user_name(bigPingOwner,aName,31)
                log_amx("ping: %d (name:%s) (idx:%d) (alevel:%d) (loop #%d)",myPing,aName,id,aristokraatit[id],i)
            #endif
            myPing = (myPing+pingMultiply)

            if ( myPing > bigPing ) {
                bigPingOwner = id
                // +1 jotta pingi aina >1
                bigPing = (myPing+1)
                #if defined NOISY
                    log_amx("Uusi suurin pingi: %s: %d (idx:%d) (loop #%d)",aName,bigPing,bigPingOwner,i)
                #endif
            }
        }
    }

    // Onko ket�n potkittavaa?
    if ( bigPing > 0 && bigPingOwner ) {

        #if defined NOISY
            get_user_name(bigPingOwner,aName,31)
            log_amx("Pelaaja %s statuksella %s scorella %d", aName, statukset[aristokraatit[bigPingOwner]], bigPing)
        #endif
        redirectPlayer(bigPingOwner)

        return bigPing
    }
    else {
        log_amx("Ei ketaan joka voitaisiin poistaa.")
    }
    return 0
}

public isMaxRedirs(id) {
    new redirCountStr[3]
    new redirCount = 0, newRedirCount = 0

    #if defined NOISY
        new aName[32]
        get_user_name(id,aName,31)
    #endif

    // Hae monestikko pelaaja on jo uudelleenohjattu
    get_user_info(id, "rq_redircount", redirCountStr, 2)
    redirCount = str_to_num(redirCountStr)

    // Jos ohjattu useammin kuin amx_rq_redircount, resetoi
    if( redirCount >= get_cvar_num("amx_rq_redircount") )  {
        newRedirCount = 1
    } else {
        newRedirCount = redirCount+1
    }

    #if defined NOISY
        log_amx("Pelaaja %s newRedirCount %d (vanha:%d)", aName, newRedirCount,redirCount)
    #endif

    // Asetetaan pelaajan redircount
    client_cmd(id, "setinfo rq_redircount %d", newRedirCount)

    #if defined NOISY
        log_amx("Pelaaja %s (idx:%d) redirCountilla %d (new:%d)", aName, id, redirCount,newRedirCount)
    #endif

    if(redirCount >= get_cvar_num("amx_rq_redircount")) {
        new userid = get_user_userid(id)
        server_cmd("kick #%d Serverit taynna. Yrita pian uudestaan.",userid,redirCount)
        return 1
    }
    return 0

}

public redirectPlayer(id) {

    if (isMaxRedirs(id)) {
        // Pelaajan redirCount t�nn� lopeta suoritus
        return PLUGIN_HANDLED
    } else {

        new myIP[16]
        get_cvar_string("ip",myIP,15)

        new Result:Res = dbi_query(dbc,"SELECT `name`, `publicaddress` AS `addr`, `port` FROM `hlstats_Servers` WHERE `game` = 'cstrike' AND `publicaddress` != '%s' ORDER BY RAND() LIMIT 0, 1", myIP)

        if (Res == RESULT_FAILED) {
            dbi_error(dbc,dbcError,127)
            log_amx("Virhe haettaessa servereita: %s",dbcError)
            dbi_free_result(Res)

            new id_str[3]
            num_to_str(id, id_str, 2)
            kickPlayer(id_str)

            return PLUGIN_HANDLED
        }
        else if (Res == RESULT_NONE) {
            log_amx("Ei muita servereita? Sitten monotan.")
            // Ei servereiet� Monota sitten
            dbi_free_result(Res)

            new id_str[3]
            num_to_str(id, id_str, 2)
            kickPlayer(id_str)

            return PLUGIN_HANDLED
        }

        new redirName[32], redirSrv[32], redirPort[6]
        while( dbi_nextrow(Res) > 0 ) {
            dbi_result(Res, "name", redirName, 31)
            dbi_result(Res, "addr", redirSrv, 31)
            dbi_result(Res, "port", redirPort, 6)

            #if defined NOISY
                log_amx("Loytyi serveri %s; %s:%s",redirName,redirSrv,redirPort)
            #endif

            dbi_free_result(Res)
        }

        #if defined NOISY
            log_amx("ohjataan serverille %s:%s",redirSrv,redirPort)
        #endif

        client_print(id,print_console,"======================================================")
        client_print(id,print_console," Serveri %s on pelaajalimitissa", myIP)
        client_print(id,print_console," Sinut ohjataan toiselle serverille:")
        client_print(id,print_console," > %s",redirName)
        client_print(id,print_console," > %s:%s",redirSrv,redirPort)
        client_print(id,print_console,"======================================================")

        client_cmd(id,"echo;disconnect; connect %s:%s",redirSrv,redirPort)

        // Varmistetaan viela etta h�pyy
        /*
        new id_str[3]
        num_to_str(id, id_str, 2)
        set_task(5.0, "kickPlayer",0,id_str)
        */
    }
    return PLUGIN_CONTINUE
}

public kickPlayer(id_str[3]) {
    new player_id
    player_id = str_to_num(id_str)
    new userid = get_user_userid(player_id)
    #if defined NOISY
        log_amx("Potkitaan idx:%d, #%d",player_id, userid)
    #endif
    server_cmd("kick #%d Serveri ei vastaanota pelaajia enempaa",userid)
    return PLUGIN_CONTINUE
}

public announcePlayer( pId ) {

    /*
    new pId
    pId = str_to_num(id_str)

    #if defined NOISY
        log_amx("announcePlayerille passattiin arvot s:%s d:%d", id_str, pId)
    #endif
    */

    // TODO: Miksei toimi?
    // Tarkista viel��pelaaja on linjoilla

    if ( !is_user_connected(pId) && !is_user_connecting(pId) ) {
        new cntd, cnntng
        cntd = is_user_connected(pId)
        cnntng = is_user_connecting(pId)
        #if defined NOISY
            log_amx("Pelaaja idx:%d ei ollut enaan yhdistynyt: connected:%d connecting:%d",pId,cntd,cnntng)
        #endif
        return PLUGIN_CONTINUE
    }

    new pName[32]
    get_user_name(pId, pName, 31)

    client_print(0,print_chat,"* %s %s liittyy peliin", statukset[aristokraatit[pId]], pName)
    #if defined NOISY
        log_amx("%s (idx:%d) %s liittyy peliin", statukset[aristokraatit[pId]],pId, pName)
    #endif
    return PLUGIN_CONTINUE
}

//
// CHEATIN-DEATH specified
//

#if defined CHEATIN_DEATH

public client_infochanged(id) {
    if(is_user_hltv(id) || is_user_bot(id)) return PLUGIN_HANDLED
    if(aristokraatit[id] <= 0 && is_user_connected(id) && !is_user_connecting(id)) {
        new nName[9]
        get_user_name(id, nName, 8)
        if(equali(nName, "[No C-D]") || equali(nName, "[Old C-D")) {
            #if defined NOISY
                log_amx("Pelaajan (idx:%d) nimesta loytyi %s prefix",id,nName)
            #endif
            // hanskaa
            cdstatuscheck(id)
        }
    }
    return PLUGIN_CONTINUE
}

public delayedCDCheck(id_str[]) {
    new player_id = str_to_num(id_str)
    cdstatuscheck(player_id)
    return PLUGIN_CONTINUE
}

public cdstatuscheck(id) {

    if(is_user_hltv(id) || is_user_bot(id)) return PLUGIN_HANDLED

    // Ei tarkasteta vippeja tai parempia
    if(aristokraatit[id] <= 0) {

        // Pelaajalla ei C-D:ta

        client_print(id,print_console,"[No C-D] =============================================")
        client_print(id,print_console," Asenna/Paivita Cheating-Death:")
        client_print(id,print_console,"     http://www.unitedadmins.com/cdeath.php")
        client_print(id,print_console," (Linux-hihhulit: #rautakuu @ QuakeNET)")
        client_print(id,print_console,"[No C-D] =============================================")

        new msg[1201], aname[32]
        get_user_name(id,aname,31)

        format(msg, 1200,"<html><head><title>No C-D</title></head><body bgcolor=black color=green>")
        format(msg, 1200,"%s [No C-D] =============================================<br />", msg)
        format(msg, 1200,"%s <strong>Hjuva tjoveri %s</strong><br />", msg, aname)
        format(msg, 1200,"%s <p>Asenna/p&auml;ivit&auml; Cheating-Death:<br />", msg)
        format(msg, 1200,"%s  <a href=http://www.unitedadmins.com/cdeath.php>http://www.unitedadmins.com/cdeath.php</a><br />", msg)
        format(msg, 1200,"%s  </p><p>(Linux-hihhulit: #rautakuu @ QuakeNET)</p>", msg)
        format(msg, 1200,"%s [No C-D] =============================================", msg)
        format(msg, 1200,"%s </body></html>", msg)
        show_motd(id, msg, "No C-D")

        client_print(0,print_chat,"* Pelaaja %s potkitaan puuttuvan C-Dn takia.", aname)

        new id_str[3]
        num_to_str(id,id_str,3)
        set_task(15.0,"delayNoCDKick",1,id_str,3)
    }
    return PLUGIN_HANDLED
}

public delayNoCDKick(id_str[]) {
    new player_id = str_to_num(id_str)
    new userid = get_user_userid(player_id)
    server_cmd("kick #%d Tarkista Cheating-Death",userid)
    return PLUGIN_HANDLED
}

#endif
