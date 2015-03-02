<?php

    if(!defined("IN_MYBB")) { die("Unauthorized"); }
    
    function diceroll_info() {
        return array(
            "name"                  =>                  "TJs Dice Roll Plugin",
            "Description"           =>                  "A plugin that creates a permenant dice-roll to attach to posts (Rolls CANNOT be edited/deleted)",
            "Website"               =>                  "http://101stdivision.net",
            "author"                =>                  "NINTHTJ",
            "authorsite"            =>                  "http://101stdivision.net",
            "version"               =>                  "1.0",
            "guid"                  =>                  "",
            "compatibility"         =>                  "18*"
        );
    }
    
    function diceroll_activate() {
        global $db;
        $diceroll_group = array(
            "gid"                   =>                  "",
            "name"                  =>                  "TJsDiceRollPlugin",
            "title"                 =>                  "Dice Roller",
            "description"           =>                  "Dice Roll Settings",
            "disporder"             =>                  "1",
            "isdefault"             =>                  "0"
        );
        
        $db -> insert_query("settinggroups",$diceroll_group);
        $gid = $db -> insert_id();
        
        $diceroll_setting = array(
            'sid'                   =>                  'NULL',
            "name"                  =>                  "diceroll_enabled",
            "title"                 =>                  "Dice roll system enabled?",
            "description"           =>                  "If you set this option to yes, dice rolls will be available on your board",
            "optionscode"           =>                  "yesno",
            "value"                 =>                  "1",
            "disporder"             =>                  1,
            "gid"                   =>                  intval($gid)
        );
        
        $db -> insert_query("settings",$diceroll_setting);
        rebuild_settings();
        
        $db -> add_column("posts","diceroll","text");  
    }
    
    function diceroll_uninstall() {
        global $db;
        $db -> query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name IN ('diceroll_enable')");
        $db -> query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name = 'TJsDiceRollPlugin'");
        
        $db -> drop_column("posts","diceroll");
        
        rebuild_settings();
    }
    
    /**************************************************************************/
    
    $plugins->add_hook("datahandler_post_insert_post", "diceroll_roll");
    $plugins->add_hook("datahandler_post_insert_thread_post", "diceroll_roll");
    //$plugins->add_hook("datahandler_post_update", "dymy_dice_do");
    
    $plugins -> add_hook("postbit","diceroll_appendrolls");
    
    function diceroll_roll(&$post) {
        $msg = $post->post_insert_data['message'];
        $rollArray = array();
        $matches = array();
        preg_match_all("!\[roll ([A-z0-9]+) ([0-9]+)d([0-9]+)\]!i",$msg,$matches,PREG_SET_ORDER);
        foreach($matches as $match) {
            $rollName = $match[1];
            $numberOfRolls = $match[2];
            $sidedDice = $match[3];
            if($rollName && $numberOfRolls > 0 && $numberOfRolls < 999 && $sidedDice > 0 && $sidedDice < 999) {
                for($roll = 0; $roll < $numberOfRolls; $roll ++) {
                    $rollArray[$rollName][$sidedDice][$roll] = rand(1,$sidedDice);
                }
            }
       } 
       $saveString = serialize($rollArray);
       $post->post_insert_data['diceroll'] = $saveString;
    }
    
    function diceroll_appendrolls(&$post) {
        if($post['diceroll']) {
            $rollArray = @unserialize($post['diceroll']);
            if(is_array($rollArray)) {
                $post['message'] .= '<br /><br />Dice rolls attached to this thread:<br />';
                foreach($rollArray as $rollName => $sidedDice) {
                    $count = 0;
                    $post['message'] .= '<br /><i>' . $rollName . ' (' . $numberOfSides . ' sided dice):';
                    foreach($sidedDice as $numberOfSides => $result) {
                        foreach($result as $rollOffset => $rollResult) {
                            if($count > 0) { $post['message'] .= ','; }
                            $post['message'] .= $rollOffset . '=' . $rollResult;
                            $count ++;
                        }
                    }
                    $post['message'] .= '</i>';
                }
            }
        }
    }
    
    
?>