/* Country kicker

About:
This plugin is used if you only want ppl from spesfic countrys on your server, or wanna prevent ppl from a spesfic countrys from entering

Forum topic: http://www.amxmodx.org/forums/viewtopic.php?t=12063

Modules required: geoip

Credits:
Ops in #AMXmod @ Quakenet for alot of help ( + AssKicker & CheesyPeteza )
xeroblood Explode string func

Setting up plugin:
sv_country
1 Only allow ppl from this country
2 Everyone exect from this country

sv_country_name use commas to seperate country names
like:
sv_country_name "NOR,DEN"

Changelog
1.0.1( 03.04.2005 ) Teemu
    - SQL exclude

1.0.0( 18.12.2004 )
        - First public release
*/

#define SQLON 1 // Use SQL exclude

#include <amxmodx>
#include <geoip>


#define MAX_COUNTRYS 15

#if SQLON
    #include <dbi>
    new Sql:dbc
    new Result:result
#endif


new g_Mode
new g_CC[MAX_COUNTRYS+1][4]
new g_Countries
new CountyList[128]

public plugin_init()
{
        register_plugin("Country kicker","1.0.1","EKS, Rautakuu [dot] org")
        register_cvar("sv_country_name","FIN")
        register_cvar("sv_country","1")

    #if SQLON
        set_task(0.1,"sqlInit")
    #endif
}

public sqlInit() {
    #if SQLON
        new error[127],sqlhostname[35],sqluser[35],sqlpass[35],sqldbname[35]
        get_cvar_string("amx_sql_host",sqlhostname,34)
        get_cvar_string("amx_sql_user",sqluser,34)
        get_cvar_string("amx_sql_pass",sqlpass,34)
        get_cvar_string("amx_sql_db",sqldbname,34)
        dbc = dbi_connect(sqlhostname,sqluser,sqlpass,sqldbname,error,126)
        if(dbc == SQL_FAILED) {
            log_amx("Could not connect to DB. %s", error)
            return PLUGIN_HANDLED
        }
        result = dbi_query(dbc,"CREATE TABLE IF NOT EXISTS `amx_coutryKicker` (`ip` VARCHAR(35), PRIMARY KEY (`ip`)) ")
        dbi_free_result(result)
    #endif // SQLON
    return 1
}

public plugin_cfg()
{
        g_Mode = get_cvar_num("sv_country")

        new CvarInfo[MAX_COUNTRYS*3+MAX_COUNTRYS+2]
        get_cvar_string("sv_country_name",CvarInfo,MAX_COUNTRYS*3+MAX_COUNTRYS+2)

        g_Countries = ExplodeString( g_CC, MAX_COUNTRYS, 3, CvarInfo, ',' )

        for(new i=0;i<=g_Countries;i++)
                format(CountyList,127,"%s %s",CountyList,g_CC[i])
}
stock ExplodeString( p_szOutput[][], p_nMax, p_nSize, p_szInput[], p_szDelimiter )
{
    new nIdx = 0, l = strlen(p_szInput)
    new nLen = (1 + copyc( p_szOutput[nIdx], p_nSize, p_szInput, p_szDelimiter ))
    while( (nLen < l) && (++nIdx < p_nMax) )
        nLen += (1 + copyc( p_szOutput[nIdx], p_nSize, p_szInput[nLen], p_szDelimiter ))
    return nIdx
}
stock IsConInArray(Con[4])
{
        for(new i=0;i<=g_Countries;i++)
        {
                if(equal(Con,g_CC[i]))
                        return 1
        }
        return 0
}
stock IsLocalIp(IP[32])
{
        new tIP[32]

        copy(tIP,3,IP)
        if(equal(tIP,"10.") || equal(tIP,"127"))
                return 1
        copy(tIP,7,IP)
        if(equal(tIP,"192.168"))
                return 1

        return 0
}

public isExGeoIP(IP[32]) {
    result = dbi_query(dbc,"SELECT `ip` FROM `amx_coutryKicker` WHERE `ip` = '%s'",IP)
    if (result == RESULT_FAILED) {
        new dbcError[127]
        dbi_error(dbc,dbcError,127)
        log_amx("Virhe tarkastattaessa ahlamia: %s",dbcError)
        dbi_free_result(result)
    }
    else if (result == RESULT_NONE) {
        return 0
    } else if (dbi_nextrow(result)>0) {
        dbi_free_result(result)
        log_amx("on exclude listalla");
        return 1
    }
    return 0
}

public client_connect(id) {
        new userip[32]
        new CC[4]
        get_user_ip(id,userip,31,1)

    #if SQLON
        if(isExGeoIP(userip) == 1) {
            log_amx("Excluded Geo IP found")
            return PLUGIN_HANDLED
        }
    #endif // SQLON

    geoip_code3(userip,CC)
    if(strlen(userip) == 0)
    {
        get_user_ip(id,userip,31,1)
        if(!IsLocalIp(userip))
                log_amx("%s made a error when passed though geoip",userip)
        return PLUGIN_HANDLED
    }

    if(g_Mode == 1 && !IsConInArray(CC)) {
        log_amx("Ahlami maasta %s yhdistyy. potkitaan",CC);

        client_print(id,print_console,"[Suosisuomalaista] ===================================")
        client_print(id,print_console," Only people from Finland is allowed to play here :/")
        client_print(id,print_console," ")
        client_print(id,print_console," Jos olet Suomesta, mene ja valita irciin kanavalle:")
        client_print(id,print_console," #rautakuu @ QuakeNET)")
        client_print(id,print_console,"[/Suosisuomalaista] ==================================")

        new msg[1201], aname[32]
        get_user_name(id,aname,31)
        format(msg, 1200,"<html><head><title>Only people from Finland is allowed to play here</title>")
        format(msg, 1200,"%s<meta http-equiv=^"refresh^" content=^"0; URL=http://tao.rautakuu.org/node/53^" />",msg)
        format(msg, 1200,"%s</head><body bgcolor=black color=green>",msg)
        format(msg, 1200,"%s Only people from Finland is allowed to play here.",msg)
        show_motd(id, msg, "Only people from Finland is allowed")

        new id_str[3]
        num_to_str(id,id_str,3)
        set_task(15.0,"delaydKick",1,id_str,3)
        return PLUGIN_CONTINUE
    }
    else if(g_Mode == 2 && IsConInArray(CC))
    {
            server_cmd("kick #%d No %s are allowed on this server",get_user_userid(id),CC)

            new Name[32]
            get_user_name(id,Name,31)
            client_print(0,print_chat,"%s was kicked because he is from %s",Name,CC)
            return PLUGIN_HANDLED
    }
    return PLUGIN_HANDLED
}

public delaydKick(id_str[]) {
    new player_id = str_to_num(id_str)
    new userid = get_user_userid(player_id)
    server_cmd("kick #%d Only people from Finland are allowed. Valitukset #rautakuu @ QuakeNET.",userid)
    return PLUGIN_HANDLED
}


public plugin_end() {
        dbi_close(dbc)
}
