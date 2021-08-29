{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- BaoLaKiswahili implementation : © <Alexander Rühl> <alex@geziefer.de>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->


<script type="text/javascript">
    var jstpl_stone = '<div id="stone_${no}" class="blk_stone" style="left: ${left}px; top: ${top}px; transform: rotate(${degree}deg);"></div>';
</script>

<div id="phase_label" class="blk_phase_label">
    <p>{LBL_PHASE}</p>
</div>
<div id="board">
    <div id="circle_{PLAYER2}_0" class="blk_seed_area" style="top: -75px">
        <div id="label_{PLAYER2}_0" class="blk_label" style="margin-left:5px;">
            <p>2</p>
        </div>
    </div>
    <div id="nyumba_opponent" class="blk_nyumba" style="left: 379px; top: 119px;"></div>
    <div id="nyumba_player" class="blk_nyumba" style="left: 482px; top: 237px;"></div>
    <!-- BEGIN circle -->
    <div id="circle_{PLAYER}_{FIELD}" class="blk_circle" style="left: {LEFT}px; top: {TOP}px;">
        <div id="label_{PLAYER}_{FIELD}" class="blk_label" style="margin-left: {MLEFT}px; margin-top: {MTOP}px;">
            <p>2</p>
        </div>
    </div>
    <!-- END circle -->
    <div id="circle_{PLAYER1}_0" class="blk_seed_area" style="top: 410px">
        <div id="label_{PLAYER1}_0" class="blk_label" style="margin-left:5px;">
            <p>2</p>
        </div>
    </div>
</div>

<div id="preferences">
    <div class="blk_auto_preference_box">
        <h3><span>{LBL_PREF_TITLE}</span></h3>
        <p><input type="checkbox" id="checkbox_kichwa_mode">&nbsp; <span>{LBL_PREF_AUTO_KICHWA}</span></p>
    </div>
</div>

{OVERALL_GAME_FOOTER}