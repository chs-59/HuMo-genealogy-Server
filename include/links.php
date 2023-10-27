<?php

/**
 * Added in in august 2023.
 * 
 * Example to get a link:
 * $link = $link_cls->get_link($uri_path, 'tree_index', $tree_id);
 *
 * For family page, add $vars.
 *  $vars['pers_family'] = $pers_family;
 *  $link = $link_cls->get_link('../', 'family', $tree_id, true, $vars);
 *  $link .= "main_person=" . $person->pers_gedcomnumber;
 */

class Link_cls
{
    public $path = '';

    // *** Seperator can be used if rewrite is disabled ***
    public $link_array = [
        ['page' => 'addresses', 'file_rewrite' => 'addresses', 'file' => 'index.php?page=addresses', 'seperator' => '&amp;'],
        ['page' => 'ancestor_report', 'file_rewrite' => 'ancestor_report', 'file' => 'index.php?page=ancestor_report', 'seperator' => '&amp;'],
        ['page' => 'birthday_list', 'file_rewrite' => 'birthday_list', 'file' => 'index.php?page=birthday_list', 'seperator' => '&amp;'],
        ['page' => 'cms_pages', 'file_rewrite' => 'cms_pages', 'file' => 'index.php?page=cms_pages', 'seperator' => '&amp;'],
        ['page' => 'cookies', 'file_rewrite' => 'cookies', 'file' => 'index.php?page=cookies', 'seperator' => '&amp;'],
        ['page' => 'family', 'file_rewrite' => 'family', 'file' => 'index.php?page=family', 'seperator' => '&amp;'],
        ['page' => 'fanchart', 'file_rewrite' => 'fanchart', 'file' => 'index.php?page=fanchart', 'seperator' => '&amp;'],
        ['page' => 'help', 'file_rewrite' => 'help', 'file' => 'index.php?page=help', 'seperator' => '&amp;'],
        ['page' => 'hourglass', 'file_rewrite' => 'hourglass', 'file' => 'index.php?page=hourglass', 'seperator' => '&amp;'],
        ['page' => 'index', 'file_rewrite' => 'index', 'file' => 'index.php', 'seperator' => '?'],
        ['page' => 'language', 'file_rewrite' => 'index', 'file' => 'index.php', 'seperator' => '?'],
        ['page' => 'latest_changes', 'file_rewrite' => 'latest_changes', 'file' => 'index.php?page=latest_changes', 'seperator' => '&amp;'],
        ['page' => 'list', 'file_rewrite' => 'list', 'file' => 'index.php?page=list', 'seperator' => '&amp;'],
        ['page' => 'list_places_families', 'file_rewrite' => 'list_places_families', 'file' => 'index.php?page=list_places_families', 'seperator' => '&amp;'],
        ['page' => 'login', 'file_rewrite' => 'login', 'file' => 'index.php?page=login', 'seperator' => '&amp;'],
        ['page' => 'logoff', 'file_rewrite' => 'index?log_off=1', 'file' => 'index.php?log_off=1', 'seperator' => '&amp;'],
        ['page' => 'mailform', 'file_rewrite' => 'mailform', 'file' => 'index.php?page=mailform', 'seperator' => '&amp;'],
        ['page' => 'maps', 'file_rewrite' => 'maps', 'file' => 'index.php?page=maps', 'seperator' => '&amp;'],
        ['page' => 'photoalbum', 'file_rewrite' => 'photoalbum', 'file' => 'index.php?page=photoalbum', 'seperator' => '&amp;'],
        ['page' => 'register', 'file_rewrite' => 'register', 'file' => 'index.php?page=register', 'seperator' => '&amp;'],
        ['page' => 'relations', 'file_rewrite' => 'relations', 'file' => 'index.php?page=relations', 'seperator' => '&amp;'],
        ['page' => 'report_outline', 'file_rewrite' => 'report_outline', 'file' => 'index.php?page=report_outline', 'seperator' => '&amp;'],
        ['page' => 'sources', 'file_rewrite' => 'sources', 'file' => 'index.php?page=sources', 'seperator' => '&amp;'],
        ['page' => 'statistics', 'file_rewrite' => 'statistics', 'file' => 'index.php?page=statistics', 'seperator' => '&amp;'],
        ['page' => 'timelines', 'file_rewrite' => 'timelines', 'file' => 'index.php?page=timelines', 'seperator' => '&amp;'],
        ['page' => 'tree_index', 'file_rewrite' => 'tree_index', 'file' => 'index.php?page=tree_index', 'seperator' => '&amp;'],
        ['page' => 'user_settings', 'file_rewrite' => 'user_settings', 'file' => 'index.php?page=user_settings', 'seperator' => '&amp;'],
    ];

    public function __construct($path = '')
    {
        $this->path = $path;
    }

    public function get_link($change_path, $page, $tree_id = NULL, $add_seperator = false, $vars = '')
    {
        //TODO move to constructor.
        global $humo_option;

        $path = $this->path;
        if ($change_path != '') $path = $change_path;

        // *** Default link ***
        if ($humo_option["url_rewrite"] == "j") {
            $link = $path . 'index'; // *** Default value ***
        } else {
            $seperator = '?';
            $link = $path . 'index.php'; // *** Default value ***
        }

        foreach ($this->link_array as $links) {
            //$vars = [];

            //if (isset($route['vars'])) {
            //    $vars = explode(',', $route['vars']);
            //}

            if ($humo_option["url_rewrite"] == "j") {
                if ($page == $links['page']) {
                    $link = $path . $links['file_rewrite'];

                    if ($tree_id) $link .= '/' . $tree_id;

                    if ($page == 'family' AND $vars) {
                        $link .= '/' . $vars['pers_family'];
                    }

                    if ($page == 'hourglass' AND $vars) {
                        $link .= '/' . $vars['pers_family'];
                    }

                    if ($page == 'ancestor_report' AND $vars) {
                        $link .= '/' . $vars['id'];
                    }

                    if ($add_seperator) $link .= '?';
                    break;
                }
            } else {
                if ($page == $links['page']) {
                    $link = $path . $links['file'];
                    $seperator = $links['seperator'];

                    if ($tree_id) {
                        $link .= $seperator . 'tree_id=' . $tree_id;
                        $seperator = '&amp;';
                    }

                    if ($page == 'family' AND $vars) {
                        $link .= '&amp;id=' . $vars['pers_family'];
                    }

                    if ($page == 'hourglass' AND $vars) {
                        $link .= '&amp;id=' . $vars['pers_family'];
                    }

                    if ($page == 'ancestor_report' AND $vars) {
                        $link .= '&amp;id=' . $vars['id'];
                    }

                    if ($add_seperator) $link .= $seperator;
                    break;
                }
            }
        }

        return $link;
    }
}
