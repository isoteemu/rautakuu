#include <amxmodx>
#include <engine>

new currentMap[50]

public plugin_init()
{
    register_plugin("Wad lataaja","0.0.1","Rautakuu [dot] org")
    return PLUGIN_CONTINUE
}

public plugin_precache() {
    get_mapname(currentMap, 50)
    // Jos kartan nimi se ja se, niin sitten...
    if( equali(currentMap,"cs_apartement") ) {
        // relatiivin polku /opt/hlds/cstrike/ kansiosta
        precache_generic("cs_apartement.wad")
    }
    else if( equali(currentMap,"as_riverside") ) {
        precache_generic("as_riverside.wad")
    }
    else if( equali(currentMap,"cs_wolfenstein") ) {
        precache_generic("cs_wolfenstein.wad")
        precache_generic("ad.wad")
    }
    else if( equali(currentMap,"cs_wildwest_assault") ) {
        precache_generic("cs_wildwest_assault.wad")
    }
    else if( equali(currentMap,"fy_snowplace_sym") ) {
        precache_generic("fy_snowplace_sym.wad")
    }
    else if( equali(currentMap,"de_deep6") ) {
        precache_generic("deep6.wad")
    }
    else if( equali(currentMap,"cs_iraq") ) {
        precache_generic("mr_deth.wad")
        precache_generic("yurik.wad")
    }
    else if( equali(currentMap,"cs_bikini") ) {
        precache_generic("maps/cs_bikini.txt")
        precache_generic("gfx/env/3dm_bikiniBk.tga")
        precache_generic("gfx/env/3dm_bikiniDn.tga")
        precache_generic("gfx/env/3dm_bikiniFt.tga")
        precache_generic("gfx/env/3dm_bikiniLf.tga")
        precache_generic("gfx/env/3dm_bikiniRt.tga")
        precache_generic("gfx/env/3dm_bikiniUp.tga")
        precache_generic("models/3dm_balls.mdl")
        precache_generic("models/3dm_beach.mdl")
        precache_generic("models/3dm_bikini.mdl")
        precache_generic("models/3dm_bik_brick.mdl")
        precache_generic("models/3dm_bik_glass.mdl")
        precache_generic("models/3dm_bulb.mdl")
        precache_generic("models/3dm_chair.mdl")
        precache_generic("models/3dm_chimney.mdl")
        precache_generic("models/3dm_csc.mdl")
        precache_generic("models/3dm_fastcar.mdl")
        precache_generic("models/3dm_fastcar_r.mdl")
        precache_generic("models/3dm_hossie01.mdl")
        precache_generic("models/3dm_hossie02.mdl")
        precache_generic("models/3dm_hossie03.mdl")
        precache_generic("models/3dm_hossie04.mdl")
        precache_generic("models/3dm_palm.mdl")
        precache_generic("models/3dm_parasol.mdl")
        precache_generic("models/3dm_pc.mdl")
        precache_generic("models/3dm_pc2.mdl")
        precache_generic("models/3dm_pc3.mdl")
        precache_generic("models/3dm_pc4.mdl")
        precache_generic("models/3dm_plant01.mdl")
        precache_generic("models/3dm_plant02.mdl")
        precache_generic("models/3dm_poolout.mdl")
        precache_generic("models/3dm_put.mdl")
        precache_generic("models/3dm_rainp.mdl")
        precache_generic("models/3dm_seagull.mdl")
        precache_generic("models/3dm_shark.mdl")
        precache_generic("models/3dm_shooter.mdl")
        precache_generic("models/3dm_sofa.mdl")
        precache_generic("models/3dm_statue.mdl")
        precache_generic("models/3dm_tafel.mdl")
        precache_generic("models/3dm_voetbal.mdl")
        precache_generic("models/3dm_wave.mdl")
        precache_generic("models/3dm_xdm.mdl")
        precache_generic("overviews/cs_bikini.bmp")
        precache_generic("overviews/cs_bikini.txt")
        precache_generic("sound/ambience/3dm_bik_ball.wav")
        precache_generic("sound/ambience/3dm_bik_bel.wav")
        precache_generic("sound/ambience/3dm_bik_hills.wav")
        precache_generic("sound/ambience/3dm_bik_intro.wav")
        precache_generic("sound/ambience/3dm_bik_jaws.wav")
        precache_generic("sound/ambience/3dm_bik_mac.wav")
        precache_generic("sound/ambience/3dm_bik_plons.wav")
        precache_generic("sound/ambience/3dm_bik_rad.wav")
        precache_generic("sound/ambience/3dm_bik_sea.wav")
        precache_generic("sound/ambience/3dm_bik_seagul.wav")
        precache_generic("sound/ambience/3dm_bik_sf1.wav")
        precache_generic("sound/ambience/3dm_bik_sf2.wav")
        precache_generic("sprites/3dm_branch.spr")
        precache_generic("sprites/3dm_leaves1.spr")
        precache_generic("sprites/3dm_leaves2.spr")
        precache_generic("sprites/3dm_leaves3.spr")
        precache_generic("sprites/3dm_leaves4.spr")
        precache_generic("sprites/3dm_palm_01.spr")
        precache_generic("sprites/3dm_palm_02.spr")
        precache_generic("sprites/3dm_palm_03.spr")
        precache_generic("sprites/3dm_sfa_tva.spr")
        precache_generic("sprites/3dm_sfa_tvb.spr")
        precache_generic("sprites/3dm_sfa_tvc.spr")
        precache_generic("sprites/3dm_sfa_tvd.spr")
        precache_generic("sprites/3dm_sfa_tve.spr")
        precache_generic("sprites/3dm_trunk.spr")
        precache_generic("sprites/3dm_trunk1.spr")
        precache_generic("sprites/3dm_trunk2.spr")
    }
    return PLUGIN_CONTINUE
}
