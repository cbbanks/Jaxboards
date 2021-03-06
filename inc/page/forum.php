<?php

$PAGE->loadmeta('forum');

$IDX = new FORUM();
class FORUM
{
    public $topicsRead = array();
    public $forumsRead = array();
    public $forumReadTime = 0;

    public function __construct()
    {
        global $JAX,$PAGE;

        $this->numperpage = 20;
        $this->page = 0;
        if (isset($JAX->b['page'])
            && is_numeric($JAX->b['page'])
            && $JAX->b['page'] > 0
        ) {
            $this->page = $JAX->b['page'] - 1;
        }

        preg_match('@^([a-zA-Z_]+)(\\d+)$@', $JAX->g['act'], $act);
        if (isset($JAX->b['markread']) && $JAX->b['markread']) {
            $this->markread($act[2]);

            return $PAGE->location('?');
        }
        if ($PAGE->jsupdate) {
            $this->update();
        } elseif (isset($JAX->b['replies']) && is_numeric($JAX->b['replies'])) {
            $this->getreplysummary($JAX->b['replies']);
        } else {
            $this->viewforum($act[2]);
        }
    }

    public function viewforum($fid)
    {
        global $DB,$PAGE,$JAX,$PERMS,$USER;

        // If no fid supplied, go to the index and halt execution.
        if (!$fid) {
            return $PAGE->location('?');
        }

        $page = $rows = $table = '';

        $fdata = $DB->fetchResource("forum/$fid");

        if (!$fdata) {
            return $PAGE->location('?');
        }

        if ($fdata['redirect']) {
            $PAGE->JS('softurl');
            $DB->safespecial(
                <<<'EOT'
UPDATE %t
SET `redirects` = `redirects` + 1
WHERE `id`=?
EOT
                ,
                array('forums'),
                $DB->basicvalue($fid)
            );

            return $PAGE->location($fdata['redirect']);
        }

        $title = &$fdata['title'];

        $fdata['perms'] = $JAX->parseperms(
            $fdata['perms'],
            $USER ? $USER['group_id'] : 3
        );
        if (!$fdata['perms']['read']) {
            $PAGE->JS('alert', 'no permission');

            return $PAGE->location('?');
        }

        // NOW we can actually start building the page
        // subforums
        // right now, this loop also fixes the number of pages to show in a forum
        // parent forum - subforum topics = total topics
        // I'm fairly sure this is faster than doing
        // `SELECT count(*) FROM topics`... but I haven't benchmarked it.
        $result = $DB->fetchResource("forums", [
            "path" => $fid
        ]);
        $rows = '';
        foreach ($result as $f) {
            $fdata['topics'] -= $f['topics'];
            if ($this->page) {
                continue;
            }
            $rows .= $PAGE->meta(
                'forum-subforum-row',
                $f['id'],
                $f['title'],
                $f['subtitle'],
                $PAGE->meta(
                    'forum-subforum-lastpost',
                    $f['lp_tid'],
                    $JAX->pick($f['lp_topic'], '- - - - -'),
                    $f['last_poster']['display_name'] ? $PAGE->meta(
                        'user-link',
                        $f['lp_uid'],
                        $f['last_poster']['group_id'],
                        $f['last_poster']['display_name']
                    ) : 'None',
                    $JAX->pick($JAX->date($f['lp_date']), '- - - - -')
                ),
                $f['topics'],
                $f['posts'],
                ($read = $this->isForumRead($f)) ? 'read' : 'unread',
                $read ? $JAX->pick(
                    $PAGE->meta(
                        'subforum-icon-read'
                    ),
                    $PAGE->meta(
                        'icon-read'
                    )
                ) : $JAX->pick(
                    $PAGE->meta('subforum-icon-unread'),
                    $PAGE->meta('icon-unread')
                )
            );
            if (!$read) {
                $unread = true;
            }
        }
        if ($rows) {
            $page .= $PAGE->collapsebox(
                'Subforums',
                $PAGE->meta('forum-subforum-table', $rows)
            );
        }

        $rows = $table = '';

        // Generate pages.
        $numpages = ceil($fdata['topics'] / $this->numperpage);
        $forumpages = '';
        if ($numpages) {
            foreach ($JAX->pages($numpages, $this->page + 1, 10) as $v) {
                $forumpages .= '<a href="?act=vf' . $fid . '&amp;page=' .
                    $v . '"' . (($v - 1) == $this->page ? ' class="active"' : '') .
                    '>' . $v . '</a> ';
            }
        }

        // Buttons.
        $forumbuttons = '&nbsp;' .
            ($fdata['perms']['start'] ? '<a href="?act=post&amp;fid=' . $fid . '">' .
            ($PAGE->meta(
                $PAGE->metaexists('button-newtopic') ?
                'button-newtopic' : 'forum-button-newtopic'
            )) . '</a>' : '');
        $page .= $PAGE->meta(
            'forum-pages-top',
            $forumpages
        ) . $PAGE->meta(
            'forum-buttons-top',
            $forumbuttons
        );

        // Topics.
        $result = $DB->fetchResource("topics", [
            "fid" => $fid,
            "page" => $this->page,
            "orderBy" => $fdata['orderby']
        ]);

        foreach ($result as $f) {
            $pages = '';
            if ($f['replies'] > 9) {
                foreach ($JAX->pages(ceil(($f['replies'] + 1) / 10), 1, 10) as $v) {
                    $pages .= "<a href='?act=vt" . $f['id'] .
                        "&amp;page=${v}'>${v}</a> ";
                }
                $pages = $PAGE->meta('forum-topic-pages', $pages);
            }
            $read = false;
            $unread = false;
            $rows .= $PAGE->meta(
                'forum-row',
                $f['id'],
                // 1
                $JAX->wordfilter($f['title']),
                // 2
                $JAX->wordfilter($f['subtitle']),
                // 3
                $PAGE->meta('user-link', $f['auth_id'], $f['author']['group_id'], $f['author']['display_name']),
                // 4
                $f['replies'],
                // 5
                number_format($f['views']),
                // 6
                $JAX->date($f['lp_date']),
                // 7
                $PAGE->meta('user-link', $f['lp_uid'], $f['last_poster']['group_id'], $f['last_poster']['display_name']),
                // 8
                ($f['pinned'] ? 'pinned' : '') . ' ' . ($f['locked'] ? 'locked' : ''),
                // 9
                $f['summary'] ? $f['summary'] . (mb_strlen($f['summary']) > 45 ? '...' : '') : '',
                // 10
                $PERMS['can_moderate'] ? '<a href="?act=modcontrols&do=modt&tid=' .
                $f['id'] . '" class="moderate" onclick="RUN.modcontrols.togbutton(this)"></a>' : '',
                // 11
                $pages,
                // 12
                ($read = $this->isTopicRead($f, $fid)) ? 'read' : 'unread',
                // 13
                $read ? $JAX->pick(
                    $PAGE->meta('topic-icon-read'),
                    $PAGE->meta('icon-read')
                )
                : $JAX->pick(
                    $PAGE->meta('topic-icon-unread'),
                    $PAGE->meta('icon-read')
                )
                // 14
            );
            if (!$read) {
                $unread = true;
            }
        }
        // If they're on the first page and no topics
        // were marked as unread, mark the whole forum as read
        // since we don't care about pages past the first one.
        if (!$this->page && !$unread) {
            $this->markread($fid);
        }
        if ($rows) {
            $table = $PAGE->meta('forum-table', $rows);
        } else {
            if ($this->page > 0) {
                return $PAGE->location('?act=vf' . $fid);
            }
            if ($fdata['perms']['start']) {
                $table = $PAGE->error(
                    "This forum is empty! Don't like it? " .
                    "<a href='?act=post&amp;fid=" . $fid . "'>Create a topic!</a>"
                );
            }
        }
        $page .= $PAGE->meta('box', ' id="fid_' . $fid . '_listing"', $title, $table);
        $page .= $PAGE->meta('forum-pages-bottom', $forumpages);
        $page .= $PAGE->meta('forum-buttons-bottom', $forumbuttons);

        // Start building the nav path.
        $path[$fdata['category']['title']] = '?act=vc' . $fdata['cat_id'];
        if ($fdata['path']) {
            $pathids = explode(' ', $fdata['path']);
            $forums = array();
            $result = $DB->fetchResource('forums', [
                'ids' => implode(',', $pathids)
            ]);
            foreach ($result as $f) {
                $forums[$f['id']] = array($f['title'], '?act=vf' . $f['id']);
            }
            foreach ($pathids as $v) {
                $path[$forums[$v][0]] = $forums[$v][1];
            }
        }
        $path[$title] = "?act=vf${fid}";
        $PAGE->updatepath($path);
        if ($PAGE->jsaccess) {
            $PAGE->JS('update', 'page', $page);
        } else {
            $PAGE->append('PAGE', $page);
        }
    }

    public function getreplysummary($tid)
    {
        global $PAGE,$DB;
        $result = $DB->safespecial(
            <<<'EOT'
SELECT m.`display_name` AS `name`,COUNT(p.`id`) AS `replies`
FROM %t p
LEFT JOIN %t m
    ON p.`auth_id`=m.`id`
WHERE `tid`=?
GROUP BY p.`auth_id`
ORDER BY `replies` DESC
EOT
            ,
            array('posts', 'members'),
            $tid
        );
        $page = '';
        while ($f = $DB->arow($result)) {
            $page .= '<tr><td>' . $f['name'] . '</td><td>' . $f['replies'] . '</td></tr>';
        }
        $PAGE->JS('softurl');
        $PAGE->JS(
            'window',
            array(
                'title' => 'Post Summary',
                'content' => '<table>' . $page . '</table>',
            )
        );
    }

    public function update()
    {
        // Update the topic listing.
    }

    public function isTopicRead($topic, $fid)
    {
        global $SESS,$USER,$JAX,$PAGE;
        if (empty($this->topicsRead)) {
            $this->topicsRead = $JAX->parsereadmarkers($SESS->topicsread);
        }
        if (empty($this->forumsRead)) {
            $fr = $JAX->parsereadmarkers($SESS->forumsread);
            if (isset($fr[$fid])) {
                $this->forumReadTime = $fr[$fid];
            }
        }
        if (!isset($this->topicsRead[$topic['id']])) {
            $this->topicsRead[$topic['id']] = 0;
        }
        if ($topic['lp_date'] > $JAX->pick(
            max($this->topicsRead[$topic['id']], $this->forumReadTime),
            $SESS->read_date,
            $USER['last_visit']
        )
        ) {
            return false;
        }

        return true;
    }

    public function isForumRead($forum)
    {
        global $SESS,$USER,$JAX;
        if (!$this->forumsRead) {
            $this->forumsRead = $JAX->parsereadmarkers($SESS->forumsread);
        }
        if ($forum['lp_date'] > $JAX->pick(
            $this->forumsRead[$forum['id']],
            $SESS->read_date,
            $USER['last_visit']
        )
        ) {
            return false;
        }

        return true;
    }

    public function markread($id)
    {
        global $SESS,$JAX;
        $forumsread = $JAX->parsereadmarkers($SESS->forumsread);
        $forumsread[$id] = time();
        $SESS->forumsread = $JAX->base128encode($forumsread, true);
    }
}
