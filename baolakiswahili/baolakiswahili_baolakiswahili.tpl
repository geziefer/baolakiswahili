{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- BaoLaKiswahili implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    baolakiswahili_baolakiswahili.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->


<script type="text/javascript">
    var jstpl_stone='<div id="stone_${no}" class="stone" style="transform: rotate(${degree}deg);"></div>';
</script>  

<div id="board">
    <!-- BEGIN circle -->
    <div id="circle_{PLAYER}_{FIELD}" class="circle" style="left: {LEFT}px; top: {TOP}px;">
        <div id="label_{PLAYER}_{FIELD}" class="label" >
            <p>2</p>
        </div>
     </div>
    <!-- END circle -->
    <!-- BEGIN label -->
<!-- END label -->
</div>

{OVERALL_GAME_FOOTER}
