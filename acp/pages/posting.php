<?php

if (!defined(INACP)) {
    die();
}

new settings();
class settings
{
    public function __construct()
    {
        global $JAX, $PAGE;

        $links = array(
            'emoticons' => 'Emoticons',
            'wordfilter' => 'Word Filter',
            'postrating' => 'Post Rating',
        );
        $sidebarLinks = '';
        foreach ($links as $do => $title) {
            $sidebarLinks .= $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/sidebar-list-link.html',
                array(
                    'url' => '?act=posting&do=' . $do,
                    'title' => $title,
                )
            ) . PHP_EOL;
        }

        $PAGE->sidebar(
            $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/sidebar-list.html',
                array(
                    'content' => $sidebarLinks,
                )
            )
        );

        if (!isset($JAX->b['do'])) {
            $JAX->b['do'] = false;
        }
        switch ($JAX->b['do']) {
            case 'emoticons':
                $this->emoticons();
                break;
            case 'wordfilter':
                $this->wordfilter();
                break;
            case 'postrating':
                $this->postrating();
                break;
        }
    }

    public function wordfilter()
    {
        global $PAGE,$JAX,$DB;
        $page = '';
        $wordfilter = array();
        $result = $DB->safeselect(
            '`id`,`type`,`needle`,`replacement`,`enabled`',
            'textrules',
            "WHERE `type`='badword'"
        );
        while ($f = $DB->arow($result)) {
            $wordfilter[$f['needle']] = $f['replacement'];
        }

        // Delete.
        if (isset($JAX->g['d']) && $JAX->g['d']) {
            $DB->safedelete(
                'textrules',
                "WHERE `type`='badword' AND `needle`=?",
                $DB->basicvalue($JAX->g['d'])
            );
            unset($wordfilter[$JAX->g['d']]);
        }

        // Insert.
        if (isset($JAX->p['submit']) && $JAX->p['submit']) {
            $JAX->p['badword'] = $JAX->blockhtml($JAX->p['badword']);
            if (!$JAX->p['badword'] || !$JAX->p['replacement']) {
                $page .= $PAGE->error('All fields required.');
            } elseif (isset($wordfilter[$JAX->p['badword']])
                && $wordfilter[$JAX->p['badword']]
            ) {
                $page .= $PAGE->error(
                    "'" . $JAX->p['badword'] . "' is already used."
                );
            } else {
                $DB->safeinsert(
                    'textrules',
                    array(
                        'type' => 'badword',
                        'needle' => $JAX->p['badword'],
                        'replacement' => $JAX->p['replacement'],
                    )
                );
                $wordfilter[$JAX->p['badword']] = $JAX->p['replacement'];
            }
        }
        if (empty($wordfilter)) {
            $table = $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/word-filter-empty.html'
            ) . PHP_EOL . $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/word-filter-submit-row.html'
            );
        } else {
            $table = $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/word-filter-heading.html'
            ) . PHP_EOL . $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/word-filter-submit-row.html'
            );
            $currentFilters = array_reverse($wordfilter, true);
            foreach ($currentFilters as $filter => $result) {
                $resultCode = $JAX->blockhtml($result);
                $filterUrlEncoded = rawurlencode($filter);
                $table .= $PAGE->parseTemplate(
                    JAXBOARDS_ROOT . '/acp/views/posting/word-filter-row.html',
                    array(
                        'filter' => $filter,
                        'result_code' => $resultCode,
                        'filter_url_encoded' => $filterUrlEncoded,
                    )
                ) . PHP_EOL;
            }
        }
        $page .= $PAGE->parseTemplate(
            JAXBOARDS_ROOT . '/acp/views/posting/word-filter.html',
            array(
                'content' => $table,
            )
        );

        $PAGE->addContentBox('Word Filter', $page);
    }

    public function emoticons()
    {
        global $PAGE,$JAX,$DB;

        $basesets = array(
            'keshaemotes' => "Kesha's pack",
            'ploadpack' => "Pload's pack",
            '' => 'None',
        );
        $page = '';
        $emoticons = array();
        // Delete emoticon.
        if (isset($JAX->g['d']) && $JAX->g['d']) {
            $DB->safedelete(
                'textrules',
                "WHERE `type`='emote' AND `needle`=?",
                $DB->basicvalue($_GET['d'])
            );
        }
        // Select emoticons.
        $result = $DB->safeselect(
            '`id`,`type`,`needle`,`replacement`,`enabled`',
            'textrules',
            "WHERE `type`='emote'"
        );
        while ($f = $DB->arow($result)) {
            $emoticons[$f['needle']] = $f['replacement'];
        }

        // Insert emoticon.
        if (isset($JAX->p['submit']) && $JAX->p['submit']) {
            if (!$JAX->p['emoticon'] || !$JAX->p['image']) {
                $page .= $PAGE->error('All fields required.');
            } elseif (isset($emoticons[$JAX->blockhtml($JAX->p['emoticon'])])) {
                $page .= $PAGE->error('That emoticon is already being used.');
            } else {
                $DB->safeinsert(
                    'textrules',
                    array(
                        'needle' => $JAX->blockhtml($JAX->p['emoticon']),
                        'replacement' => $JAX->p['image'],
                        'enabled' => 1,
                        'type' => 'emote',
                    )
                );
                $emoticons[$JAX->blockhtml($JAX->p['emoticon'])] = $JAX->p['image'];
            }
        }

        if (isset($JAX->p['baseset']) && $basesets[$JAX->p['baseset']]) {
            $PAGE->writeCFG(array('emotepack' => $JAX->p['baseset']));
        }
        if (empty($emoticons)) {
            $table = $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/emoticon-heading.html'
            ) . PHP_EOL . $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/emoticon-submit-row.html'
            ) . PHP_EOL . $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/emoticon-empty-row.html'
            );
        } else {
            $table = $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/emoticon-heading.html'
            ) . PHP_EOL . $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/emoticon-submit-row.html'
            );
            $emoticons = array_reverse($emoticons, true);

            foreach ($emoticons as $emoticon => $smileyFile) {
                $smileyFile = $JAX->blockhtml($smileyFile);
                $emoticonUrlEncoded = rawurlencode($emoticon);
                $table .= $PAGE->parseTemplate(
                    JAXBOARDS_ROOT . '/acp/views/posting/emoticon-row.html',
                    array(
                        'emoticon' => $emoticon,
                        'smiley_url' => $smileyFile,
                        'emoticon_url_encoded' => rawurlencode($emoticon),
                    )
                ) . PHP_EOL;
            }
        }
        $page .= $PAGE->parseTemplate(
            JAXBOARDS_ROOT . '/acp/views/posting/emoticons.html',
            array(
                'content' => $table,
            )
        );

        $PAGE->addContentBox('Custom Emoticons', $page);

        $emoticonpath = $PAGE->getCFGSetting('emotepack');
        $emoticonsetting = $emoticonpath;
        $emoticonPackOptions = '';
        foreach ($basesets as $packId => $packName) {
            $emoticonPackOptions .= $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/select-option.html',
                array(
                    'value' => $packId,
                    'label' => $packName,
                    'selected' => $emoticonsetting == $packId ?
                ' selected="selected"' : '',
                )
            );
        }

        include JAXBOARDS_ROOT . "/emoticons/${emoticonpath}/rules.php";
        $emoticonRows = '';
        foreach ($rules as $emoticon => $smileyFile) {
            $emoticonRows .= $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/emoticon-packs-row.html',
                array(
                    'emoticon' => $emoticon,
                    'smiley_url' => "/emoticons/${emoticonpath}/${smileyFile}",
                )
            ) . PHP_EOL;
        }
        $page = $PAGE->parseTemplate(
            JAXBOARDS_ROOT . '/acp/views/posting/emoticon-packs.html',
            array(
                'emoticon_packs' => $emoticonPackOptions,
                'emoticon_rows' => $emoticonRows,
            )
        );

        $PAGE->addContentBox('Base Emoticon Set', $page);
    }

    public function postrating()
    {
        global $PAGE,$JAX,$DB;
        $page = $page2 = '';
        $niblets = array();
        $result = $DB->safeselect(
            '`id`,`img`,`title`',
            'ratingniblets',
            'ORDER BY `id` DESC'
        );
        while ($f = $DB->arow($result)) {
            $niblets[$f['id']] = array('img' => $f['img'], 'title' => $f['title']);
        }

        // Delete.
        if (isset($JAX->g['d']) && $JAX->g['d']) {
            $DB->safedelete(
                'ratingniblets',
                'WHERE `id`=?',
                $DB->basicvalue($JAX->g['d'])
            );
            unset($niblets[$JAX->g['d']]);
        }

        // Insert.
        if (isset($JAX->p['submit']) && $JAX->p['submit']) {
            if (!$JAX->p['img'] || !$JAX->p['title']) {
                $page .= $PAGE->error('All fields required.');
            } else {
                $DB->safeinsert(
                    'ratingniblets',
                    array(
                        'img' => $JAX->p['img'],
                        'title' => $JAX->p['title'],
                    )
                );
                $niblets[$DB->insert_id(1)] = array(
                    'img' => $JAX->p['img'],
                    'title' => $JAX->p['title'],
                );
            }
        }

        if (isset($JAX->p['rsubmit']) && $JAX->p['rsubmit']) {
            $cfg = array(
                'ratings' => ($JAX->p['renabled'] ? 1 : 0) +
                ($JAX->p['ranon'] ? 2 : 0),
            );
            $PAGE->writeCFG($cfg);
            $page2 .= $PAGE->success('Settings saved!');
        }
        $ratingsettings = $PAGE->getCFGSetting('ratings');

        $page2 .= $PAGE->parseTemplate(
            JAXBOARDS_ROOT . '/acp/views/posting/post-rating-settings.html',
            array(
                'ratings_enabled' => $ratingsettings & 1 ? ' checked="checked"' : '',
                'ratings_anonymous' => $ratingsettings & 2 ? ' checked="checked"' : '',
            )
        );
        $table = $PAGE->parseTemplate(
            JAXBOARDS_ROOT . '/acp/views/posting/post-rating-heading.html'
        );
        if (empty($niblets)) {
            $table .= $PAGE->parseTemplate(
                JAXBOARDS_ROOT . '/acp/views/posting/post-rating-empty-row.html'
            );
        } else {
            krsort($niblets);
            foreach ($niblets as $ratingId => $rating) {
                $table .= $PAGE->parseTemplate(
                    JAXBOARDS_ROOT . '/acp/views/posting/post-rating-row.html',
                    array(
                        'id' => $ratingId,
                        'title' => $JAX->blockhtml($rating['title']),
                        'image_url' => $JAX->blockhtml($rating['img']),
                    )
                );
            }
        }
        $page .= $PAGE->parseTemplate(
            JAXBOARDS_ROOT . '/acp/views/posting/post-rating.html',
            array(
                'content' => $table,
            )
        );
        $PAGE->addContentBox('Post Rating System', $page2);
        $PAGE->addContentBox('Post Rating Niblets', $page);
    }
}
