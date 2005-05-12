/*
 *  RQ_AutoBank -- Tallettaa rahat lopetuksen ohessa.
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
#include <cstrike>
#include <dbi>

new Sql:sql
new error[128]

new Author[] = "Rautakuu [dot] org"
new Plugin[] = "RQ_AutoBank"
new Version[] = "0.1.0"

public plugin_init() {
    register_plugin(Plugin, Version, Author)
    register_cvar("rq_autobank_version", Version, FCVAR_SERVER|FCVAR_SPONLY)
    server_cmd("localinfo rq_autobank_version %s", Version)

    register_cvar("bank_state","1")
    register_cvar("bank_min_players","2")
    register_cvar("bank_min_opening","1000")

    // Hieman aikaa ett‰ asetukset etc ehdit‰‰n lukea
    set_task(5.0,"sqlInit")
}

public sqlInit() {

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

public client_disconnect(id) {

    // TARKISTUKSET
    if(get_cvar_num("bank_state")) {
        // Pankki ei ole k‰ytˆss‰
        return PLUGIN_HANDLED
    }

    if(is_user_bot(id)) {
        // Botti ei tietty halua talletuksia teha
        return PLUGIN_HANDLED
    }

    if (sql <= SQL_FAILED) {
        log_amx("No database connectivity, cant save money: %s", error)
        return PLUGIN_HANDLED
    }

    new rq_autobankstr[3]
    new rq_noautobank = 0
    get_user_info(id, "rq_noautobank", rq_autobankstr, 2)
    rq_noautobank = str_to_num(rq_autobankstr)
    if(rq_noautobank) {
        console_print(id,"Jos haluat atomaattisesti tallettaa rahat, aseta rq_noautobank 0.")
        return PLUGIN_HANDLED
    }

    if(get_playersnum() < get_cvar_num("bank_min_players")) {
        // Ei riitt‰v‰sti pelaajia.
        console_print(id,"Ei riittavasti pelaajia saldon talletukseen.")
        return PLUGIN_HANDLED
    }

    new curmoney,neededmoney
    neededmoney = get_cvar_num("bank_min_opening")
    curmoney = cs_get_user_money(id)
    if ( curmoney < neededmoney ) {
        console_print(id,"Tarvitset ainakin $%d pankkitalletukseen.",neededmoney)
        return PLUGIN_HANDLED
    }
    // TARKISTUKSET LOPPUU

    // ITSE TAIKA


    new sid[35]
    get_user_authid(id,sid,34)
    new Result:result = dbi_query(sql,"SELECT * FROM bank WHERE sid = '%s'",sid)
    if(result == RESULT_FAILED)
    {
        dbi_free_result(result)
        new Result:inres = dbi_query(sql, "INSERT INTO `bank` ( `sid` , `amount` ) VALUES ( '%s', '%s' )", sid, curmoney)
        if(inres == RESULT_FAILED) {
            dbi_error(sql,error,127)
            log_amx("Virhe luotaessa lopetuksen yhteydessa pankkitilia: %s",error)
        }
        dbi_free_result(inres)
    } else {
        new Result:upres = dbi_query(sql, "UPDATE `bank` SET `amount` = '%s' WHERE `sid` = '%s'", curmoney, sid)
        if(upres == RESULT_FAILED) {
            dbi_error(sql,error,127)
            log_amx("Virhe paivitettaessa lopetuksen yhteydessa pankkitilia: %s",error)
        }
    }
    return PLUGIN_HANDLED
}

public plugin_end() {
    dbi_close(sql)
}
