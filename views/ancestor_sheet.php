<?php

/**
 * First test scipt made by: Klaas de Winkel
 * Graphical script made by: Theo Huitema
 * Graphical part: better lay-out (colours) and pictures made by: Rene Janssen
 * Graphical part: improved lay-out by: Huub Mons.
 * Ancestor sheet, PDF export for ancestor report and ancestor sheet, image generation for chart made by: Yossi Beck.
 * July 2011: translated all variables to english by: Huub Mons.
 */

$screen_mode = 'ancestor_sheet';

//$pdf_source = array();  // is set in show_sources.php with sourcenr as key to be used in source appendix
// see end of this code 

// *** Check if person gedcomnumber is valid ***
$db_functions->check_person($data["main_person"]);

// The following is used for ancestor chart, ancestor sheet and ancestor sheet PDF (ASPDF)
// person 01
$personDb = $db_functions->get_person($data["main_person"]);
$gedcomnumber[1] = $personDb->pers_gedcomnumber;
$pers_famc[1] = $personDb->pers_famc;
$sexe[1] = $personDb->pers_sexe;
$parent_array[2] = '';
$parent_array[3] = '';
if ($pers_famc[1]) {
    $parentDb = $db_functions->get_family($pers_famc[1]);
    $parent_array[2] = $parentDb->fam_man;
    $parent_array[3] = $parentDb->fam_woman;
    $marr_date_array[2] = $parentDb->fam_marr_date;
    $marr_place_array[2] = $parentDb->fam_marr_place;
}
// end of person 1

// Loop to find person data
$count_max = 64;
for ($counter = 2; $counter < $count_max; $counter++) {
    $gedcomnumber[$counter] = '';
    $pers_famc[$counter] = '';
    $sexe[$counter] = '';
    if ($parent_array[$counter]) {
        $personDb = $db_functions->get_person($parent_array[$counter]);
        $gedcomnumber[$counter] = $personDb->pers_gedcomnumber;
        $pers_famc[$counter] = $personDb->pers_famc;
        $sexe[$counter] = $personDb->pers_sexe;
    }

    $Vcounter = $counter * 2;
    $Mcounter = $Vcounter + 1;
    $parent_array[$Vcounter] = '';
    $parent_array[$Mcounter] = '';
    $marr_date_array[$Vcounter] = '';
    $marr_place_array[$Vcounter] = '';
    if ($pers_famc[$counter]) {
        $parentDb = $db_functions->get_family($pers_famc[$counter]);
        $parent_array[$Vcounter] = $parentDb->fam_man;
        $parent_array[$Mcounter] = $parentDb->fam_woman;
        $marr_date_array[$Vcounter] = $parentDb->fam_marr_date;
        $marr_place_array[$Vcounter] = $parentDb->fam_marr_place;
    }
}

// *** Function to show data ***
// box_appearance (large, medium, small, and some other boxes...)
function ancestor_chart_person($id, $box_appearance)
{
    global $dbh, $db_functions, $tree_prefix_quoted, $humo_option, $user;
    global $marr_date_array, $marr_place_array;
    global $gedcomnumber, $language, $screen_mode, $dirmark1, $dirmark2;

    $hour_value = ''; // if called from hourglass size of chart is given in box_appearance as "hour45" etc.
    if (strpos($box_appearance, "hour") !== false) {
        $hour_value = substr($box_appearance, 4);
    }

    $text = '';
    $popup = '';

    if ($gedcomnumber[$id]) {
        @$personDb = $db_functions->get_person($gedcomnumber[$id]);
        $person_cls = new person_cls($personDb);
        $pers_privacy = $person_cls->privacy;
        $name = $person_cls->person_name($personDb);

        if ($screen_mode == "ancestor_sheet" or $language["dir"] == "rtl") {
            $name2 = $name["name"];
        } else {
            //$name2=$name["short_firstname"];
            $name2 = $name["name"];
        }
        $name2 = $dirmark2 . $name2 . $name["colour_mark"] . $dirmark2;

        // *** Replace pop-up icon by a text box ***
        $replacement_text = '';
        if ($screen_mode == "ancestor_sheet") {  // *** Ancestor sheet: name bold, id not ***
            //$replacement_text.=$id.' <b>'.$name2.'</b>';
            $replacement_text .= '<b>' . $name2 . '</b>';
        } else {
            //$replacement_text.='<b>'.$id.'</b>';  // *** Ancestor number: id bold, name not ***
            $replacement_text .= '<span class="anc_box_name">' . $name2 . '</span>';
        }

        // >>>>> link to show rest of ancestor chart
        //if ($box_appearance=='small' AND isset($personDb->pers_gedcomnumber) AND $screen_mode!="ancestor_sheet"){
        if ($box_appearance == 'small' and isset($personDb->pers_gedcomnumber) and $personDb->pers_famc and $screen_mode != "ancestor_sheet") {
            $replacement_text .= ' &gt;&gt;&gt;' . $dirmark1;
        }

        if ($pers_privacy) {
            if ($box_appearance != 'ancestor_sheet_marr') {
                $replacement_text .= '<br>' . __(' PRIVACY FILTER');  //Tekst privacy weergeven
            } else {
                $replacement_text = __(' PRIVACY FILTER');
            }
        } else {
            if ($box_appearance != 'small') {
                //if ($personDb->pers_birth_date OR $personDb->pers_birth_place){
                if ($personDb->pers_birth_date) {
                    //$replacement_text.='<br>'.__('*').$dirmark1.' '.date_place($personDb->pers_birth_date,$personDb->pers_birth_place); }
                    $replacement_text .= '<br>' . __('*') . $dirmark1 . ' ' . date_place($personDb->pers_birth_date, '');
                }
                //elseif ($personDb->pers_bapt_date OR $personDb->pers_bapt_place){
                elseif ($personDb->pers_bapt_date) {
                    //$replacement_text.='<br>'.__('~').$dirmark1.' '.date_place($personDb->pers_bapt_date,$personDb->pers_bapt_place); }
                    $replacement_text .= '<br>' . __('~') . $dirmark1 . ' ' . date_place($personDb->pers_bapt_date, '');
                }

                //if ($personDb->pers_death_date OR $personDb->pers_death_place){
                if ($personDb->pers_death_date) {
                    //$replacement_text.='<br>'.__('&#134;').$dirmark1.' '.date_place($personDb->pers_death_date,$personDb->pers_death_place); }
                    $replacement_text .= '<br>' . __('&#134;') . $dirmark1 . ' ' . date_place($personDb->pers_death_date, '');
                }
                //elseif ($personDb->pers_buried_date OR $personDb->pers_buried_place){
                elseif ($personDb->pers_buried_date) {
                    //$replacement_text.='<br>'.__('[]').$dirmark1.' '.date_place($personDb->pers_buried_date,$personDb->pers_buried_place); }
                    $replacement_text .= '<br>' . __('[]') . $dirmark1 . ' ' . date_place($personDb->pers_buried_date, '');
                }

                if ($box_appearance != 'medium') {
                    $marr_date = '';
                    if (isset($marr_date_array[$id]) and ($marr_date_array[$id] != '')) {
                        $marr_date = $marr_date_array[$id];
                    }
                    $marr_place = '';
                    if (isset($marr_place_array[$id]) and ($marr_place_array[$id] != '')) {
                        $marr_place = $marr_place_array[$id];
                    }
                    //if ($marr_date OR $marr_place){
                    if ($marr_date) {
                        //$replacement_text.='<br>'.__('X').$dirmark1.' '.date_place($marr_date,$marr_place); }
                        $replacement_text .= '<br>' . __('X') . $dirmark1 . ' ' . date_place($marr_date, '');
                    }
                }
                if ($box_appearance == 'ancestor_sheet_marr') {
                    $replacement_text = '';
                    $marr_date = '';
                    if (isset($marr_date_array[$id]) and ($marr_date_array[$id] != '')) {
                        $marr_date = $marr_date_array[$id];
                    }
                    $marr_place = '';
                    if (isset($marr_place_array[$id]) and ($marr_place_array[$id] != '')) {
                        $marr_place = $marr_place_array[$id];
                    }
                    //if ($marr_date OR $marr_place){
                    if ($marr_date) {
                        //$replacement_text=__('X').$dirmark1.' '.date_place($marr_date,$marr_place); }
                        $replacement_text = __('X') . $dirmark1 . ' ' . date_place($marr_date, '');
                    } else $replacement_text = __('X'); // if no details in the row we don't want the row to collapse
                }
                if ($box_appearance == 'ancestor_header') {
                    $replacement_text = '';
                    $replacement_text .= strip_tags($name2);
                    $replacement_text .= $dirmark2;
                }
            }
        }

        if ($hour_value != '') { // called from hourglass
            if ($hour_value == '45') {
                $replacement_text = $name['name'];
            } elseif ($hour_value == '40') {
                $replacement_text = '<span class="wordwrap" style="font-size:75%">' . $name['short_firstname'] . '</span>';
            } elseif ($hour_value > 20 and $hour_value < 40) {
                $replacement_text = $name['initials'];
            } elseif ($hour_value < 25) {
                $replacement_text = "&nbsp;";
            }
            // if full scale (50) then the default of this function will be used: name with details
        }

        $extra_popup_text = '';
        $marr_date = '';
        if (isset($marr_date_array[$id]) and ($marr_date_array[$id] != '')) {
            $marr_date = $marr_date_array[$id];
        }
        $marr_place = '';
        if (isset($marr_place_array[$id]) and ($marr_place_array[$id] != '')) {
            $marr_place = $marr_place_array[$id];
        }
        if ($marr_date or $marr_place) {
            $extra_popup_text .= '<br>' . __('X') . $dirmark1 . ' ' . date_place($marr_date, $marr_place);
        }

        // *** Show picture by person ***
        if ($box_appearance != 'small' and $box_appearance != 'medium' and (strpos($box_appearance, "hour") === false or $box_appearance == "hour50")) {
            // *** Show picture ***
            if (!$pers_privacy and $user['group_pictures'] == 'j') {
                //  *** Path can be changed per family tree ***
                global $dataDb;
                $tree_pict_path = $dataDb->tree_pict_path;
                if (substr($tree_pict_path, 0, 1) == '|') $tree_pict_path = 'media/';
                $picture_qry = $db_functions->get_events_connect('person', $personDb->pers_gedcomnumber, 'picture');
                // *** Only show 1st picture ***
                if (isset($picture_qry[0])) {
                    $pictureDb = $picture_qry[0];
                    $picture = show_picture($tree_pict_path, $pictureDb->event_event, 80, 70);
                    //$text.='<img src="'.$tree_pict_path.$picture['thumb'].$picture['picture'].'" style="float:left; margin:5px;" alt="'.$pictureDb->event_text.'" width="'.$picture['width'].'">';
                    $text .= '<img src="' . $picture['path'] . $picture['thumb'] . $picture['picture'] . '" style="float:left; margin:5px;" alt="' . $pictureDb->event_text . '" width="' . $picture['width'] . '">';
                }
            }
        }

        if ($box_appearance == 'ancestor_sheet_marr' or $box_appearance == 'ancestor_header') { // cause in that case there is no link
            $text .= $replacement_text;
        } else {
            $text .= $person_cls->person_popup_menu($personDb, true, $replacement_text, $extra_popup_text);
        }
    }
    return $text . "\n";
}
// *** End of function ancestor_chart_person ***

// Specific code for ancestor SHEET:
// print names and details for each row in the table
function kwname($start, $end, $increment, $fontclass, $colspan, $type)
{
    global $sexe;

    echo '<tr>';
    for ($x = $start; $x < $end; $x += $increment) {
        // *** Added coloured boxes in november 2022 ***
        $sexe_colour = '';
        if ($type != 'ancestor_sheet_marr') {
            if ($sexe[$x] == 'F') {
                $sexe_colour = ' style=" background-image: linear-gradient(to bottom, #FFFFFF 0%, #F5BCA9 100%);"';
            }
            if ($sexe[$x] == 'M') {
                $sexe_colour = ' style="background-image: linear-gradient(to bottom, #FFFFFF 0%, #81BEF7 100%);"';
            }
        }

        if ($colspan > 1) {
            //echo '<td class="'.$fontclass.'" colspan='.$colspan.'>';
            echo '<td colspan=' . $colspan . $sexe_colour . '>';
        } else {
            //echo '<td class="'.$fontclass.'">';
            echo '<td' . $sexe_colour . '>';
        }
        $kwpers = ancestor_chart_person($x, $type);
        if ($kwpers != '') {
            echo $kwpers;
        } else {   // if we don't do this IE7 wil not print borders of cells
            echo '&nbsp;';
        }
        echo '</td>';
    }
    echo '</tr>';
}

// check if there is anyone in a generation so no empty and collapsed rows will be shown
function check_gen($start, $end)
{
    global $gedcomnumber;
    $is_gen = 0;
    for ($i = $start; $i < $end; $i++) {
        if (isset($gedcomnumber[$i]) and $gedcomnumber[$i] != '') {
            $is_gen = 1;
        }
    }
    return $is_gen;
}
?>

<?= $data["ancestor_header"]; ?>

<table class="humo ancestor_sheet">
    <tr>
        <th class="ancestor_head" colspan="8"> <!-- adjusted for IE7 -->
            <?php
            echo __('Ancestor sheet') . __(' of ') . ancestor_chart_person(1, "ancestor_header");

            if ($user["group_pdf_button"] == 'y' and $language["dir"] != "rtl" and $language["name"] != "简体中文") {
                // Show pdf button
                echo '&nbsp;&nbsp; <form method="POST" action="' . $uri_path . 'views/ancestor_sheet_pdf.php?show_sources=1" style="display : inline;">';
                echo '<input type="hidden" name="id" value="' . $data["main_person"] . '">';
                echo '<input type="hidden" name="database" value="' . $_SESSION['tree_prefix'] . '">';
                echo '<input type="hidden" name="screen_mode" value="ASPDF">';
                echo '<input class="fonts" type="Submit" name="submit" value="PDF Report">';
                echo '</form>';
            }
            ?>
        </th>
    </tr>

    <?php
    $gen = 0;
    $gen = check_gen(16, 32);
    if ($gen == 1) {
        kwname(16, 32, 2, "kw-small", 1, "medium");
        kwname(16, 32, 2, "kw-small", 1, "ancestor_sheet_marr");
        kwname(17, 33, 2, "kw-small", 1, "medium");
        echo '<tr><td colspan=8 class="ancestor_devider">&nbsp;</td></tr>';  // adjusted for IE7
    }
    $gen = 0;
    $gen = check_gen(8, 16);
    if ($gen == 1) {
        kwname(8, 16, 1, "kw-bigger", 1, "medium");
        kwname(8, 16, 2, "kw-small", 2, "ancestor_sheet_marr");
    }
    $gen = 0;
    $gen = check_gen(4, 8);
    if ($gen == 1) {
        kwname(4, 8, 1, "kw-medium", 2, "medium");
        kwname(4, 8, 2, "kw-small", 4, "ancestor_sheet_marr");
    }
    kwname(2, 4, 1, "kw-big", 4, "medium");
    kwname(2, 4, 2, "kw-small", 8, "ancestor_sheet_marr");
    kwname(1, 2, 1, "kw-big", 8, "medium");
    ?>
</table>

<br>
<div class="ancestor_legend">
    <b><?= __('Legend'); ?></b><br>
    <?= __('*') . '  ' . __('born') . ', ' . __('&#134;') . '  ' . __('died') . ', ' . __('X') . '  ' . __('married'); ?><br>
    <?php printf(__('Generated with %s on %s'), 'HuMo-genealogy', date("d M Y - H:i")); ?>
</div>
<br>