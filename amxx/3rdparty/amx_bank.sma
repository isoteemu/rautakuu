#define SQLON 0 // 1 = Use SQL | 0 = Use file

#include <amxmodx>
#include <amxmisc>
#include <cstrike>

new bool:canuse[33] = false
new interest[33] = 0
new bankfees = 0

#if SQLON
	#include <dbi>
#else
	#include <vault>
#endif

#if SQLON
	new Sql:dbc
	new Result:result
#else
	new allowfilepath[251]
#endif

public plugin_init()
{
	register_plugin("AMX Bank","1.5-rq","twistedeuphoria")
	register_concmd("bank_create","bank_create",ADMIN_USER,"<opening amount> :Create a new bank account.")
	register_concmd("bank_close","bank_close",ADMIN_CVAR,"Close the AMX Bank.")
	register_concmd("bank_open","bank_open",ADMIN_CVAR,"Open the AMX Bank for business.")
	register_concmd("bank_amount","bank_amount",ADMIN_USER,"Display the amount of money you have in the bank.")
	register_concmd("bank_deposit","bank_deposit",ADMIN_USER,"<amount> :Deposit money into your bank account.")
	register_concmd("bank_withdraw","bank_withdrawl",ADMIN_USER,"<amount> :Withdraw money from your bank account.")
	register_concmd("maxdep","deposit_maximum",ADMIN_USER,"Deposit all your money.")
	register_concmd("maxwit","withdrawl_maximum",ADMIN_USER,"Withdrawl until you have $16000 or your bank account is empty.")
	register_clcmd("say","say_cheese")
	register_clcmd("say_team","say_cheese")
	register_cvar("bank_min_opening","1000")
	register_cvar("bank_state","1")
	register_cvar("bank_min_players","2")
	register_cvar("bank_restrict","0") // 0 = All user can use the bank 1 = Only users defined in file or SQL
	register_cvar("bank_interest_rounds","15")
	register_cvar("bank_interest_rate","0.01")
	register_cvar("bank_fees_base","100")  //Base bank fee in $
	register_cvar("bank_fees_increase","10") //Added to the base fee for each transaction in a round

    // Rautakuu mysli
    register_cvar("rq_sql_host","rautakuu.org")
    register_cvar("rq_sql_user","user")
    register_cvar("rq_sql_pass","p4ssw0rd")
    register_cvar("rq_sql_db","hlds")
    // /Rautakuu mysli

	register_logevent("giveinterest",2,"0=World triggered","1=Round_Start")
	#if SQLON
		set_task(3.0,"sqlinit")
	#else
		new directory[201]
		get_configsdir(directory,200)
		if(get_cvar_num("bank_restrict") == 1)
		{
			format(allowfilepath,250,"%s/bankusers.ini",directory)
			if(!file_exists(allowfilepath))
			{
				new writestr[101]
				format(writestr,100,";Put all users who can use the bank in here.")
				write_file(allowfilepath,writestr)
			}
		}
	#endif
}

public say_cheese(id)
{
	new said[191]
	read_args(said,190)
	remove_quotes(said)
	if(said[0] == 'm')
	{
		if(equali(said,"maxwit"))
		{
			withdrawl_maximum(id)
			return PLUGIN_HANDLED
		}
		if(equali(said,"maxdep"))
		{
			deposit_maximum(id)
			return PLUGIN_HANDLED
		}
	}
	else if(said[0] == 'b')
	{
		if(containi(said,"bank_") != -1)
		{
			if(containi(said,"bank_create") != -1)
			{
				replace(said,190,"bank_create","")
				new amount = str_to_num(said)
				said_create(id,amount)
				return PLUGIN_HANDLED
			}
			if(containi(said,"bank_amount") != -1)
			{
				said_amount(id)
				return PLUGIN_HANDLED
			}
			if(containi(said,"bank_withdraw") != -1)
			{
				replace(said,190,"bank_withdraw","")
				new amount = str_to_num(said)
				said_withdraw(id,amount)
				return PLUGIN_HANDLED
			}
			if(containi(said,"bank_deposit") != -1)
			{
				replace(said,190,"bank_deposit","")
				new amount = str_to_num(said)
				said_deposit(id,amount)
				return PLUGIN_HANDLED
			}
		}
	}
	return PLUGIN_CONTINUE
}

public said_create(id,amountnum)
{
	if(canuse[id] == false)
	{
		client_print(id,print_chat,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	if(get_cvar_num("bank_state"))
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			new curmoney,neededmoney
			neededmoney = get_cvar_num("bank_min_opening")
			if(amountnum < neededmoney)
			{
				client_print(id,print_chat,"You need to deposit at least $%d to start an account.",neededmoney)
				return PLUGIN_HANDLED
			}
			curmoney = cs_get_user_money(id) - bankfees
			if(amountnum > curmoney)
			{
				if(amountnum < cs_get_user_money(id))
					client_print(id,print_chat,"You don't have enough money to cover the bank fee of %d and your deposit.",bankfees)
				else
					client_print(id,print_chat,"You don't have that much money.")
				return PLUGIN_HANDLED
			}
			#if SQLON
				new sid[35]
				get_user_authid(id,sid,34)
				cs_set_user_money(id,curmoney-amountnum,1)
				result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
				if(result != RESULT_FAILED)
				{
					client_print(id,print_chat,"You already have a bank account!")
					dbi_free_result(result)
					return PLUGIN_HANDLED
				}
				dbi_free_result(result)
				result = dbi_query(dbc,"INSERT INTO bank VALUES ( '%s' , '%d')",sid,amountnum)
				dbi_free_result(result)
			#else
				new sid[35],key[51]
				get_user_authid(id,sid,34)
				format(key,50,"%s_account",sid)
				if(vaultdata_exists(key))
				{
					client_print(id,print_chat,"You already have a bank account!")
					return PLUGIN_HANDLED
				}
				new saveamstr[21]
				num_to_str(amountnum,saveamstr,20)
				set_vaultdata(key,saveamstr)
			#endif
			cs_set_user_money(id,cs_get_user_money(id)-bankfees)
			if(bankfees > 0)
				client_print(id,print_chat,"You paid %d in bank fees.",bankfees)
			bankfees += get_cvar_num("bank_fees_increase")
			client_print(id,print_chat,"Bank account created successfully. Your account has $%d in it.",amountnum)
		}
		else
			client_print(id,print_chat,"There are not enough players connected to use the bank.")
	}
	else
		client_print(id,print_chat,"The bank is closed!")
	return PLUGIN_HANDLED
}

public said_amount(id)
{
	if(canuse[id] == false)
	{
		client_print(id,print_chat,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	new sid[35]
	get_user_authid(id,sid,34)
	#if SQLON
		result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
		if(result == RESULT_FAILED)
		{
			client_print(id,print_chat,"You do not have a bank account.")
			dbi_free_result(result)
			return PLUGIN_HANDLED
		}
		dbi_nextrow(result)
		new amnum
		amnum = dbi_result(result,"amount")
		client_print(id,print_chat,"You have $%d in your bank account.",amnum)
		dbi_free_result(result)
	#else
		new key[51]
		format(key,50,"%s_account",sid)
		if(vaultdata_exists(key))
		{
			new saveamnum,saveamstr[21]
			get_vaultdata(key,saveamstr,20)
			saveamnum = str_to_num(saveamstr)
			client_print(id,print_chat,"You have $%d in your bank account.",saveamnum)
			return PLUGIN_HANDLED
		}
		else
			client_print(id,print_chat,"You do not have a bank account.")
	#endif
	return PLUGIN_HANDLED
}

public said_withdraw(id,amount)
{
	if(canuse[id] == false)
	{
		client_print(id,print_chat,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	if(get_cvar_num("bank_state"))
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			new sid[35]
			get_user_authid(id,sid,34)
			#if SQLON
				result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
				if(result == RESULT_FAILED)
				{
					client_print(id,print_chat,"You do not have a bank account.")
					dbi_free_result(result)
					return PLUGIN_HANDLED
				}
				dbi_nextrow(result)
				new samount
				samount = dbi_result(result,"amount")
				new amn,maxam
				amn = amount
				maxam = 16000 - cs_get_user_money(id)
				if(amn > maxam)
					amn = maxam
				if(amn > samount)
				{
					client_print(id,print_chat,"There is not enough money in your bank account.")
					dbi_free_result(result)
					return PLUGIN_HANDLED
				}
				samount -= amn
				dbi_free_result(result)
				cs_set_user_money(id,cs_get_user_money(id) + amn,1)
				if((samount - bankfees) > 0)
					samount -= bankfees
				else
					cs_set_user_money(id,cs_get_user_money(id) - bankfees,1)
				result = dbi_query(dbc,"UPDATE bank SET amount = '%d' WHERE sid = '%s'",samount,sid)
				dbi_free_result(result)
				if(bankfees > 0)
					client_print(id,print_chat,"You paid %d in bank fees.",bankfees)
				bankfees += get_cvar_num("bank_fees_increase")
				client_print(id,print_chat,"You have withdrawn $%d from your bank account. You now have $%d in your account.",amn,samount)
			#else
				new key[51]
				format(key,50,"%s_account",sid)
				if(vaultdata_exists(key))
				{
					new sam,saveam,saveamstr[21]
					sam = amount
					get_vaultdata(key,saveamstr,20)
					saveam = str_to_num(saveamstr)
					if(sam > saveam)
					{
						client_print(id,print_chat,"There is not enough money in your bank account.")
						return PLUGIN_HANDLED
					}
					new curmoney = 16000 - cs_get_user_money(id)
					if(sam > curmoney)
						sam = curmoney
					saveam -= sam
					cs_set_user_money(id,cs_get_user_money(id) + sam)
					if((saveam - bankfees) > 0)
						saveam -= bankfees
					else
						cs_set_user_money(id,cs_get_user_money(id) - bankfees)
					num_to_str(saveam,saveamstr,20)
					set_vaultdata(key,saveamstr)
					if(bankfees > 0)
						client_print(id,print_chat,"You paid %d in bank fees.",bankfees)
					bankfees += get_cvar_num("bank_fees_increase")
					client_print(id,print_chat,"You have withdrawn $%d from your bank account. You now have $%d in your account.",sam,saveam)
				}
				else
					client_print(id,print_chat,"You do not have a bank account.")
			#endif
		}
		else
			client_print(id,print_chat,"There are not enough players connected to use the bank.")
	}
	else
		client_print(id,print_chat,"The bank is closed.")
	return PLUGIN_HANDLED
}

public said_deposit(id,damount)
{
	if(canuse[id] == false)
	{
		client_print(id,print_chat,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	if(get_cvar_num("bank_state"))
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			new curmoney
			curmoney = cs_get_user_money(id)
			if(damount > curmoney)
			{
				client_print(id,print_chat,"You don't have that much money.")
				return PLUGIN_HANDLED
			}
			#if SQLON
				new sid[35],samount
				get_user_authid(id,sid,34)
				result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
				if(result == RESULT_FAILED)
				{
					console_print(id,"You do not have a bank account.")
					dbi_free_result(result)
					return PLUGIN_HANDLED
				}
				dbi_nextrow(result)
				samount = dbi_result(result,"amount")
				samount += damount
				samount -= bankfees
				bankfees += get_cvar_num("bank_fees_increase")
				dbi_free_result(result)
				result = dbi_query(dbc,"UPDATE bank SET amount = '%d' WHERE sid = '%s'",samount,sid)
				dbi_free_result(result)
				cs_set_user_money(id,curmoney-damount,1)
				client_print(id,print_chat,"You have deposited $%d in your bank account. You now have $%d in your account.",damount,samount)
			#else
				new sid[35],key[51]
				get_user_authid(id,sid,34)
				format(key,50,"%s_account",sid)
				if(vaultdata_exists(key))
				{
					new saveamnum,saveamstr[21]
					get_vaultdata(key,saveamstr,20)
					saveamnum = str_to_num(saveamstr)
					saveamnum += damount
					saveamnum -= bankfees
					bankfees += get_cvar_num("bank_fees_increase")
					num_to_str(saveamnum,saveamstr,20)
					set_vaultdata(key,saveamstr)
					cs_set_user_money(id,curmoney-damount,1)
					client_print(id,print_chat,"You have deposited $%d in your bank account. You now have $%d in your account.",damount,saveamnum)
					return PLUGIN_HANDLED
				}
				else
					client_print(id,print_chat,"You do not have a bank account.")
			#endif
		}
		else
			client_print(id,print_chat,"There are not enough players connected to use the bank.")
	}
	else
		client_print(id,print_chat,"The bank is closed.")
	return PLUGIN_HANDLED
}

public giveinterest()
{
	if(get_cvar_num("bank_state") == 1)
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			bankfees = get_cvar_num("bank_fees_base")
			new Float:rate = get_cvar_float("bank_interest_rate")
			new rounds = get_cvar_num("bank_interest_rounds")
			for(new i = 1;i<=get_playersnum();i++)
			{
				if(is_user_alive(i))
				{
					if(canuse[i] == true)
					{
						interest[i]++
						if(interest[i] >= rounds)
						{
							interest[i] = 0
							#if SQLON
								new allowed = 16000 - cs_get_user_money(i)
								new sid[35],samount
								get_user_authid(i,sid,34)
								result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
								if(result != RESULT_NONE)
								{
									dbi_nextrow(result)
									samount = dbi_result(result,"amount")
									new Float:give = floatmul(rate,float(samount))
									new givint = floatround(give)
									if(givint > 0)
									{
										if(givint <= allowed)
										{
											cs_set_user_money(i,cs_get_user_money(i)+givint)
											client_print(i,print_chat,"You were given $%d in interest.",givint)
										}
										if(givint > allowed)
										{
											new dep = givint - allowed
											client_print(i,print_chat,"You were given $%d in interest $%d of which went into your account.",givint,dep)
											cs_set_user_money(i,16000)
											samount += dep
											result = dbi_query(dbc,"UPDATE bank SET amount = '%d' WHERE sid = '%s'",samount,sid)
										}
									}
								}
								dbi_free_result(result)
							#else
								new sid[35],key[51]
								get_user_authid(i,sid,34)
								format(key,50,"%s_account",sid)
								if(vaultdata_exists(key))
								{
									new saveamnum, saveamstr[21]
									new allowed = 16000 - cs_get_user_money(i)
									get_vaultdata(key,saveamstr,20)
									saveamnum = str_to_num(saveamstr)
									new Float:give = floatmul(rate,float(saveamnum))
									new givint = floatround(give)
									if(givint > 0)
									{
										if(givint <= allowed)
										{
											cs_set_user_money(i,cs_get_user_money(i)+givint)
											client_print(i,print_chat,"You were given $%d in interest.",givint)
										}
										if(givint > allowed)
										{
											new dep = givint - allowed
											client_print(i,print_chat,"You were given $%d in interest $%d of which went into your account.",givint,dep)
											cs_set_user_money(i,16000)
											saveamnum += dep
											num_to_str(saveamnum,saveamstr,20)
											set_vaultdata(key,saveamstr)
										}
									}
								}
							#endif
						}
					}
				}
			}
		}
	}
}



public client_putinserver(id)
{
	interest[id] = 0
	canuse[id] = false
	switch(get_cvar_num("bank_restrict"))
	{
		case 0:
		{
			canuse[id] = true
		}
		case 1:
		{
			if(access(id,ADMIN_CHAT))
				canuse[id] = true
			else
				canuse[id] = false
		}
		case 2:
		{
			canuse[id] = false
			new sid[35]
			get_user_authid(id,sid,34)
			#if SQLON
				result = dbi_query(dbc,"SELECT * FROM `drupal_steamids` WHERE steamid = '%s'",sid)
				if(result == RESULT_NONE)
					canuse[id] = false
				else
					canuse[id] = true
				dbi_free_result(result)
			#else
				new retstr[35],a,i
				for(i=0;read_file(allowfilepath,i,retstr,34,a) != 0;i++)
				{
					if(equali(sid,retstr))
					canuse[id] = true
				}
			#endif
		}
	}
}

public client_disconnect(id)
{
	canuse[id] = false
	interest[id] = 0
}

public deposit_maximum(id)
{
	if(canuse[id] == false)
	{
		client_print(id,print_chat,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	if(get_cvar_num("bank_state"))
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			#if SQLON
				new curmoney
				curmoney = cs_get_user_money(id)
				new sid[35],samount
				get_user_authid(id,sid,34)
				result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
				if(result == RESULT_FAILED)
				{
					client_print(id,print_chat,"You do not have a bank account.")
					dbi_free_result(result)
					return PLUGIN_HANDLED
				}
				dbi_nextrow(result)
				samount = dbi_result(result,"amount")
				samount += curmoney
				samount -= bankfees
				bankfees += get_cvar_num("bank_fees_increase")
				result = dbi_query(dbc,"UPDATE bank SET amount = '%d' WHERE sid = '%s'",samount,sid)
				dbi_free_result(result)
				cs_set_user_money(id,0,1)
				client_print(id,print_chat,"You have deposited $%d in your bank account. You now have $%d in your account.",curmoney,samount)
			#else
				new sid[35],key[51]
				get_user_authid(id,sid,34)
				format(key,50,"%s_account",sid)
				if(vaultdata_exists(key))
				{
					new saveamnum, saveamstr[21], curmoney
					curmoney = cs_get_user_money(id)
					if(curmoney <= 0)
					{
						client_print(id,print_chat,"You have no money to deposit.")
						return PLUGIN_HANDLED
					}
					get_vaultdata(key,saveamstr,20)
					saveamnum = str_to_num(saveamstr)
					saveamnum += curmoney
					saveamnum -= bankfees
					bankfees += get_cvar_num("bank_fees_increase")
					num_to_str(saveamnum,saveamstr,20)
					set_vaultdata(key,saveamstr)
					cs_set_user_money(id,0,1)
					client_print(id,print_chat,"You have deposited $%d in your bank account. You now have $%d in your account.",curmoney,saveamnum)
				}
				else
					client_print(id,print_chat,"You do not have a bank account.")
			#endif
		}
		else
			client_print(id,print_chat,"There are not enough players connected to use the bank.")
	}
	else
		client_print(id,print_chat,"The bank is closed.")
	return PLUGIN_HANDLED
}

public withdrawl_maximum(id)
{
	if(canuse[id] == false)
	{
		client_print(id,print_chat,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	if(get_cvar_num("bank_state"))
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			#if SQLON
				new sid[35]
				get_user_authid(id,sid,34)
				result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
				if(result == RESULT_FAILED)
				{
					console_print(id,"You do not have a bank account.")
					return PLUGIN_HANDLED
				}
				dbi_nextrow(result)
				new samount
				samount = dbi_result(result,"amount")
				new maxam
				maxam = 16000 - cs_get_user_money(id)
				if(maxam > samount)
					maxam = samount
				samount -= maxam
				cs_set_user_money(id,cs_get_user_money(id) + maxam,1)
				if((samount - bankfees) > 0)
					samount -= bankfees
				else
					cs_set_user_money(id,cs_get_user_money(id) - bankfees,1)
				if(bankfees > 0)
					client_print(id,print_chat,"You paid %d in bank fees.",bankfees)
				bankfees += get_cvar_num("bank_fees_increase")
				dbi_free_result(result)
				result = dbi_query(dbc,"UPDATE bank SET amount = '%d' WHERE sid = '%s'",samount,sid)
				dbi_free_result(result)
				client_print(id,print_chat,"You have withdrawn $%d from your bank account. You now have $%d in your account.",maxam,samount)
			#else
				new sid[35],key[51]
				get_user_authid(id,sid,34)
				format(key,50,"%s_account",sid)
				if(vaultdata_exists(key))
				{
					new saveamnum,saveamstr[21],mtw
					mtw = 16000 - cs_get_user_money(id)
					if(mtw <= 0)
					{
						client_print(id,print_chat,"You already have the maximum amount of money!")
						return PLUGIN_HANDLED
					}
					get_vaultdata(key,saveamstr,20)
					saveamnum = str_to_num(saveamstr)
					if(mtw > saveamnum)
						mtw = saveamnum
					saveamnum -= mtw
					cs_set_user_money(id,cs_get_user_money(id) + mtw,1)
					if((saveamnum - bankfees) > 0)
						saveamnum -= bankfees
					else
						cs_set_user_money(id,cs_get_user_money(id) - bankfees,1)
					if(bankfees > 0)
						client_print(id,print_chat,"You paid %d in bank fees.",bankfees)
					bankfees += get_cvar_num("bank_fees_increase")
					num_to_str(saveamnum,saveamstr,20)
					set_vaultdata(key,saveamstr)
					client_print(id,print_chat,"You have withdrawn $%d from your bank account. You now have $%d in your account.",mtw,saveamnum)
				}
				else
					client_print(id,print_chat,"You do not have a bank account.")
			#endif
		}
		else
			client_print(id,print_chat,"There are not enough players connected to use the bank.")
	}
	else
		client_print(id,print_chat,"The bank is closed.")
	return PLUGIN_HANDLED
}

public bank_amount(id)
{
	if(canuse[id] == false)
	{
		console_print(id,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	new sid[35]
	get_user_authid(id,sid,34)
	#if SQLON
		result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
		if(result == RESULT_FAILED)
		{
			console_print(id,"You do not have a bank account.")
			return PLUGIN_HANDLED
		}
		dbi_nextrow(result)
		new amnum
		amnum = dbi_result(result,"amount")
		console_print(id,"You have $%d in your bank account.",amnum)
	#else
		new key[51]
		format(key,50,"%s_account",sid)
		if(vaultdata_exists(key))
		{
			new saveamnum,saveamstr[21]
			get_vaultdata(key,saveamstr,20)
			saveamnum = str_to_num(saveamstr)
			console_print(id,"You have $%d in your bank account.",saveamnum)
			return PLUGIN_HANDLED
		}
		else
			console_print(id,"You do not have a bank account.")
	#endif
	return PLUGIN_HANDLED
}

public bank_open(id,level,cid)
{
	if(!cmd_access(id,level,cid,1))
		return PLUGIN_HANDLED
	if(get_cvar_num("bank_state"))
		console_print(id,"The AMX bank is already open.")
	else
	{
		console_cmd(id,"amx_cvar bank_state 1")
		console_print(id,"The bank is now open.")
		client_print(0,print_chat,"The bank is now open for business.")
	}
	return PLUGIN_HANDLED
}

public bank_close(id,level,cid)
{
	if(!cmd_access(id,level,cid,1))
		return PLUGIN_HANDLED
	if(get_cvar_num("bank_state"))
	{
		console_cmd(id,"amx_cvar bank_state 0")
		console_print(id,"The bank has been closed.")
		client_print(0,print_chat,"The bank has closed.")
	}
	else
		console_print(id,"The bank is already closed.")
	return PLUGIN_HANDLED
}

public sqlinit()
{
	#if SQLON
		new error[32],sqlhostname[35],sqluser[35],sqlpass[35],sqldbname[35]
		get_cvar_string("rq_sql_host",sqlhostname,34)
		get_cvar_string("rq_sql_user",sqluser,34)
		get_cvar_string("rq_sql_pass",sqlpass,34)
		get_cvar_string("rq_sql_db",sqldbname,34)
		dbc = dbi_connect(sqlhostname,sqluser,sqlpass,sqldbname,error,31)
		if(dbc == SQL_FAILED)
		{
			server_print("Could not connect.")
			return PLUGIN_HANDLED
		}

		//result = dbi_query(dbc,"CREATE TABLE IF NOT EXISTS `bank` (`sid` VARCHAR(35), `amount` BIGINT(20))")
		//dbi_free_result(result)
		//result = dbi_query(dbc,"CREATE TABLE IF NOT EXISTS `drupal_steamids` (`steamid` VARCHAR(35))")
		//dbi_free_result(result)

	#endif
	return 1
}

public bank_create(id)
{
	if(canuse[id] == false)
	{
		console_print(id,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	if(get_cvar_num("bank_state"))
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			new amount[31],amountnum,curmoney,neededmoney
			read_args(amount,31)
			amountnum = str_to_num(amount)
			neededmoney = get_cvar_num("bank_min_opening")
			if(amountnum < neededmoney)
			{
				console_print(id,"You need to deposit at least $%d to start an account.",neededmoney)
				return PLUGIN_HANDLED
			}
			curmoney = cs_get_user_money(id) - bankfees
			if(amountnum > curmoney)
			{
				if(amountnum < cs_get_user_money(id))
					console_print(id,"You don't have enough money to cover the bank fee of %d and your deposit.",bankfees)
				else
					console_print(id,"You don't have that much money.")
				return PLUGIN_HANDLED
			}
			#if SQLON
				new sid[35]
				get_user_authid(id,sid,34)
				cs_set_user_money(id,curmoney-amountnum,1)
				result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
				if(result != RESULT_NONE)
				{
					console_print(id,"You already have a bank account!")
					return PLUGIN_HANDLED
				}
				dbi_free_result(result)
				result = dbi_query(dbc,"INSERT INTO bank VALUES ( '%s' , '%d')",sid,amountnum)
				dbi_free_result(result)
			#else
				new sid[35],key[51]
				get_user_authid(id,sid,34)
				format(key,50,"%s_account",sid)
				if(vaultdata_exists(key))
				{
					console_print(id,"You already have a bank account!")
					return PLUGIN_HANDLED
				}
				new saveamstr[21]
				num_to_str(amountnum,saveamstr,20)
				set_vaultdata(key,saveamstr)
			#endif
			cs_set_user_money(id,cs_get_user_money(id)-bankfees)
			if(bankfees > 0)
				client_print(id,print_chat,"You paid %d in bank fees.",bankfees)
			bankfees += get_cvar_num("bank_fees_increase")
			console_print(id,"Bank account created successfully. Your account has $%d in it.",amountnum)
		}
		else
			console_print(id,"There are not enough players connected to use the bank.")
	}
	else
		console_print(id,"The bank is closed!")
	return PLUGIN_HANDLED
}

public bank_withdrawl(id)
{
	if(canuse[id] == false)
	{
		console_print(id,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	if(get_cvar_num("bank_state"))
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			new sid[35]
			get_user_authid(id,sid,34)
			#if SQLON
				result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
				if(result == RESULT_FAILED)
				{
					console_print(id,"You do not have a bank account.")
					return PLUGIN_HANDLED
				}
				dbi_nextrow(result)
				new samount
				samount = dbi_result(result,"amount")
				new ams[9],amn,maxam
				read_args(ams,8)
				amn = str_to_num(ams)
				maxam = 16000 - cs_get_user_money(id)
				if(amn > maxam)
					amn = maxam
				if(amn > samount)
				{
					console_print(id,"There is not enough money in your bank account.")
					return PLUGIN_HANDLED
				}
				samount -= amn
				cs_set_user_money(id,cs_get_user_money(id) + amn,1)
				if((samount - bankfees) > 0)
					samount -= bankfees
				else
					cs_set_user_money(id,cs_get_user_money(id) - bankfees,1)
				dbi_free_result(result)
				result = dbi_query(dbc,"UPDATE bank SET amount = '%d' WHERE sid = '%s'",samount,sid)
				dbi_free_result(result)
				if(bankfees > 0)
					console_print(id,"You paid %d in bank fees.",bankfees)
				bankfees += get_cvar_num("bank_fees_increase")
				console_print(id,"You have withdrawn $%d from your bank account. You now have $%d in your account.",amn,samount)
			#else
				new key[51]
				format(key,50,"%s_account",sid)
				if(vaultdata_exists(key))
				{
					new sam,samstr[9],saveam,saveamstr[21]
					read_args(samstr,8)
					sam = str_to_num(samstr)
					get_vaultdata(key,saveamstr,20)
					saveam = str_to_num(saveamstr)
					if(sam > saveam)
					{
						console_print(id,"There is not enough money in your bank account.")
						return PLUGIN_HANDLED
					}
					new curmoney = 16000 - cs_get_user_money(id)
					if(sam > curmoney)
						sam = curmoney
					saveam -= sam
					cs_set_user_money(id,cs_get_user_money(id) + sam)
					if((saveam - bankfees) > 0)
						saveam -= bankfees
					else
						cs_set_user_money(id,cs_get_user_money(id) + sam)
					num_to_str(saveam,saveamstr,20)
					set_vaultdata(key,saveamstr)
					if(bankfees > 0)
						console_print(id,"You paid %d in bank fees.",bankfees)
					bankfees += get_cvar_num("bank_fees_increase")
					console_print(id,"You have withdrawn $%d from your bank account. You now have $%d in your account.",sam,saveam)
				}
				else
					console_print(id,"You do not have a bank account.")
			#endif
		}
		else
			console_print(id,"There are not enough players connected to use the bank.")
	}
	else
		console_print(id,"The bank is closed.")
	return PLUGIN_HANDLED
}

public bank_deposit(id)
{
	if(canuse[id] == false)
	{
		console_print(id,"You are not allowed to use the bank.")
		return PLUGIN_HANDLED
	}
	if(get_cvar_num("bank_state"))
	{
		if(get_playersnum() >= get_cvar_num("bank_min_players"))
		{
			new damounts[9],damount,curmoney
			read_args(damounts,8)
			damount = str_to_num(damounts)
			curmoney = cs_get_user_money(id)
			if(damount > curmoney)
			{
				console_print(id,"You don't have that much money.")
				return PLUGIN_HANDLED
			}
			#if SQLON
				new sid[35],samount
				get_user_authid(id,sid,34)
				result = dbi_query(dbc,"SELECT * FROM bank WHERE sid = '%s'",sid)
				if(result == RESULT_FAILED)
				{
					console_print(id,"You do not have a bank account.")
					return PLUGIN_HANDLED
				}
				dbi_nextrow(result)
				samount = dbi_result(result,"amount")
				samount += damount
				samount -= bankfees
				if(bankfees > 0)
					console_print(id,"You paid %d in bank fees.",bankfees)
				bankfees += get_cvar_num("bank_fees_increase")
				dbi_free_result(result)
				result = dbi_query(dbc,"UPDATE bank SET amount = '%d' WHERE sid = '%s'",samount,sid)
				dbi_free_result(result)
				cs_set_user_money(id,curmoney-damount,1)
				console_print(id,"You have deposited $%d in your bank account. You now have $%d in your account.",damount,samount)
			#else
				new sid[35],key[51]
				get_user_authid(id,sid,34)
				format(key,50,"%s_account",sid)
				if(vaultdata_exists(key))
				{
					new saveamnum,saveamstr[21]
					get_vaultdata(key,saveamstr,20)
					saveamnum = str_to_num(saveamstr)
					saveamnum += damount
					saveamnum -= bankfees
					if(bankfees > 0)
						console_print(id,"You paid %d in bank fees.",bankfees)
					bankfees += get_cvar_num("bank_fees_increase")
					num_to_str(saveamnum,saveamstr,20)
					set_vaultdata(key,saveamstr)
					cs_set_user_money(id,curmoney-damount,1)
					console_print(id,"You have deposited $%d in your bank account. You now have $%d in your account.",damount,saveamnum)
					return PLUGIN_HANDLED
				}
				else
					console_print(id,"You do not have a bank account.")
			#endif
		}
		else
			console_print(id,"There are not enough players connected to use the bank.")
	}
	else
		console_print(id,"The bank is closed.")
	return PLUGIN_HANDLED
}