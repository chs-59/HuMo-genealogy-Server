<?php
class TimelineModel
{
    //private $db_functions;

    /*
    public function __construct($db_functions)
    {
        $this->db_functions = $db_functions;
    }
    */

    /*
    private function julgreg($date)
    {   // alters a julian/gregorian date entry such as 4 mar 1572/3 to use regular date for calculations
        if (strpos($date, '/') > 0) {
            $date_array = explode('/', $date);
            $date = $date_array[0];
        }
        return $date;
    }
    */
    // TODO remove return_array check (and use array in all julgreg lines.).
    private function julgreg($process_date, $return_array = false)
    {
        $data["year"] = '';
        $data["date_translated"] = '';

        // Alters a julian/gregorian date entry such as 4 mar 1572/3 to use regular date for calculations
        if (strpos($process_date, '/') > 0) {
            $date_array = explode('/', $process_date);
            $process_date = $date_array[0];
        }

        $year = substr($process_date, -4);
        if ($year > 0 && $year < 2200) {
            $data["year"] = $year;
            $data["date_translated"] = language_date($process_date);
        }

        if ($return_array) {
            return $data; // new method
        } else {
            return $process_date; //old method
        }
    }

    public function getPersonData($personDb)
    {
        $process_age = new calculate_year_cls;

        $data["isborn"] = 0;
        $data["isdeath"] = 0;
        $data["ismarr"] = 0;
        $data["ischild"] = 0;
        $data["deathtext"] = '';
        $data["borntext"] = '';
        $data["bapttext"] = '';
        $data["burrtext"] = '';
        $data["marrtext"] = array();
        $data["privacy_filtered"] = false;

        /*
        $data["bornyear"] = '';
        if (@$personDb->pers_birth_date) {
            $borndate = $this->julgreg($personDb->pers_birth_date);
            $temp = substr($borndate, -4);
            if ($temp > 0 and $temp < 2200) {
                $data["bornyear"] = $temp;
                $data["borntext"] = ucfirst(__('birth')) . ' ' . language_date($borndate);
                $data["isborn"] = 1;
            }
        }
        */
        $data["bornyear"] = '';
        if (@$personDb->pers_birth_date) {
            $get_date = $this->julgreg($personDb->pers_birth_date, true);
            if ($get_date["year"]) {
                $data["bornyear"] = $get_date["year"];
                $data["borntext"] = ucfirst(__('birth')) . ' ' . $get_date["date_translated"];
                $data["isborn"] = 1;
            }
        }

        /*
        $data["baptyear"] = '';
        if (@$personDb->pers_bapt_date) {
            $baptdate = $this->julgreg($personDb->pers_bapt_date);
            $temp = substr($baptdate, -4);
            if ($temp > 0 and $temp < 2200) {
                $data["baptyear"] = $temp;
                $data["bapttext"] = ucfirst(__('baptised')) . ' ' . language_date($baptdate);
                $data["isborn"] = 1;
            }
        }
        */
        $data["baptyear"] = '';
        if (@$personDb->pers_bapt_date) {
            $get_date = $this->julgreg($personDb->pers_bapt_date, true);
            if ($get_date["year"]) {
                $data["baptyear"] = $get_date["year"];
                $data["bapttext"] = ucfirst(__('baptised')) . ' ' . $get_date["date_translated"];
                $data["isborn"] = 1;
            }
        }

        $data["deathyear"] = '';
        if (@$personDb->pers_death_date) {
            $deathdate = $this->julgreg($personDb->pers_death_date);
            $temp = substr($deathdate, -4);
            if ($temp > 0 && $temp < 2200) {
                $data["deathyear"] = $temp;
                $data["deathtext"] = ucfirst(__('death')) . ' ' . language_date($deathdate);
                $age = $process_age->calculate_age($personDb->pers_bapt_date, $personDb->pers_birth_date, $personDb->pers_death_date, true);
                if ($age) {
                    $data["deathtext"] = '[' . $age . '] ' . $data["deathtext"];
                }
                $data["isdeath"] = 1;
            }
        }

        $data["burryear"] = '';
        if (@$personDb->pers_buried_date) {
            $burrdate = $this->julgreg($personDb->pers_buried_date);
            $temp = substr($burrdate, -4);
            if ($temp > 0 && $temp < 2200) {
                $data["burryear"] = $temp;
                $data["burrtext"] = ucfirst(__('buried')) . language_date($burrdate);
                $data["isdeath"] = 1;
            }
        }


        // *** CHECK IF ANY DATES ARE AVAILABLE. IF PARTS ARE MISSING ESTIMATE BIRTH/DEATH ***
        if ($data["isborn"] == 1 && $data["isdeath"] == 0) {
            // birth date but no death date: we show 80 years from birth
            $data["deathyear"] = $data["bornyear"] != 0 ? $data["bornyear"] + 80 : $data["baptyear"] + 80;
            $data["deathtext"] = __('Date of death unknown');
            // if birth+80 goes beyond present, we stop there but of course don't mention death.... ;-)
            if ($data["deathyear"] > date("Y")) {
                $data["deathyear"] = date("Y");
                $data["deathtext"] = '';
            }
        }
        if ($data["isborn"] == 0 && $data["isdeath"] == 1) {
            // death date but no birth date: we show 80 years prior to death
            $data["bornyear"] = $data["deathyear"] != 0 ? $data["deathyear"] - 80 : $data["burryear"] - 80;
            $data["borntext"] = __('Date of birth unknown');
        }
        if ($data["isborn"] == 0 && $data["isdeath"] == 0 && $data["ismarr"] == 1) {
            // no birth or death date but there is a marriage date:
            // birth is estimated as 25 years prior to marriage date
            // death is estimated as 55 years after marriage date
            if ($data["marryear"][0] != 0) {
                $data["bornyear"] = $data["marryear"][0] - 25;
                $data["deathyear"] = $data["marryear"][0] + 55;
            }
            $data["borntext"] = __('Date of birth unknown');
            $data["deathtext"] = __('Date of death unknown');
        }
        if ($data["isborn"] == 0 && $data["isdeath"] == 0 && $data["ismarr"] == 0 && $data["ischild"] == 1) {
            // no birth,death or marriage date but there is a childbirth date:
            // birth is estimated as 25 years prior to child birth date
            // death is estimated as 55 years after child birth date
            if ($data["chbornyear"][0][0] != 0) {
                $data["bornyear"] = $data["chbornyear"][0][0] - 25;
                $data["deathyear"] = $data["chdeathyear"][0][0] + 55;
            }
            $data["borntext"] = __('Date of birth unknown');
            $data["deathtext"] = __('Date of death unknown');
        }

        return $data;
    }

    public function getTimelinePersons($db_functions, $personDb, $user, $dirmark1)
    {
        // *** MARRIAGES & CHILDREN ***
        if (isset($personDb->pers_fams) && $personDb->pers_fams) {
            $process_age = new calculate_year_cls;

            $data["marriages"] = explode(";", $personDb->pers_fams);
            $count_marriages = count($data["marriages"]);
            for ($i = 0; $i < $count_marriages; $i++) {
                $data["children"][$i] = '';
                $data["marryear"][$i] = '';
                $marrdate[$i] = '';
                $familyDb = $db_functions->get_family($data["marriages"][$i]);
                $spouse = $personDb->pers_gedcomnumber == $familyDb->fam_man ? $familyDb->fam_woman : $familyDb->fam_man;
                $spouse2Db = $db_functions->get_person($spouse);
                $privacy = true;
                if ($spouse2Db) {
                    $person_cls = new person_cls($spouse2Db);
                    $privacy = $person_cls->privacy;
                    $name = $person_cls->person_name($spouse2Db);
                }
                if (!$privacy) {
                    if (isset($spouse2Db->pers_death_date) && $spouse2Db->pers_death_date) {
                        $data["spousedeathname"][$i] = '';
                        $data["spousedeathyear"][$i] = '';
                        $data["spousedeathtext"][$i] = '';
                        $data["spousedeathdate"][$i] = $this->julgreg($spouse2Db->pers_death_date);
                        $temp = substr($data["spousedeathdate"][$i], -4);
                        if ($temp && $temp > 0 && $temp < 2200) {
                            $spouse = $spouse2Db->pers_sexe == "M" ? __('SPOUSE_MALE') : __('SPOUSE_FEMALE');
                            $data["spousedeathyear"][$i] = $temp;
                            if ($name["firstname"]) {
                                $data["spousedeathname"][$i] = $name["firstname"];
                            }
                            $data["spousedeathtext"][$i] = ucfirst(__('death')) . ' ' . $spouse . " " . $data["spousedeathname"][$i] . " " . $dirmark1 . str_replace(" ", "&nbsp;", language_date($data["spousedeathdate"][$i]));
                            $age = $process_age->calculate_age($personDb->pers_bapt_date, $personDb->pers_birth_date, $spouse2Db->pers_death_date, true);
                            if ($age) {
                                $data["spousedeathtext"][$i] = '[' . $age . '] ' . $data["spousedeathtext"][$i];
                            }
                        }
                    }

                    $temp = '';
                    if ($familyDb->fam_marr_date) {
                        $marrdate[$i] = $this->julgreg($familyDb->fam_marr_date);
                        $text = ucfirst(__('marriage')) . ' ';
                    } elseif ($familyDb->fam_marr_church_date) {
                        $marrdate[$i] = $this->julgreg($familyDb->fam_marr_church_date);
                        $text = ucfirst(__('church marriage')) . ' ';
                    } elseif ($familyDb->fam_marr_notice_date) {
                        $marrdate[$i] = $this->julgreg($familyDb->fam_marr_notice_date);
                        $text = ucfirst(__('marriage notice')) . ' ';
                    } elseif ($familyDb->fam_marr_church_notice_date) {
                        $marrdate[$i] = $this->julgreg($familyDb->fam_marr_church_notice_date);
                        $text = ucfirst(__('church marriage notice')) . ' ';
                    } elseif ($familyDb->fam_relation_date) {
                        $marrdate[$i] = $this->julgreg($familyDb->fam_relation_date);
                        $text = ucfirst(__('partnership')) . ' ';
                    }
                    if ($marrdate[$i]) {
                        $temp = substr($marrdate[$i], -4);
                    }
                    if ($temp && $temp > 0 && $temp < 2200) {
                        if ($name["firstname"]) {
                            $spousename = $name["firstname"];
                            $spousetext = __('with ') . $spousename;
                        }
                        $data["marryear"][$i] = $temp;
                        $data["marrtext"][$i] = $text . $spousetext . " " . $dirmark1 . str_replace(" ", "&nbsp;", language_date($marrdate[$i]));
                        $data["ismarr"] = 1;

                        $age = $process_age->calculate_age($personDb->pers_bapt_date, $personDb->pers_birth_date, $marrdate[$i], true);
                        if ($age) {
                            $data["marrtext"][$i] = '[' . $age . '] ' . $data["marrtext"][$i];
                        }
                    }
                } else {
                    // *** Privacy filter activated ***
                    $data["privacy_filtered"] = true;
                }

                if ($familyDb->fam_children) {
                    $data["children"][$i] = explode(";", $familyDb->fam_children);
                    $count_children = count($data["children"][$i]);
                    for ($m = 0; $m < $count_children; $m++) {
                        $data["chmarriages"][$i][$m] = ''; // enter value so we wont get error messages
                        $chldDb = $db_functions->get_person($data["children"][$i][$m]);

                        // *** Check if child must be hidden ***
                        if (
                            $user["group_pers_hide_totally_act"] == 'j' && isset($chldDb->pers_own_code) && strpos(' ' . $chldDb->pers_own_code, $user["group_pers_hide_totally"]) > 0
                        ) {
                            continue;
                        }

                        if ($chldDb->pers_sexe == "M") {
                            $child = __('son');
                        } elseif ($chldDb->pers_sexe == "F") {
                            $child = __('daughter');
                        } else {
                            $child = __('child ');
                        }

                        $person2_cls = new person_cls($chldDb);
                        $privacy = $person2_cls->privacy;
                        $name = $person2_cls->person_name($chldDb);

                        if (!$privacy) {
                            $data["chbornyear"][$i][$m] = '';
                            $data["chborndate"][$i][$m] = '';
                            $data["chborntext"][$i][$m] = '';
                            $data["chdeathyear"][$i][$m] = '';
                            $data["chdeathdate"][$i][$m] = '';
                            $data["chdeathtext"][$i][$m] = '';

                            $childname[$i][$m] = $name["firstname"];
                            $data["chborndate"][$i][$m] = $this->julgreg($chldDb->pers_birth_date);
                            $temp = substr($data["chborndate"][$i][$m], -4);
                            if ($temp > 0 && $temp < 2200) {
                                $data["chbornyear"][$i][$m] = $temp;
                                $data["chborntext"][$i][$m] = ucfirst(__('birth')) . ' ' . $child . " " . $childname[$i][$m] . " " . $dirmark1 . str_replace(" ", "&nbsp;", language_date($data["chborndate"][$i][$m]));
                                $data["ischild"] = 1;

                                $age = $process_age->calculate_age($personDb->pers_bapt_date, $personDb->pers_birth_date, $chldDb->pers_birth_date, true);
                                if ($age) {
                                    $data["chborntext"][$i][$m] = '[' . $age . '] ' . $data["chborntext"][$i][$m];
                                }
                            }
                            $data["chdeathdate"][$i][$m] = $this->julgreg($chldDb->pers_death_date);
                            $temp = substr($data["chdeathdate"][$i][$m], -4);
                            if ($temp > 0 && $temp < 2200) {
                                $data["chdeathyear"][$i][$m] = $temp;
                                $data["chdeathtext"][$i][$m] = ucfirst(__('death')) . ' ' . $child . " " . $childname[$i][$m] . " " . $dirmark1 . str_replace(" ", "&nbsp;", language_date($data["chdeathdate"][$i][$m]));

                                $age = $process_age->calculate_age($personDb->pers_bapt_date, $personDb->pers_birth_date, $chldDb->pers_death_date, true);
                                if ($age) {
                                    $data["chdeathtext"][$i][$m] = '[' . $age . '] ' . $data["chdeathtext"][$i][$m];
                                }
                            }
                        } else {
                            // *** Privacy filter activated ***
                            $data["privacy_filtered"] = true;
                        }
                        if ($chldDb->pers_fams) {
                            $data["chmarriages"][$i][$m] = explode(";", $chldDb->pers_fams);
                            $count_chmarriages = count($data["chmarriages"][$i][$m]);
                            for ($p = 0; $p < $count_chmarriages; $p++) {
                                $data["grchildren"][$i][$m][$p] = ''; // enter value so webserver wont throw error messages
                                $data["chmarryear"][$i][$m][$p] = '';
                                $data["chmarrdate"][$i][$m][$p] = '';
                                $temp = '';
                                $chfamilyDb = $db_functions->get_family($data["chmarriages"][$i][$m][$p]);

                                // CHILDREN'S MARRIAGES
                                $chspouse = $chldDb->pers_gedcomnumber == $chfamilyDb->fam_man ? $chfamilyDb->fam_woman : $chfamilyDb->fam_man;
                                $chspouse2Db = $db_functions->get_person($chspouse);
                                $person_cls = new person_cls($chspouse2Db);
                                $privacy = $person_cls->privacy;
                                $name = $person_cls->person_name($chspouse2Db);
                                if (!$privacy) {
                                    if ($chfamilyDb->fam_marr_date) {
                                        $data["chmarrdate"][$i][$m][$p] = $this->julgreg($chfamilyDb->fam_marr_date);
                                        $chtext = ucfirst(__('marriage')) . ' ';
                                    } elseif ($chfamilyDb->fam_marr_church_date) {
                                        $data["chmarrdate"][$i][$m][$p] = $this->julgreg($chfamilyDb->fam_marr_church_date);
                                        $chtext = ucfirst(__('church marriage')) . ' ';
                                    } elseif ($chfamilyDb->fam_marr_notice_date) {
                                        $data["chmarrdate"][$i][$m][$p] = $this->julgreg($chfamilyDb->fam_marr_notice_date);
                                        $chtext = ucfirst(__('marriage notice')) . ' ';
                                    } elseif ($chfamilyDb->fam_marr_church_notice_date) {
                                        $data["chmarrdate"][$i][$m][$p] = $this->julgreg($chfamilyDb->fam_marr_church_notice_date);
                                        $chtext = ucfirst(__('church marriage notice')) . ' ';
                                    } elseif ($chfamilyDb->fam_relation_date) {
                                        $data["chmarrdate"][$i][$m][$p] = $this->julgreg($chfamilyDb->fam_relation_date);
                                        $chtext = ucfirst(__('partnership')) . ' ';
                                    }
                                    if ($data["chmarrdate"][$i][$m][$p]) {
                                        $temp = substr($data["chmarrdate"][$i][$m][$p], -4);
                                    }
                                    if ($temp && $temp > 0 && $temp < 2200) {
                                        if ($name["firstname"]) {
                                            $chspousename = $name["firstname"];
                                            $chspousetext = __('with ') . $chspousename;
                                        }
                                        $data["chmarryear"][$i][$m][$p] = $temp;
                                        $data["chmarrtext"][$i][$m][$p] = $chtext . $child . " " . $childname[$i][$m] . ' ' . $chspousetext . " " . $dirmark1 . str_replace(" ", "&nbsp;", language_date($data["chmarrdate"][$i][$m][$p]));
                                        //$chismarr=1;

                                        $age = $process_age->calculate_age($personDb->pers_bapt_date, $personDb->pers_birth_date, $data["chmarrdate"][$i][$m][$p], true);
                                        if ($age) {
                                            $data["chmarrtext"][$i][$m][$p] = '[' . $age . '] ' . $data["chmarrtext"][$i][$m][$p];
                                        }
                                    }
                                } else {
                                    // *** Privacy filter activated ***
                                    $data["privacy_filtered"] = true;
                                }
                                // END CHILDREN'S MARRIAGES

                                if ($chfamilyDb->fam_children) {
                                    $data["grchildren"][$i][$m][$p] = explode(";", $chfamilyDb->fam_children);
                                    $count_grchildren = count($data["grchildren"][$i][$m][$p]);
                                    for ($g = 0; $g < $count_grchildren; $g++) {
                                        $grchldDb = $db_functions->get_person($data["grchildren"][$i][$m][$p][$g]);
                                        $person3_cls = new person_cls($grchldDb);
                                        $privacy = $person3_cls->privacy;
                                        $name = $person3_cls->person_name($grchldDb);
                                        if (!$privacy) {
                                            $data["grchbornyear"][$i][$m][$p][$g] = '';
                                            $data["grchborndate"][$i][$m][$p][$g] = '';
                                            $data["grchborntext"][$i][$m][$p][$g] = '';
                                            $data["grchdeathyear"][$i][$m][$p][$g] = '';
                                            $data["grchdeathdate"][$i][$m][$p][$g] = '';
                                            $data["grchdeathtext"][$i][$m][$p][$g] = '';

                                            if ($grchldDb->pers_sexe == "M") {
                                                $grchild = __('grandson');
                                            } elseif ($grchldDb->pers_sexe == "F") {
                                                $grchild = __('granddaughter');
                                            } else {
                                                $grchild = __('grandchild');
                                            }

                                            $grchildname[$i][$m][$p][$g] = $name["firstname"];
                                            $data["grchborndate"][$i][$m][$p][$g] = $this->julgreg($grchldDb->pers_birth_date);
                                            $temp = substr($data["grchborndate"][$i][$m][$p][$g], -4);
                                            if ($temp > 0 && $temp < 2200) {
                                                $data["grchbornyear"][$i][$m][$p][$g] = $temp;
                                                $data["grchborntext"][$i][$m][$p][$g] = ucfirst(__('birth')) . ' ' . $grchild . " " . $grchildname[$i][$m][$p][$g] . " " . $dirmark1 . str_replace(" ", "&nbsp;", language_date($data["grchborndate"][$i][$m][$p][$g]));

                                                $age = $process_age->calculate_age($personDb->pers_bapt_date, $personDb->pers_birth_date, $grchldDb->pers_birth_date, true);
                                                if ($age) {
                                                    $data["grchborntext"][$i][$m][$p][$g] = '[' . $age . '] ' . $data["grchborntext"][$i][$m][$p][$g];
                                                }
                                            }
                                            $data["grchdeathdate"][$i][$m][$p][$g] = $this->julgreg($grchldDb->pers_death_date);
                                            $temp = substr($data["grchdeathdate"][$i][$m][$p][$g], -4);
                                            if ($temp > 0 && $temp < 2200) {
                                                $data["grchdeathyear"][$i][$m][$p][$g] = $temp;
                                                $data["grchdeathtext"][$i][$m][$p][$g] = ucfirst(__('death')) . ' ' . $grchild . " " . $grchildname[$i][$m][$p][$g] . "  " . $dirmark1 . str_replace(" ", "&nbsp;", language_date($data["grchdeathdate"][$i][$m][$p][$g]));

                                                $age = $process_age->calculate_age($personDb->pers_bapt_date, $personDb->pers_birth_date, $grchldDb->pers_death_date, true);
                                                if ($age) {
                                                    $data["grchdeathtext"][$i][$m][$p][$g] = '[' . $age . '] ' . $data["grchdeathtext"][$i][$m][$p][$g];
                                                }
                                            }
                                        } // end if privacy==''
                                        else {
                                            // *** Privacy filter activated ***
                                            $data["privacy_filtered"] = true;
                                        }
                                    } // end for grchildren
                                }    // end if grchildren
                            } // end for chmarriages
                        } //end if chldDb->pers_fams
                    } //end for
                } // end if children
            }

            return $data;
        }
        return null;
    }
}
