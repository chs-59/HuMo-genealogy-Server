<?php

// *** Needed to build array ***
class Change
{
    public $show_person;
    public $changed_date;
    public $new_date;
}

class Latest_changesModel
{
    private $dbh;

    public function __construct($dbh)
    {
        $this->dbh = $dbh;
    }

    public function listChanges($dbh, $tree_id)
    {
        // *** EXAMPLE of a UNION querie ***
        //$qry = "(SELECT * FROM humo1_person ".$query.') ';
        //$qry.= " UNION (SELECT * FROM humo2_person ".$query.')';
        //$qry.= " UNION (SELECT * FROM humo3_person ".$query.')';
        //$qry.= " ORDER BY pers_lastname, pers_firstname";

        $person_qry = "(SELECT *, STR_TO_DATE(pers_changed_date,'%d %b %Y') AS changed_date, pers_changed_time as changed_time
            FROM humo_persons
            WHERE pers_tree_id='" . $tree_id . "' AND pers_changed_date IS NOT NULL AND pers_changed_date!='')";
        $person_qry .= " UNION (SELECT *, STR_TO_DATE(pers_new_date,'%d %b %Y') AS changed_date, pers_new_time as changed_time
            FROM humo_persons
            WHERE pers_tree_id='" . $tree_id . "' AND pers_changed_date IS NULL)";
        $person_qry .= " ORDER BY changed_date DESC, changed_time DESC LIMIT 0,100";

        $search_name = '';
        if (isset($_POST["search_name"])) {
            $search_name = $_POST["search_name"];

            // *** Renewed querie because of ONLY_FULL_GROUP_BY in MySQL 5.7 ***
            $person_qry = "
                SELECT humo_persons2.*, humo_persons1.pers_id
                FROM humo_persons as humo_persons2
                RIGHT JOIN 
                (
                    (
                    SELECT pers_id
                    FROM humo_persons
                    LEFT JOIN humo_events
                        ON pers_gedcomnumber=event_connect_id AND pers_tree_id=event_tree_id AND event_kind='name'
                    WHERE (CONCAT(pers_firstname,REPLACE(pers_prefix,'_',' '),pers_lastname) LIKE '%" . safe_text_db($search_name) . "%'
                        OR event_event LIKE '%" . safe_text_db($search_name) . "%')
                        AND ((pers_changed_date IS NOT NULL AND pers_changed_date!='') OR (pers_new_date IS NOT NULL AND pers_new_date!=''))
                        AND pers_tree_id='" . $tree_id . "'
                    GROUP BY pers_id
                    )
                ) as humo_persons1
                ON humo_persons1.pers_id = humo_persons2.pers_id
                ";

            // *** Order by pers_changed_date or pers_new_date, also order by pers_changed_time or pers_new_time ***
            $person_qry .= " ORDER BY
                IF (humo_persons2.pers_changed_date IS NOT NULL AND humo_persons2.pers_changed_date!='',
                    STR_TO_DATE(humo_persons2.pers_changed_date,'%d %b %Y'),
                    STR_TO_DATE(humo_persons2.pers_new_date,'%d %b %Y')
                    ) DESC,
                IF (humo_persons2.pers_changed_date IS NOT NULL AND humo_persons2.pers_changed_date!='',
                    humo_persons2.pers_changed_time, humo_persons2.pers_new_time
                    ) DESC LIMIT 0,100";
        }

        $person_result = $dbh->query($person_qry);
        $i = 1;
        $changes = array();
        while (@$person = $person_result->fetch(PDO::FETCH_OBJ)) {
            $pers_changed_date = '';
            if ($person->pers_changed_date) $pers_changed_date = $person->pers_changed_date;

            if ($person->pers_sexe == "M") {
                $pers_sexe = '<img src="images/man.gif" alt="man">';
            } elseif ($person->pers_sexe == "F") {
                $pers_sexe = '<img src="images/woman.gif" alt="woman">';
            } else {
                $pers_sexe = '<img src="images/unknown.gif" alt="unknown">';
            }

            $person_cls = new person_cls($person);
            // *** Person url example (optional: "main_person=I23"): http://localhost/humo-genealogy/family/2/F10?main_person=I23/ ***
            $url = $person_cls->person_url2($person->pers_tree_id, $person->pers_famc, $person->pers_fams, $person->pers_gedcomnumber);
            $name = $person_cls->person_name($person);

            $changes[$i] = new Change();
            $changes[$i]->show_person = $person_cls->person_popup_menu($person) . $pers_sexe;
            $changes[$i]->show_person .= '<a href="' . $url . '">' . $name["standard_name"] . '</a>';

            $changes[$i]->changed_date = language_date($pers_changed_date) . ' - ' . $person->pers_changed_time;
            $changes[$i]->new_date = language_date($person->pers_new_date) . ' - ' . $person->pers_new_time;

            $i++;
        }

        return $changes;
    }
}