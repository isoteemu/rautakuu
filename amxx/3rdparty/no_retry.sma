/*
NO RETRY 1.10

Plugin by Priski

Usage :
kicks and/or notifies users if they use retry

CVARS :
amx_retrytime	 - time in seconds to determine if retry was used ( default: 15 )
amx_retrykick	 - set to 1 if you want to kick retry users ( default: 0 )
amx_retryshow	 - set to 0 if you want to disable public announces about use of retry ( default: 1 )
amx_retrymsg	 - message which is send to client on kick and public announce
%s means players name ( default: "No retry allowed here, %s" )
amx_retrykickmsg - reason when kicked ( default: "DO NOT USE RETRY COMMAND" )

Changelog :

1.10.1 / 2005-09-25
- Rautakuu modifications. Use amxbans to ban player temporarily

1.10 / 2005-08-17
- whole code rewritten
- bugs fixed

1.00 / 2005-08-15
- first release


*/
#include <amxmodx>
#include <amxmisc>

#define MAX_PLAYERS 32

new pID[MAX_PLAYERS][22]


public plugin_init() {
	register_plugin("No retry","1.10","Priski")
	register_cvar("amx_retrytime","15")
	register_cvar("amx_retrykick","0")
    register_cvar("amx_retryamxbans","1")
	register_cvar("amx_retryshow","0")

	// %s is the player name
	register_cvar("amx_retrymsg","Using retry command is _not_ allowed here.")
	register_cvar("amx_retrykickmsg","DO NOT USE RETRY COMMAND")

	return PLUGIN_HANDLED
}

public client_connect(id) {
	// no bots or admin immunity users
	if ((is_user_bot(id)) || (get_user_flags(id)&ADMIN_IMMUNITY)) {
		return PLUGIN_HANDLED
	}

	// gather info
	new ip[22]
	get_user_ip(id,ip,21)


	for(new i = 1; i < MAX_PLAYERS; i++) {
		if (equal(ip, pID[i], 21)) {

			new name[34]
			get_user_name(id, name, 33)

			if (get_cvar_num("amx_retryshow")) {
				new rID[1]
				rID[0] = id
				set_task(25.0,"showMsg", id, name, 33)
			}

            if(get_cvar_num("amx_retryamxbans")) {
                new sID[50], reason[128]
                get_user_authid(id, sID, 50)
                get_cvar_string("amx_retrymsg", reason, 127)

                server_cmd("amx_ban 5 %s ^"%s^"", sID, reason)
			} else if (get_cvar_num("amx_retrykick")) {
				new uID[1], reason[128]
				uID[0] = get_user_userid(id)
				get_cvar_string("amx_retrymsg", reason, 127)

				//delayed kick
				set_task(1.0,"kick",77,uID,1)

			}

			break
		}
	}

	return PLUGIN_HANDLED;
}

public client_disconnect(id) {
	// no bots or admin immunity users are in list
	if ((is_user_bot(id)) || (get_user_flags(id)&ADMIN_IMMUNITY)) {
	return PLUGIN_HANDLED; }


	for(new i = 1; i < MAX_PLAYERS; i++) {
		if (pID[i][0] == 0) {	// found empty slot
			get_user_ip(id, pID[i], 21)
			new aID[1]
			aID[0] = i
			set_task( get_cvar_float("amx_retrytime"), "cleanID", (id + MAX_PLAYERS),aID,1)

			break
		}
	}
	return PLUGIN_HANDLED;
}


public cleanID(i[]) {
	pID[i[0]][0] = 0
}

public showMsg(playername[]) {
	new txt[128]
	get_cvar_string("amx_retrymsg", txt, 127)
	replace(txt, 127, "%s", playername)
	set_hudmessage(120, 200, 0, 0.02, 0.70, 0, 5.0, 10.0, 2.0, 0.15, 3)
	show_hudmessage(0,txt)
}

public kick(id[]) {
	new txt[128]
	get_cvar_string("amx_retrykickmsg", txt, 127)
	server_cmd("kick #%d ^"%s^"", id[0], txt)
}