<?php

if (!defined(INACP)) {
    die();
}

new groups();
class groups
{
    public function __construct()
    {
        global $JAX,$PAGE;
        $sidebar = '';
        $links = array(
            'perms' => 'Edit Permissions',
            'create' => 'Create Group',
            'delete' => 'Delete Group',
        );
        foreach ($links as $k => $v) {
            $sidebar .= "<li><a href='?act=groups&do=" . $k . "'>${v}</a></li>";
        }
        $PAGE->sidebar("<ul>${sidebar}</ul>");
        if (@$JAX->g['edit']) {
            $JAX->g['do'] = 'edit';
        }
        switch (@$JAX->g['do']) {
            case 'perms':
                $this->showperms();
                break;
            case 'create':
                $this->create();
                break;
            case 'edit':
                $this->create($JAX->g['edit']);
                break;
            case 'delete':
                $this->delete();
                break;
            default:
                $this->showperms();
                break;
        }
    }

    public function showindex()
    {
        global $PAGE;
        $PAGE->addContentBox('Error', 'under construction');
    }

    public function updateperms($perms)
    {
        global $PAGE,$DB;
        $columns = array(
            'can_access_acp',
            'can_post',
            'can_edit_posts',
            'can_add_comments',
            'can_delete_comments',
            'can_delete_own_posts',
            'can_post_topics',
            'can_edit_topics',
            'can_view_board',
            'can_view_offline_board',
            'flood_control',
            'can_override_locked_topics',
            'can_view_shoutbox',
            'can_shout',
            'can_moderate',
            'can_delete_shouts',
            'can_delete_own_shouts',
            'can_karma',
            'can_im',
            'can_pm',
            'can_lock_own_topics',
            'can_delete_own_topics',
            'can_use_sigs',
            'can_attach',
            'can_poll',
            'can_view_stats',
            'legend',
        );

        // Set anything not sent to 0.
        foreach ($perms as $k => $v2) {
            foreach ($columns as $v) {
                if (!isset($v2[$v])) {
                    $v2[$v] = false;
                }
                $perms[$k][$v] = $v2[$v] ? 1 : 0;
            }
        }

        // Remove any columns that don't exist silently.
        $columns = array_flip($columns);
        foreach ($perms as $k => $v) {
            foreach ($v as $k2 => $v2) {
                if (!isset($columns[$k2])) {
                    unset($perms[$k][$k2]);
                }
            }
        }

        // Update this.
        foreach ($perms as $k => $v) {
            if (2 == $k) {
                $v['can_access_acp'] = 1;
            }
            if ($k) {
                $DB->safeupdate(
                    'member_groups',
                    $v,
                    'WHERE `id`=?',
                    $k
                );
            }
        }

        echo $DB->error();

        $PAGE->addContentBox(
            'Success!',
            "<div style='padding:20px'>Changes Saved successfully.<br />" .
            "<br /><br /><a href='?act=groups'>Home</a></div>"
        );
    }

    public function showperms()
    {
        global $DB,$PAGE,$JAX;

        if (@$JAX->p['perm']) {
            foreach (explode(',', $JAX->p['grouplist']) as $v) {
                if (!isset($JAX->p['perm'][$v]) || !$JAX->p['perm'][$v]) {
                    $JAX->p['perm'][$v] = array();
                }
            }

            return $this->updateperms($JAX->p['perm']);
        }
        if (preg_match('@[^\\d,]@', @$JAX->b['grouplist'])
            || false !== mb_strpos(@$JAX->b['grouplist'], ',,')
        ) {
            $JAX->b['grouplist'] = '';
        }

        $result = @$JAX->b['grouplist'] ?
            $DB->safeselect(
                <<<'EOT'
`id`,`title`,`can_post`,`can_edit_posts`,`can_post_topics`,`can_edit_topics`,
`can_add_comments`,`can_delete_comments`,`can_view_board`,
`can_view_offline_board`,`flood_control`,`can_override_locked_topics`,
`icon`,`can_shout`,`can_moderate`,`can_delete_shouts`,`can_delete_own_shouts`,
`can_karma`,`can_im`,`can_pm`,`can_lock_own_topics`,`can_delete_own_topics`,
`can_use_sigs`,`can_attach`,`can_delete_own_posts`,`can_poll`,`can_access_acp`,
`can_view_shoutbox`,`can_view_stats`,`legend`,`can_view_fullprofile`
EOT
                ,
                'member_groups',
                'WHERE `id` IN ? ORDER BY `id` ASC',
                explode(',', $JAX->b['grouplist'])
            ) :
            $DB->safeselect(
                <<<'EOT'
`id`,`title`,`can_post`,`can_edit_posts`,`can_post_topics`,`can_edit_topics`,
`can_add_comments`,`can_delete_comments`,`can_view_board`,
`can_view_offline_board`,`flood_control`,`can_override_locked_topics`,
`icon`,`can_shout`,`can_moderate`,`can_delete_shouts`,`can_delete_own_shouts`,
`can_karma`,`can_im`,`can_pm`,`can_lock_own_topics`,`can_delete_own_topics`,
`can_use_sigs`,`can_attach`,`can_delete_own_posts`,`can_poll`,`can_access_acp`,
`can_view_shoutbox`,`can_view_stats`,`legend`,`can_view_fullprofile`
EOT
                ,
                'member_groups',
                'ORDER BY id ASC'
            );
        $numgroups = 0;
        $grouplist = '';
        while ($f = $DB->arow($result)) {
            ++$numgroups;
            $perms[$f['id']] = $f;
            $grouplist .= $f['id'] . ',';
        }
        if (!$numgroups) {
            die("Don't play with my variables!");
        }
        $grouplist = mb_substr($grouplist, 0, -1);
        $page = "<form action='?act=groups&do=perms' method='post'>
<input type='hidden' name='grouplist' value='${grouplist}' />
<div class='permcontainer'>* Starred are permissions not functional yet.
<table style='padding-left:150px;width:100%;position:relative;background:#FFF'
id='heading'>
    <tr>";
        foreach ($perms as $k => $v) {
            $page .= "<th style='width:" . ((1 / $numgroups) * 100) . "%'>" .
                "<a class='icons edit' href='?act=groups&edit=${k}'>" .
                $v['title'] . "</a> (${k})</th>";
        }
        $page .= "</tr>
  </table>
  <table class='perms'>
  ";
        foreach (array(
                'breaker1' => 'Global',
                'can_view_board' => 'View Online Board',
                'can_view_offline_board' => 'View Offline Board',
                'can_access_acp' => 'Access ACP',
                'can_moderate' => 'Global Moderator',

                'breaker2' => 'Members',
                'can_karma' => '*Change Karma',

                'breaker3' => 'Posts',
                'can_post' => 'Create',
                'can_edit_posts' => 'Edit',
                'can_delete_own_posts' => '*Delete Own Posts',
                'can_attach' => 'Attach files',
                'can_use_sigs' => '*Can have signatures',

                'breaker4' => 'Topics',
                'can_post_topics' => 'Create',
                'can_edit_topics' => 'Edit',
                'can_poll' => 'Add Polls',
                'can_delete_own_topics' => '*Delete Own Topics',
                'can_lock_own_topics' => '*Lock Own Topics',
                'can_override_locked_topics' => 'Post in locked topics',

                'breaker5' => 'Profiles',
                'can_add_comments' => 'Add Comments',
                'can_delete_comments' => '*Delete own Comments',

                'breaker6' => 'Shoutbox',
                'can_view_shoutbox' => 'View Shoutbox',
                'can_shout' => 'Can Shout',
                'can_delete_shouts' => 'Delete All Shouts',
                'can_delete_own_shouts' => 'Delete Own Shouts',

                'breaker8' => 'Statistics',
                'can_view_stats' => 'View Board Stats',
                'legend' => 'Display in Legend',

                'breaker7' => 'Private/Instant Messaging',
                'can_pm' => 'Can PM',
                'can_im' => 'Can IM',
            ) as $k => $v) {
            if ('breaker' == mb_substr($k, 0, 7)) {
                $page .= "<tr><td class='breaker' colspan='" .
                    (1 + $numgroups) . "'>${v}</td></tr>";
            } else {
                $page .= "<tr><td style='width:150px'>" . $v . '</td>';
                foreach ($perms as $k2 => $v2) {
                    $page .= '<td class="center"><input name="perm[' . $k2 .
                        '][' . $k . ']" type="checkbox" ' .
                        ($v2[$k] ? 'checked="checked" ' : '') .
                        'class="switch yn" /></td>';
                }
                $page .= '</tr>';
            }
        }
        $page .= '
  </table>';
        $page .= "</div><div style='margin-top:20px' class='center'>" .
            "<input type='submit' value='Save Changes' /></div></form>";
        $page .= '<script type="text/javascript">window.onscroll=function(){
            var heading = document.querySelector("#heading");
            var c=ACP.getCoordinates(heading)
            c.y -= parseInt(heading.style.top) || 0;
            var st = document.documentElement.scrollTop || document.body.scrollTop
            heading.style.top=((st-c.y)<0?0:st-c.y)+"px"
        }
        </script>';

        $PAGE->addContentBox('Perms', $page);
    }

    public function create($gid = false)
    {
        if ($gid && !is_numeric($gid)) {
            $gid = false;
        }
        global $PAGE,$JAX,$DB;
        $page = '';
        $e = '';
        if (@$JAX->p['submit']) {
            if (!@$JAX->p['groupname']) {
                $e = 'Group name required!';
            } elseif (mb_strlen($JAX->p['groupname']) > 250) {
                $e = 'Group name must not exceed 250 characters!';
            } elseif (mb_strlen($JAX->p['groupicon']) > 250) {
                $e = 'Group icon must not exceed 250 characters!';
            } elseif ($JAX->p['groupicon'] && !$JAX->isurl($JAX->p['groupicon'])) {
                $e = 'Group icon must be a valid image url';
            }
            if ($e) {
                $page .= $PAGE->error($e);
            } else {
                $write = array(
                    'title' => $JAX->p['groupname'],
                    'icon' => $JAX->p['groupicon'],
                );
                if ($gid) {
                    $DB->safeupdate(
                        'member_groups',
                        $write,
                        'WHERE `id`=?',
                        $DB->basicvalue($gid)
                    );
                } else {
                    $DB->safeinsert(
                        'member_groups',
                        $write
                    );
                }
                $page .= $PAGE->success(
                    "Data saved. <a href='?act=groups'>Back</a>"
                );
            }
        }
        if ($gid) {
            $result = $DB->safeselect(
                '`title`,`icon`',
                'member_groups',
                'WHERE `id`=?',
                $DB->basicvalue($gid)
            );
            $gdata = $DB->arow($result);
            $DB->disposeresult($result);
        }

        $page .= '<form method="post"><label for="groupname">' .
            'Group name:</label><input type="text" id="groupname" ' .
            'name="groupname" value="' .
            ($gid ? $JAX->blockhtml($gdata['title']) : '') . '" /><br />
            <label for="groupicon">Icon: </label><input type="text" ' .
            'id="groupicon" name="groupicon" value="' .
            ($gid ? $JAX->blockhtml($gdata['icon']) : '') . '" /><br />
            <input type="submit" name="submit" value="' .
            ($gid ? 'Edit' : 'Create') . '" />
          </form>';
        $PAGE->addContentBox(
            $gid ? 'Editing group: ' . $gdata['title'] : 'Create a group!',
            $page
        );
    }

    public function delete()
    {
        global $PAGE,$DB,$JAX;
        $page = '';
        if (is_numeric(@$JAX->b['delete']) && $JAX->b['delete'] > 5) {
            $DB->safedelete(
                'member_groups',
                'WHERE `id`=?',
                $DB->basicvalue($JAX->b['delete'])
            );
            $DB->safeupdate(
                'members',
                array(
                    'group_id' => 1,
                ),
                'WHERE `group_id`=?',
                $DB->basicvalue($JAX->b['delete'])
            );
        }
        $result = $DB->safeselect(
            '`id`,`title`',
            'member_groups',
            'WHERE `id`>5'
        );
        $found = false;
        while ($f = $DB->arow($result)) {
            $found = true;
            $page .= '<a class="icons delete" onclick="' .
                'return confirm(\'You sure?\')" href="?act=groups&do=delete' .
                '&delete=' . $f['id'] . '">' . $f['title'] . '</a>';
        }
        if (!$found) {
            $page .= "You haven't created any groups to delete. " .
                "(Hint: default groups can't be deleted)";
        }
        $PAGE->addContentBox('Delete Groups', $page);
    }
}
