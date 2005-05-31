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
#define tbl_reasons "amx_banreasons"
#define tbl_svrnfo "amx_serverinfo"

// If you want static reasons instead of reasons fetched from the database, uncomment this
//#define STATIC_REASONS

#include <amxmodx>
#include <amxmisc>

#if !defined STATIC_REASONS
#include <dbi>
#endif

new g_menuPosition[33]
new g_menuPlayers[33][32]
new g_menuPlayersNum[33]
new g_menuOption[33]
new g_menuSettings[33]

new g_bannedPlayer[33]

new g_banReasons[7][128]
new g_lastCustom[33][128]
new g_inCustomReason[33]

new g_coloredMenus
new g_aNum = 0

new Sql:sql
new error[128]

#if !defined STATIC_REASONS
public plugin_modules() {
   require_module("dbi")
}
#endif

public client_connect(id) {
  g_lastCustom[id][0]='^0'
  g_inCustomReason[id]=0
}

public plugin_init() {
#if defined STATIC_REASONS
  copy(g_banReasons[0],127,"Cheating")
  copy(g_banReasons[1],127,"Laming")
  copy(g_banReasons[2],127,"Swearing")
  copy(g_banReasons[3],127,"Wallhack")
  copy(g_banReasons[4],127,"Aimbot")
  copy(g_banReasons[5],127,"Wallhack + Aimbot")
  copy(g_banReasons[6],127,"Camping")
#endif

  register_plugin("AMXBans Menu","4.0","YoMama")

  register_dictionary("common.txt")
  register_dictionary("amxbans.txt")

#if !defined STATIC_REASONS
  register_concmd("amx_reloadreasons","reasonReload",ADMIN_CFG)
#endif

  register_clcmd("amxbans_menu","cmdBanMenu",ADMIN_BAN,"- displays ban menu")
  register_clcmd("amxbans_menureason","cmdBanMenuReason",ADMIN_BAN,"- configures custom ban message")

  register_menucmd(register_menuid("Ban Menu"),1023,"actionBanMenu")
  register_menucmd(register_menuid("Ban Reason Menu"),1023,"actionBanMenuReason")

  g_coloredMenus = colored_menus()

#if !defined STATIC_REASONS
  set_task(0.1,"init_function")
#endif
}

public init_function() {
	sql_init()
	fetchReasons()
	banmenu_online()
}

public sql_init() {
	new host[64], user[32], pass[32], db[32]

	get_cvar_string("rq_sql_host",host,64)
	get_cvar_string("rq_sql_user",user,32)
	get_cvar_string("rq_sql_pass",pass,32)
	get_cvar_string("rq_sql_db",db,32)

	sql = dbi_connect(host,user,pass,db,error,128)

	if(sql == SQL_FAILED) {
		server_print("[AMXX] %L",LANG_SERVER,"SQL_CANT_CON",error)
		dbi_close(sql)
	}

	return PLUGIN_CONTINUE
}

#if !defined STATIC_REASONS
public banmenu_online() {
  new ip[32]
  get_cvar_string("ip", ip, 32)
  new port[10]
  get_cvar_string("port", port, 10)

  new Result:register = dbi_query(sql,"UPDATE `%s` set amxban_menu = '1' where address = '%s:%s'", tbl_svrnfo, ip, port)

  if (register == RESULT_FAILED) {
    dbi_error(sql,error,128)
    server_print("[AMXX] %L",LANG_SERVER,"GENERIC_SQL_ERROR",error)
    dbi_free_result(register)
    dbi_close(sql)
    return PLUGIN_HANDLED
  }

  if (g_aNum == 1) {
    log_amx("AMXBans Menu is online (1 reason loaded)")
  } else {
    log_amx("AMXBans Menu is online (%d reasons loaded)",g_aNum)
  }

  return PLUGIN_CONTINUE
}

public fetchReasons() {
  new ip[32],port[10]
  get_cvar_string("ip",ip,32)
  get_cvar_string("port",port,10)

  new Result:get_reasons = dbi_query(sql,"SELECT reason FROM %s WHERE address = '%s:%s'",tbl_reasons,ip,port)

  if (get_reasons == RESULT_FAILED) {
    dbi_error(sql,error,127)
    server_print("[AMXX] %L",LANG_SERVER,"SQL_CANT_LOAD_REASONS",error)
    dbi_free_result(get_reasons)
    dbi_close(sql)
    return PLUGIN_HANDLED
  } else if (get_reasons == RESULT_NONE) {
    server_print("[AMXX] %L",LANG_SERVER,"NO_REASONS")
    dbi_free_result(get_reasons)
    return PLUGIN_HANDLED
  }

  g_aNum = 0
  while( dbi_nextrow(get_reasons) > 0 ) {
    dbi_result(get_reasons, "reason", g_banReasons[g_aNum], 127)
    ++g_aNum
  }

  if (g_aNum == 1) {
    server_print("[AMXX] %L", LANG_SERVER, "SQL_LOADED_REASON" )
  } else {
    server_print("[AMXX] %L", LANG_SERVER, "SQL_LOADED_REASONS", g_aNum )
  }

  return PLUGIN_HANDLED
}

public reasonReload(id,level,cid) {
  if (!cmd_access(id,level,cid,1)) {
    return PLUGIN_HANDLED
  } else {
    fetchReasons()

    if (id != 0) {
      if (g_aNum == 1) {
        console_print(id,"[AMXX] %L", LANG_SERVER, "SQL_LOADED_REASON" )
      } else {
        console_print(id,"[AMXX] %L", LANG_SERVER, "SQL_LOADED_REASONS", g_aNum )
      }
    }
  }

  return PLUGIN_HANDLED
}
#endif

/* Ban menu */

public actionBanMenu(id,key) {
  switch (key) {
    case 7: {
      ++g_menuOption[id]
      g_menuOption[id] %= 4
      switch(g_menuOption[id]){
      case 0: g_menuSettings[id] = 0
      case 1: g_menuSettings[id] = 10000
      case 2: g_menuSettings[id] = 20000
      case 3: g_menuSettings[id] = 40000
      }

      displayBanMenu(id,g_menuPosition[id])
    }

    case 8: displayBanMenu(id,++g_menuPosition[id])
    case 9: displayBanMenu(id,--g_menuPosition[id])
    default: {
      g_bannedPlayer[id] = g_menuPlayers[id][g_menuPosition[id] * 6 + key]
      cmdBanReasonMenu(id)
    }
  }
  return PLUGIN_HANDLED
}

displayBanMenu(id,pos) {
  if (pos < 0)  return

  get_players(g_menuPlayers[id],g_menuPlayersNum[id])

  new menuBody[512]
  new b = 0
  new i
  new name[32]
  new start = pos * 6

  if (start >= g_menuPlayersNum[id]) {
    start = pos = g_menuPosition[id] = 0
  }

  new len = format(menuBody,511, g_coloredMenus ?
    "\y%L\R%d/%d^n\w^n" : "%L %d/%d^n^n",
    id,"BAN_MENU",pos+1,(  g_menuPlayersNum[id] / 6 + ((g_menuPlayersNum[id] % 6) ? 1 : 0 )) )

  new end = start + 6
  new keys = MENU_KEY_0|MENU_KEY_8

  if (end > g_menuPlayersNum[id]) {
    end = g_menuPlayersNum[id]
  }

  for (new a = start; a < end; ++a) {
    i = g_menuPlayers[id][a]
    get_user_name(i,name,31)

    if ( is_user_bot(i) || access(i,ADMIN_IMMUNITY) ) {
      ++b
      if ( g_coloredMenus ) {
        len += format(menuBody[len],511-len,"\d%d. %s^n\w",b,name)
      } else {
        len += format(menuBody[len],511-len,"#. %s^n",name)
      }
    } else {
      keys |= (1<<b)
      len += format(menuBody[len],511-len,"%d. %s^n",++b,name)
    }
  }

  if ( g_menuSettings[id] ) {
    len += format(menuBody[len],511-len,"^n8. %L^n", id, "BAN_FOR_MIN", g_menuSettings[id] )
  } else {
    len += format(menuBody[len],511-len,"^n8. %L^n", id, "BAN_PERM" )
  }

  if (end != g_menuPlayersNum[id]) {
    format(menuBody[len],511-len,"^n9. %L...^n0. %L", id, "MORE", id, pos ? "BACK" : "EXIT")
    keys |= MENU_KEY_9
  } else {
    format(menuBody[len],511-len,"^n0. %L", id, pos ? "BACK" : "EXIT")
  }

  show_menu(id,keys,menuBody,-1,"Ban Menu")
}

public cmdBanMenu(id,level,cid) {
  if (!cmd_access(id,level,cid,1)) {
    return PLUGIN_HANDLED
  }

  g_menuOption[id] = 3
  g_menuSettings[id] = 10000
  displayBanMenu(id,g_menuPosition[id] = 0)

  return PLUGIN_HANDLED
}

cmdBanReasonMenu(id) {
  new menuBody[1024]
  new len = format(menuBody,1023, g_coloredMenus ? "\y%s\R^n\w^n" : "%s^n^n","Reason")
  new i=0;

  while (i<6) {
    len+=format(menuBody[len],1023-len,"%d. %s^n",i+1,g_banReasons[i])
    i++;
  }

  len+=format(menuBody[len],1023-len,"^n8. Custom^n")
  if (g_lastCustom[id][0]!='^0') {
    len+=format(menuBody[len],1023-len,"^n9. %s^n",g_lastCustom[id])
  }

  len+=format(menuBody[len],1023-len,"^n0. %L^n",id,"EXIT")

  new keys = MENU_KEY_1 | MENU_KEY_2 | MENU_KEY_3 | MENU_KEY_4 | MENU_KEY_5 | MENU_KEY_6 | MENU_KEY_7 | MENU_KEY_8 | MENU_KEY_0
  if (g_lastCustom[id][0]!='^0') {
    keys |= MENU_KEY_9
  }
  show_menu(id,keys,menuBody,-1,"Ban Reason Menu")
}

public actionBanMenuReason(id,key) {
  switch (key) {
    case 9: { // go back to ban menu
      displayBanMenu(id,g_menuPosition[id])
    }

    case 7: {
      g_inCustomReason[id]=1
      client_cmd(id,"messagemode amx_banmenureason")
      return PLUGIN_HANDLED
    }

    case 8: {
      banUser(id,g_lastCustom[id])
    }

    default: {
      banUser(id,g_banReasons[key])
    }
  }
  return PLUGIN_HANDLED
}

banUser(id,banReason[]) { /* id is the player banning, not player being banned :] */
  new player = g_bannedPlayer[id]

  new name[32], name2[32], authid[32],authid2[32]
  get_user_name(player,name2,31)
  get_user_authid(player,authid2,31)
  get_user_authid(id,authid,31)
  get_user_name(id,name,31)

  switch (get_cvar_num("amx_show_activity")) {
    case 2: client_print(0,print_chat,"%L",LANG_PLAYER,"ADMIN_BAN_2",name,name2)
    case 1: client_print(0,print_chat,"%L",LANG_PLAYER,"ADMIN_BAN_1",name2)
  }

  if (equal("4294967295",authid2)) { /* lan */
    new ipa[32]
    get_user_ip(player,ipa,31,1)
    console_cmd(id,"amx_banip %d %s %s" ,g_menuSettings[id],ipa,banReason)
  } else {
   	console_cmd(id,"amx_ban %d %s %s" ,g_menuSettings[id],authid2,banReason)
  }
  server_exec()
}

public cmdBanMenuReason(id,level,cid) {
  if (!cmd_access(id,level,cid,1)) {
    return PLUGIN_HANDLED
  }

  new szReason[128]
  read_argv(1,szReason,127)
  copy(g_lastCustom[id],127,szReason)

  if (g_inCustomReason[id]) {
    g_inCustomReason[id]=0
    banUser(id,g_lastCustom[id])
  }
  return PLUGIN_HANDLED
}
