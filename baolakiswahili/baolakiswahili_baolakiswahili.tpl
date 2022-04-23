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
    var jstpl_player_side = '<div id="player_side_${player_id}" class="blk_player_side"></div>';
    var jstpl_nyumba_message = '<div id="nyumba_message_${player_id}" class="blk_nyumba_message"></div>';
</script>

<div id="phase_label" class="blk_phase_label">
    <p>{LBL_PHASE}</p>
</div>

<div id="board" class="board-nyumba">
    <div id="circle_{PLAYER2}_0" class="blk_seed_area" style="top: -75px">
        <div id="label_{PLAYER2}_0" class="blk_label" style="margin-left:5px;">
            <p>2</p>
        </div>
    </div>
    <!-- BEGIN circle -->
    <div id="circle_{PLAYER}_{FIELD}" class="blk_circle" style="left: {LEFT}px; top: {TOP}px;">
        <div id="label_{PLAYER}_{FIELD}" class="blk_label" style="margin-left: {MLEFT}px; margin-top: {MTOP}px;">
            <p>2</p>
        </div>
        <div class="blk_board_labeling">
            {LABEL}
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

<div id="gamelog">
    <div class="blk_gamelog_box">
        <div class="blk_gamelog_title">
            <h3><span>{LBL_GAMELOG_TITLE}</span></h3>
        </div>
        <div class="blk_gamelog_option">
            <input type="checkbox" id="checkbox_board_labeling">&nbsp; <span>{LBL_SHOW_BOARD_LABELING}</span>
        </div>
        <div>
            <textarea id="gamelog_content" rows="10" cols="90" readonly="true">
            </textarea>
            <p><span id="gamelog_key">{LBL_GAMELOG_KEY}</span></p>
        </div>
    </div>
</div>

{OVERALL_GAME_FOOTER}