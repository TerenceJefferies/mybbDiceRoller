<?php

    /*MyBB Dice Roller - Permenant Rolls, rolls created using this plugin cannot be edited.*/

    if(!defined("IN_MYBB")) { die("Unauthorized"); }

    $diceroll_salt = "";
    $diceroll_verificationvisible = true;
    $diceroll_enable = false;

    function diceroll_info() {
        return array(
            "name"                  =>                  "TJs Dice Roll Plugin",
            "Description"           =>                  "A plugin that creates a permenant dice-roll to attach to posts (Rolls CANNOT be edited/deleted)",
            "Website"               =>                  "http://101stdivision.net",
            "author"                =>                  "NINTHTJ",
            "authorsite"            =>                  "http://101stdivision.net",
            "version"               =>                  "1.1",
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

        $diceroll_setting = array(
            'sid'                   =>                  'NULL',
            "name"                  =>                  "diceroll_salt",
            "title"                 =>                  "Dice role salt (for verification)",
            "description"           =>                  "Set this value to be a string only your admins know. They can use it to verify the authenticity of roles.",
            "optionscode"           =>                  "text",
            "value"                 =>                  "OJISndiujn91udndu9nasdu",
            "disporder"             =>                  3,
            "gid"                   =>                  intval($gid)
        );

        $db -> insert_query("settings",$diceroll_setting);

        $diceroll_setting = array(
            'sid'                   =>                  'NULL',
            "name"                  =>                  "diceroll_verificationvisible",
            "title"                 =>                  "Is the verification code visible?",
            "description"           =>                  "If set to YES, the verification code will be visible on posts",
            "optionscode"           =>                  "yesno",
            "value"                 =>                  "1",
            "disporder"             =>                  2,
            "gid"                   =>                  intval($gid)
        );

        $db -> insert_query("settings",$diceroll_setting);

        rebuild_settings();

        $db -> add_column("posts","diceroll","text");
    }

    function diceroll_deactivate() {
        global $db;
        $db -> query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name IN ('diceroll_enabled')");
        $db -> query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name IN ('diceroll_salt')");
        $db -> query("DELETE FROM " . TABLE_PREFIX . "settings WHERE name IN ('diceroll_verificationvisible')");
        $db -> query("DELETE FROM " . TABLE_PREFIX . "settinggroups WHERE name = 'TJsDiceRollPlugin'");

        $db -> drop_column("posts","diceroll");

        rebuild_settings();
    }

    $plugins -> add_hook("global_start","diceroll_init");
    function diceroll_init() {
        global $db;
        global $diceroll_salt;
        global $diceroll_verificationvisible;
        global $diceroll_enable;
        global $plugins;
        $result = $db -> query("SELECT * FROM " . TABLE_PREFIX . "settings WHERE name IN ('diceroll_salt')");
        $diceroll_salt = $db -> fetch_field($result,"value");
        $result = $db -> query("SELECT * FROM " . TABLE_PREFIX . "settings WHERE name IN ('diceroll_verificationvisible')");
        $diceroll_verificationvisible = $db -> fetch_field($result,"value");
        $result = $db -> query("SELECT * FROM " . TABLE_PREFIX . "settings WHERE name IN ('diceroll_enabled')");
        $diceroll_enable = $db -> fetch_field($result,"value");
        if($diceroll_enable) {
          $plugins->add_hook("datahandler_post_insert_post", "diceroll_roll");
          $plugins->add_hook("datahandler_post_insert_thread_post", "diceroll_roll");
          $plugins -> add_hook("postbit","diceroll_appendrolls");
        }
    }

    /**************************************************************************/

    function diceroll_roll(&$post) {
        global $db;
        $msg = $post->post_insert_data['message'];
        $rollArray = array();
        $matches = array();
        preg_match_all("!\[roll ([A-z0-9]+) ([0-9]+)d([0-9]+)\]!i",$msg,$matches,PREG_SET_ORDER);
        if($matches) {
            foreach($matches as $match) {
                $rollName = $match[1];
                $numberOfRolls = $match[2];
                $sidedDice = $match[3];
                if($rollName && $numberOfRolls > 0 && $numberOfRolls < 16 && $sidedDice > 0 && $sidedDice < 36) {
                    for($roll = 0; $roll < $numberOfRolls; $roll ++) {
                        $rollArray[$rollName][$sidedDice][$roll] = rand(1,$sidedDice);
                    }
                }
                $post -> post_insert_data['message'] = str_replace($match[0],"",$post -> post_insert_data['message']);
           }
           $saveString = serialize($rollArray);
           $post->post_insert_data['diceroll'] = $db -> escape_string($saveString);
        }
    }

    function diceroll_appendrolls(&$post) {
        global $diceroll_salt;
        global $diceroll_verificationvisible;
        if($post['diceroll']) {
            $rollArray = @unserialize($post['diceroll']);
            if(is_array($rollArray)) {
                if(count($rollArray) > 0) {
                    $post['message'] .= '<br /><br /><span style="color:purple;"><i>Dice rolls attached to this post:</i><br />';
                    if($diceroll_verificationvisible) {
                      $post['message'] .= 'Verification Code: ' . sha1($diceroll_salt . $post['pid']) . '<br />';
                    }
                    foreach($rollArray as $rollName => $sidedDice) {
                        foreach($sidedDice as $numberOfSides => $result) {
                            $post['message'] .= '<br /><i>' . $rollName . ' (' . $numberOfSides . ' sided dice):';
                            $appendRolls = '';
                            $totalResult = 0;
                            $count = 0;
                            foreach($result as $rollOffset => $rollResult) {
                                if($count > 0) { $appendRolls .= ','; }
                                $appendRolls .= ($rollOffset + 1) . '=' . $rollResult;
                                $totalResult += $rollResult;
                                $count ++;
                            }
                            if($count > 1) {
                              $appendRolls .= ' = ' . $totalResult;
                            } else {
                              $appendRolls = $totalResult;
                            }
                            $post['message'] .= $appendRolls;
                        }
                        $post['message'] .= '</i>';
                    }
                    $post['message'] .= '</span>';
                }
            }
        }
    }


?>
