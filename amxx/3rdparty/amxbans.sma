/*

    AMXBans, managing bans for Half-Life modifications
    Copyright (C) 2003, 2004  Ronald Renes / Jeroen de Rover

		web		: http://www.xs4all.nl/~yomama/amxbans/
		mail	: yomama@xs4all.nl
		ICQ		: 104115504
		IRC		: #xs4all (Quakenet, nickname YoMama)

		This file is part of AMXBans.

    AMXBans is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    AMXBans is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with AMXBans; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

		Check out readme.html for more information

		Current version: v4.0

*/

// specify tablenames here
#define tbl_bans "amx_bans"
#define tbl_banhist "amx_banhistory"
#define tbl_svrnfo "amx_serverinfo"

// If you want bans broadcasted to all players in the server, uncomment this
#define BCAST_BANS

#include <amxmodx>
//#include <amxmisc>
#include <dbi>

// 16k * 4 = 64k stack size
#pragma dynamic 16384

new Sql:sql
new Sql:rqSql
new merror[128]

new amxbans_version[10] = "amxx_4.0-rq"
new ban_motd[4096] = "Sinut on bannittu. Syy: %s. Kesto: %s. SteamIDsi: %s."
new Float:kick_delay=10.0

public sql_init() {
	new mhost[64], muser[32], mpass[32], mdb[32]

	get_cvar_string("amx_sql_host",mhost,64)
	get_cvar_string("amx_sql_user",muser,32)
	get_cvar_string("amx_sql_pass",mpass,32)
	get_cvar_string("amx_sql_db",mdb,32)

	sql = dbi_connect(mhost,muser,mpass,mdb,merror,128)

	if(sql == SQL_FAILED) {
		server_print("[AMXX] %L",LANG_SERVER,"SQL_CANT_CON",merror)
	}

	return PLUGIN_CONTINUE
}

public sql_init_rq() {
    new mhost[64], muser[32], mpass[32], mdb[32]

    get_cvar_string("rq_sql_host",mhost,64)
    get_cvar_string("rq_sql_user",muser,32)
    get_cvar_string("rq_sql_pass",mpass,32)
    get_cvar_string("rq_sql_db",mdb,32)

    rqSql = dbi_connect(mhost,muser,mpass,mdb,merror,128)

    if(rqSql == SQL_FAILED) {
        server_print("[AMXX][rqSql] %L",LANG_SERVER,"SQL_CANT_CON",merror)
    }

    return PLUGIN_CONTINUE
}

public sql_ban(adminid,player,ban_type[],player_steamid[], player_ip[], player_nick[], admin_ip[], admin_steamid[], admin_nick[], ban_reason[], ban_length[]) {

	new query[1024]

	if (equal(ban_type, "S")) {
		format(query,1024,"SELECT player_id FROM %s WHERE player_id='%s'", tbl_bans, player_steamid)
	} else {
		format(query,1024,"SELECT player_ip FROM %s WHERE player_ip='%s'", tbl_bans, player_ip)
	}

	new Result:result = dbi_query(rqSql,query)
	new Result:Retval = RESULT_NONE

	if (result == RESULT_FAILED) {
		dbi_error(rqSql,merror,128)
		client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"SQL_BAN_ERROR",merror)
		server_print("[AMXX] %L",LANG_SERVER,"SQL_BAN_ERROR",merror)
		return PLUGIN_HANDLED
	}

	if (dbi_nextrow(result)>0) {
		dbi_free_result(result)

		if (strlen(player_ip)>0) {
			Retval = dbi_query(rqSql,"UPDATE `%s` SET player_ip='%s' WHERE player_id='%s'", tbl_bans, player_ip, player_steamid)

			if (Retval == RESULT_FAILED) {
				dbi_error(rqSql,merror,128)
				client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"SQL_BAN_UPDATE_ERROR",merror)
				server_print("[AMXX] %L",LANG_SERVER,"SQL_BAN_UPDATE_ERROR",merror)
			} else {
				client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"ALLREADY_BANNED_IP_ADDED",player_steamid, player_ip)
				server_print("[AMXX] %L",LANG_SERVER,"ALLREADY_BANNED_IP_ADDED",player_steamid, player_ip)
			}
		} else {
			client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"ALLREADY_BANNED",player_steamid)
			server_print("[AMXX] %L",LANG_SERVER,"ALLREADY_BANNED",player_steamid)
		}

		return PLUGIN_HANDLED
	}

	new ip[32]
	get_cvar_string("ip", ip, 32)
	new port[10]
	get_cvar_string("port", port, 10)
	new server_name[100]
	get_cvar_string("hostname",server_name,100)

	new ban_created = get_systime(0)

	//make sure there are no single quotes in these 4 vars
	szQuerySafe(player_nick)
	szQuerySafe(admin_nick)
	szQuerySafe(ban_reason)
	szQuerySafe(server_name)

	Retval = dbi_query(rqSql,"INSERT into `%s` (player_id,player_ip,player_nick,admin_ip,admin_id,admin_nick,ban_type,ban_reason,ban_created,ban_length,server_name,server_ip) values('%s','%s','%s','%s','%s','%s','%s','%s','%i','%s','%s','%s:%s')",tbl_bans, player_steamid, player_ip, player_nick, admin_ip, admin_steamid, admin_nick, ban_type,ban_reason, ban_created, ban_length,server_name, ip,port)

	if (Retval == RESULT_FAILED) {
		dbi_error(rqSql,merror,128)
		client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"SQL_BAN_INSERT_ERROR",merror)
		server_print("[AMXX] %L",LANG_SERVER,"SQL_BAN_INSERT_ERROR",merror)
		return PLUGIN_HANDLED
	}

	new bid[20]
	new Result:Result2 = dbi_query(rqSql,"SELECT bid FROM `%s` WHERE player_id='%s' AND player_ip='%s' AND ban_type='%s'", tbl_bans, player_steamid, player_ip, ban_type)

	if (Result2 == RESULT_FAILED) {
		dbi_error(rqSql,merror,128)
		client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"SQL_ERROR",merror)
		server_print("[AMXX] %L",LANG_SERVER,"SQL_ERROR",merror)
		return PLUGIN_HANDLED
	}

	if (dbi_nextrow(Result2)>0) {
		dbi_field(Result2,1,bid,20)
	} else {
		copy(bid,20, "0");
	}

	dbi_free_result(Result2)

	if (player) {

		if (equal(ban_type, "S")) {
			client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"STEAMID_BANNED_SUCCESS_IP_LOGGED",player_steamid)
		} else {
			client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"STEAMID_BANNED_SUCCESS",player_steamid)
		}

		new id_str[3]
		num_to_str(player,id_str,3)

		client_print(player,print_console,"[AMXX] ===============================================")
		client_print(player,print_console,"[AMXX] %L",LANG_PLAYER,"MSG_1")
		client_print(player,print_console,"[AMXX] %L",LANG_PLAYER,"MSG_2", ban_reason)
		client_print(player,print_console,"[AMXX] %L",LANG_PLAYER,"MSG_3", ban_length)
		client_print(player,print_console,"[AMXX] %L",LANG_PLAYER,"MSG_4", player_steamid)
		client_print(player,print_console,"[AMXX] %L",LANG_PLAYER,"MSG_5", player_ip)
		client_print(player,print_console,"[AMXX] ===============================================")

		new msg[4096]
		format(msg, 4096, ban_motd, bid)
		show_motd(player, msg, "Banned")
		set_task(kick_delay,"delayed_kick",1,id_str,3)
	} else {
		client_print(adminid,print_console,"[AMXX] %L",LANG_PLAYER,"STEAMID_BANNED_SUCCESS",player_steamid)
	}

	return PLUGIN_CONTINUE
}

public amx_ban(id) {
	if (!(get_user_flags(id)&ADMIN_KICK)) {
		client_print(id,print_console,"[AMXX] %L",LANG_SERVER,"NO_ACCESS_TO_CMD")
		return PLUGIN_HANDLED
	}

	if (read_argc() < 4) {
		client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"AMX_BAN_SYNTAX")
		return PLUGIN_HANDLED
	}

	new player_ip[50], player_steamid[50], player_nick[100], admin_ip[100], admin_steamid[50], admin_nick[100], ban_reason[255], ban_length[50]

	get_user_ip(id,admin_ip,100,1)
	get_user_authid(id, admin_steamid, 50)
	get_user_name(id,admin_nick,100)

	new steamidorusername[50]

	new text[128]
	read_args(text,128)
	parse(text,ban_length,50,steamidorusername,50)
	new length1 = strlen(ban_length)
	new length2 = strlen(steamidorusername)
	new length = length1 + length2
	length+=2
	new reason[128]
	read_args(reason,128)
	format(ban_reason, 255, "%s", reason[length]);

	new player = find_player("c",steamidorusername)

	if (!player) {
		player = find_player("bl",steamidorusername)
	}

	if (player) {
		if (get_user_flags(player)&ADMIN_IMMUNITY) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"HAS_IMMUNITY")
			return PLUGIN_HANDLED
		} else if (is_user_bot(player)) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"IS_BOT")
			return PLUGIN_HANDLED
		} else if (is_user_hltv(player)) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"IS_HLTV")
			return PLUGIN_HANDLED
		}

		get_user_authid(player, player_steamid, 50)
		get_user_name(player, player_nick, 100)
		get_user_ip(player, player_ip, 50, 1)
	} else {
		format(player_steamid, 50, "%s", steamidorusername)
		format(player_nick, 100, "unknown_%s", player_steamid)
		format(player_ip, 50, "")
	}

	sql_ban(id,player,"S",player_steamid, player_ip, player_nick, admin_ip, admin_steamid, admin_nick, ban_reason, ban_length)

#if defined BCAST_BANS
	client_print(0,print_chat,"%L",LANG_PLAYER,"PUBLIC_BAN_ANNOUNCE",player_nick,ban_length,ban_reason)
#endif

	return PLUGIN_HANDLED
}

public amx_banip(id) {
	if (!(get_user_flags(id)&ADMIN_KICK)) {
		client_print(id,print_console,"[AMXX] %L",LANG_SERVER,"NO_ACCESS_TO_CMD")
		return PLUGIN_HANDLED
	}

	if (read_argc() < 4) {
		client_print(id,print_console,"[AMXX] %L",LANG_SERVER,"AMX_BANIP_SYNTAX")
		return PLUGIN_HANDLED
	}

	new player_ip[50],player_steamid[50], player_nick[100], admin_ip[100], admin_steamid[50], admin_nick[100], ban_reason[255], ban_length[50]

	get_user_ip(id,admin_ip,100,1)
	get_user_authid(id, admin_steamid, 50)
	get_user_name(id,admin_nick,100)

	new steamidorusername[50]

	new text[128]
	read_args(text,128)
	parse(text,ban_length,50,steamidorusername,50)
	new length1 = strlen(ban_length)
	new length2 = strlen(steamidorusername)
	new length = length1 + length2
	length+=2
	new reason[128]
	read_args(reason,128)
	format(ban_reason, 255, "%s", reason[length]);

	new player = find_player("c",steamidorusername)
	if (!player) {
		player = find_player("bl",steamidorusername)
	}

	if (player) {
		if (get_user_flags(player)&ADMIN_IMMUNITY) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"HAS_IMMUNITY")
			return PLUGIN_HANDLED
		} else if (is_user_bot(player)) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"IS_BOT")
			return PLUGIN_HANDLED
		} else if (is_user_hltv(player)) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"IS_HLTV")
			return PLUGIN_HANDLED
		}

		get_user_authid(player, player_steamid, 50)
		get_user_name(player, player_nick, 100)
		get_user_ip(player, player_ip, 50, 1)

	} else {
		format(player_steamid, 50, "%s", steamidorusername)
		format(player_nick, 100, "unknown_%s", player_steamid)
		format(player_ip, 50, "");
	}

	sql_ban(id,player,"SI",player_steamid, player_ip, player_nick, admin_ip, admin_steamid, admin_nick, ban_reason, ban_length)

#if defined BCAST_BANS
	client_print(0,print_chat,"%L",LANG_SERVER,"PUBLIC_BAN_ANNOUNCE",player_nick,ban_length,ban_reason)
#endif

	return PLUGIN_HANDLED
}

public amx_find(id) {
	if (!(get_user_flags(id)&ADMIN_KICK)) {
		client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"NO_ACCESS_TO_CMD")
		return PLUGIN_HANDLED
	}

	if (read_argc() < 2) {
		client_print(id,print_console,"[AMXX] %L",LANG_SERVER,"AMX_FIND_SYNTAX")
		return PLUGIN_HANDLED
	}

	if (dbi_error(sql,merror,128)) {
		client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"GENERIC_SQL_ERROR",merror)
		server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",merror)
		return PLUGIN_HANDLED
	}

	new steamidorusername[50] , player_steamid[50]
	read_argv(1,steamidorusername,50)

 	new player = find_player("c",steamidorusername) //by steamid

 	if (!player) {
		player = find_player("bl",steamidorusername) // by nick
	}

	if (player) {
		if (get_user_flags(player)&ADMIN_IMMUNITY) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"HAS_IMMUNITY")
			return PLUGIN_HANDLED
		} else if (is_user_bot(player)) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"IS_BOT")
			return PLUGIN_HANDLED
		} else if (is_user_hltv(player)) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"IS_HLTV")
			return PLUGIN_HANDLED
		}

		get_user_authid(player, player_steamid, 50)
	} else {
		new test = str_to_num(steamidorusername)

		if (test == 0) {
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"STEAMID_NOT_FOUND")
			return PLUGIN_HANDLED
		}

		format(player_steamid, 50, "%s", steamidorusername)
	}

	new Result:result = dbi_query(sql,"SELECT bid,ban_created,ban_length,ban_reason,admin_nick,admin_id,player_nick FROM `%s` WHERE player_id='%s' order by ban_created desc", tbl_bans, player_steamid)

	if (result == RESULT_NONE) {
		dbi_error(sql,merror,128)
		client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"GENERIC_SQL_ERROR",merror)
		server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",merror)
		return PLUGIN_HANDLED
	}

	new bid[20], ban_created[50], ban_length[50], ban_reason[100], admin_nick[100],admin_steamid[50],player_nick[100],remaining[50]
	new ban_created_int, ban_length_int, current_time_int, ban_left
	//I suggest you use dbi_num_rows, instead of this
	new res = dbi_nextrow(result)

	if (res > 0) {
		client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"AMX_FIND_RESULT_1",player_steamid)
		while (res>0) {
			dbi_field(Result,1,bid,20)
			dbi_field(Result,2,ban_created,50)
			dbi_field(Result,3,ban_length,50)
			dbi_field(Result,4,ban_reason,100)
			dbi_field(Result,5,admin_nick,50)
			dbi_field(Result,6,admin_steamid,50)
			dbi_field(Result,7,player_nick,50)

			current_time_int = get_systime(0)
			ban_created_int = str_to_num(ban_created)
			ban_length_int = str_to_num(ban_length) * 60 // in secs

			if ((ban_length_int == 0) || (ban_created_int==0)) {
				remaining = "eternity!"
			} else {
				ban_left = (ban_created_int+ban_length_int-current_time_int) / 60

				if (ban_left<0) {
					format(remaining,50,"none",ban_left)
				} else {
					format(remaining,50,"%i minutes",ban_left)
				}
			}

			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"AMX_FIND_RESULT_2", bid, player_nick)
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"AMX_FIND_RESULT_3", admin_nick, admin_steamid, ban_reason)
			client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"AMX_FIND_RESULT_4", ban_length,remaining)
			client_print(id,print_console,"[AMXX] =================")
		}
	} else {
		client_print(id, print_console, "[AMXX] %L",LANG_PLAYER,"AMX_FIND_NORESULT", player_steamid)
	}

	return PLUGIN_HANDLED
}

public client_authorized(id) {
	new authid[50],plip[50]
	get_user_authid(id,authid,50)
	get_user_ip(id,plip,50,1)

	new query[4096]
	format(query,4096,"SELECT bid,ban_created,ban_length,ban_reason,admin_nick,admin_id,player_nick,server_name,server_ip,ban_type FROM `%s` WHERE ((player_id='%s') and ((ban_type='S') or (ban_type='SI'))) or ((player_ip='%s') and (ban_type='SI'))",tbl_bans,authid,plip)
	new Result:result = dbi_query(sql,query)

	if (result == RESULT_FAILED) {
		dbi_error(sql,merror,128)
		client_print(id,print_console,"[AMXX] %L",LANG_PLAYER,"GENERIC_SQL_ERROR",merror)
		server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",merror)
		return PLUGIN_HANDLED
	}

	if(dbi_nextrow(result)>0) {
		new bid[20], ban_created[50], ban_length[50], ban_reason[100], admin_nick[100],admin_steamid[50],player_nick[100],server_name[100],server_ip[100],bantype[10]
		dbi_field(result,1,bid,20)
		dbi_field(result,2,ban_created,50)
		dbi_field(result,3,ban_length,50)
		dbi_field(result,4,ban_reason,100)
		dbi_field(result,5,admin_nick,50)
		dbi_field(result,6,admin_steamid,50)
		dbi_field(result,7,player_nick,50)
		dbi_field(result,8,server_name,100)
		dbi_field(result,9,server_ip,100)
		dbi_field(result,10,bantype,10)

		new current_time_int = get_systime(0)
		new ban_created_int = str_to_num(ban_created)
		new ban_length_int = str_to_num(ban_length) * 60 // in secs
		dbi_free_result(result)

		if ((ban_length_int == 0) || (ban_created_int==0) || (ban_created_int+ban_length_int > current_time_int)) {
			new time_msg[32]

			if ((ban_length_int == 0) || (ban_created_int==0)) {
				time_msg = "Permanent"
			} else {
				new ban_left = (ban_created_int+ban_length_int-current_time_int) / 60
				format(time_msg,32,"%i minutes",ban_left)
			}

			client_cmd(id, "echo ^"[AMXX] You have been banned by admin %s from this server.^"", admin_nick)

			if (ban_length_int==0) {
				client_cmd(id, "echo ^"[AMXX] You have banned permanently. ^"")
			} else {
				client_cmd(id, "echo ^"[AMXX] Remaining %s. ^"", time_msg)
			}

			client_cmd(id, "echo ^"[AMXX] Reason %s. ^"", ban_reason)
			client_cmd(id, "echo ^"[AMXX] Your nick: %s. Your steamid: %s. ^"", player_nick, authid)
			client_cmd(id, "echo ^"[AMXX] Your IP is %s. ^"", plip)

			new id_str[3]
			num_to_str(id,id_str,3)
			set_task(1.0,"delayed_kick",0,id_str,3)
			return PLUGIN_HANDLED
		} else {
			client_cmd(id, "echo ^"[AMXX] You were been banned at least once, dont let it happen again!.^"")

			new unban_created = get_systime(0)

			//make sure there are no single quotes in these 4 vars
			szQuerySafe(player_nick)
			szQuerySafe(admin_nick)
			szQuerySafe(ban_reason)
			szQuerySafe(server_name)

			new Result:Retval = dbi_query(rqSql,"INSERT INTO `%s` (player_id,player_ip,player_nick,admin_id,admin_nick,ban_type,ban_reason,ban_created,ban_length,server_name,unban_created,server_ip) values('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%i','%s')",tbl_banhist,authid,plip,player_nick,admin_steamid,admin_nick,bantype,ban_reason,ban_created,ban_length,server_name,unban_created,server_ip)

			if (result == RESULT_NONE) {
				dbi_error(rqSql,merror,128)
				server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",merror)
				return PLUGIN_HANDLED
			}

			Retval = dbi_query(rqSql,"DELETE FROM `%s` WHERE bid=%s",tbl_bans,bid)

			if (Retval == RESULT_NONE) {
				dbi_error(rqSql,merror,128)
				server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",merror)
				return PLUGIN_HANDLED
			}

			return PLUGIN_HANDLED
		}
	}
	return PLUGIN_CONTINUE
}

public delayed_kick(id_str[]) {
	new player_id = str_to_num(id_str)
	new userid = get_user_userid(player_id)
	server_cmd("kick #%d",userid)
	return PLUGIN_CONTINUE
}

public cmdLst(id,level,cid) {
	new players[32], inum, authid[32],name[32],ip[50]

	get_players(players,inum)
	console_print(id,"playerinfo")

	for(new a = 0; a < inum; ++a) {
		get_user_ip(players[a],ip,49,1)
		get_user_authid(players[a],authid,31)
		get_user_name(players[a],name,31)
		console_print(id,"#WM#%s#WMW#%s#WMW#%s#WMW#",name,authid,ip)
	}

	return PLUGIN_HANDLED
}

public plugin_init() {
	register_plugin("AMXBans","4.0","LuX / YoMama")

	register_dictionary("amxbans.txt")

	register_clcmd("amx_ban","amx_ban",ADMIN_BAN,"amx_ban <time in mins> <steamID | nickname> <reason>")
	register_srvcmd("amx_ban","amx_ban",-1,"amx_ban <time in min> <steamID | name> <reason>")

	register_clcmd("amx_banip","amx_banip",ADMIN_BAN,"amx_banip <time in mins> <IP | steamID | nickname> <reason>")
	register_srvcmd("amx_banip","amx_banip",-1,"amx_banip <time in mins> <IP | steamID | nickname> <reason>")

	register_clcmd("amx_find","amx_find",ADMIN_BAN,"amx_find <steamID | nickname>")
	register_srvcmd("amx_find","amx_find",-1,"amx_find <steamID | name>")

	register_concmd("amx_list","cmdLst",0,"Displays playerinfo")

	register_cvar("amxbans_version", amxbans_version)

	//register_srvcmd("amx_amxbanssql","sql_init")

     // Rautakuu mysli
    register_cvar("rq_sql_host","rautakuu.org")
    register_cvar("rq_sql_user","user")
    register_cvar("rq_sql_pass","p4ssw0rd")
    register_cvar("rq_sql_db","hlds")
    // /Rautakuu mysli

	set_task(0.1,"init_function")

	//new configsDir[64]
	//get_configsdir(configsDir, 63)
	//server_cmd("exec %s/sql.cfg;amx_amxbanssql", configsDir)
	//sql_init()
	//banmod_online()

	return PLUGIN_CONTINUE
}


public init_function() {
	sql_init()
    sql_init_rq()
	banmod_online()
}

public banmod_online() {
	new timestamp = get_systime(0)
	new ip[32]
	get_cvar_string("ip", ip, 32)
	new port[10]
	get_cvar_string("port", port, 10)
	new servername[200]
	get_cvar_string("hostname",servername,100)
	new modname[32]
	get_modname(modname,31)

	new Result:result = dbi_query(sql,"select timestamp,hostname,address,gametype,rcon,amxban_version,amxban_motd,motd_delay from `%s` where address = '%s:%s'",tbl_svrnfo,ip,port)

	if (result == RESULT_FAILED) {
		dbi_error(sql,merror,128)
		server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",merror)
		return PLUGIN_HANDLED
	}

	szQuerySafe(servername)

	if (dbi_nextrow(result)==0) {
		result = dbi_query(rqSql,"INSERT INTO `%s` VALUES ('', '%i','%s', '%s:%s', '%s', '', '%s', '', '', '0')", tbl_svrnfo, timestamp, servername, ip, port, modname, amxbans_version)

		if (result == RESULT_FAILED) {
			dbi_error(rqSql,merror,128)
			server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",merror)
			return PLUGIN_HANDLED
		}
	} else {
		new ban_motd_tmp[4096]
		dbi_field(Result, 7, ban_motd_tmp, 4096)

		if (strlen(ban_motd_tmp)) {
			copy(ban_motd,4096,ban_motd_tmp)
		}

		new kick_delay_str[10]
		dbi_field(Result, 8, kick_delay_str, 10)

		if (floatstr(kick_delay_str)>1.0) {
			kick_delay=floatstr(kick_delay_str)
		} else {
			kick_delay=12.0
		}

		new Result:register = dbi_query(rqSql,"update `%s` set timestamp='%i',hostname='%s',gametype='%s',amxban_version='%s', amxban_menu='0' where address = '%s:%s'", tbl_svrnfo, timestamp, servername, modname, amxbans_version, ip, port)

		if (register == RESULT_FAILED) {
			dbi_error(rqSql,merror,128)
			server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",merror)
			return PLUGIN_HANDLED
		}
	}

	log_amx("AMXBans %s is online",amxbans_version)

	return PLUGIN_CONTINUE
}

public plugin_end() {
	dbi_close(sql)
    dbi_close(rqSql)
}

szQuerySafe(szString[], iStringSize = 0) { //escapes ' and " characters

	new helpString[128] //temporary string
	copy(helpString,127,szString)

	if (!iStringSize) { //no length specified
		while ( szString[iStringSize] ) { //as soon as we stumble on a \0 character stop the length counting
			iStringSize++
		}
	}

	if ( !contain(szString, "'") &&  !contain(szString, "^"") ) {
		return //do nothing"
	}

	new i, j
	while (i < iStringSize) {
		if (helpString[i] == '^'' || helpString[i] == '^"') {
			//"
			szString[j++] = '\'
			//replace the single quote with a space
		}
		szString[j] = helpString[i]

		i++
		j++
	}
	//
	//replace(szString,iStringSize,"^"" ,"\^"")

}

/*
stock getsystemtime() {
	return get_systime(0);
}
*/
