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

<div id="blk_board">
    <!-- BEGIN circle -->
    <div id="circle_{PLAYER}_{FIELD}" class="blk_circle" style="left: {LEFT}px; top: {TOP}px;">
        <div id="label_{PLAYER}_{FIELD}" class="blk_label">
            <p>2</p>
        </div>
    </div>
    <!-- END circle -->
</div>

{OVERALL_GAME_FOOTER}