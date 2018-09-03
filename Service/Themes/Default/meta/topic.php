<?php

$meta = array(
    'topic-wrapper' => <<<'EOT'
<div class="box">
    <div class="title">
        <div class="topic-tools">
            %3$s
        </div>
        %1$s
    </div>
    <div class="content_top">
    </div>
    <div class="content">
        %2$s
    </div>
    <div class="content_bottom">
    </div>
</div>
EOT
    ,
    'topic-reply-form' => <<<'EOT'
<div class="topic-reply-form">
    <form onsubmit="
        RUN.submitForm(this,0,event);
        this.submitButton.disabled=true;
        return false;
    ">
        <input type="hidden" name="act" value="post" />
        <input type="hidden" name="how" value="qreply" />
        <input type="hidden" name="tid" value="%1$s" />
        <div class="replybox">
            <textarea name="postdata">%2$s</textarea>
        </div>
        <div class="submitbutton">
            <input type="submit" value="Post" name="submit"
                onclick="this.form.submitButton=this;">
            <input type="submit" name="submit" value="Full Reply"
                onclick="JAX.window.close(this);this.form.submitButton=this;" />
        </div>
    </form>
</div>
EOT
    ,
    'topic-pages-top' => <<<'EOT'
<div class="pages-top pages">
    %s
</div>
EOT
    ,
    'topic-pages-bottom' => <<<'EOT'
<div class="pages-bottom pages">
    %s
</div>
EOT
    ,
    'topic-pages-part' => <<<'EOT'
<a href="?act=vt%s&amp;page=%s"%s>
    %s
</a>

EOT
    ,
    'topic-buttons-top' => <<<'EOT'
<div class="topic-buttons-top">
    %s%s%s
</div>
EOT
    ,
    'topic-buttons-bottom' => <<<'EOT'
<div class="topic-buttons-bottom">
    %s%s%s
</div>
EOT
    ,
    'topic-users-online' => <<<'EOT'
<div class="box" id="topic-users-online">
    <div class="title">
        <div class="x" onclick="JAX.collapse(this.parentNode.nextSibling)">
            -/+
        </div>
        Users Viewing This Topic
    </div>
    <div class="collapse_content">
        <div class="content_top">
        </div>
        <div class="content">
            <div id="statusers" class="topicstatusers">
                %s
            </div>
        </div>
        <div class="content_bottom">
        </div>
    </div>
</div>
EOT
    ,
    'topic-button-reply' => 'Reply',
    'topic-button-newtopic' => 'New Topic ',
    'topic-button-qreply' => 'Quick Reply ',
    'topic-status-online' => 'Online!',
    'topic-status-offline' => 'Offline',
    'topic-edit-button' => 'Edit',
    'topic-quote-button' => 'Quote',
    'topic-mod-button' => 'Moderate',
    'topic-mod-ipbutton' => '%s',
    'topic-perma-button' => 'Perma-link',
    'topic-table' => <<<'EOT'
<table id="intopic">
    %s
</table>
EOT
    ,
    'topic-post-row' => <<<'EOT'
<tbody class="topic-post-row" id="pid_%1$s">
    <tr>
        <td class="userdata" rowspan="2">
            <div class="username">
                %3$s
            </div>
            <div class="avatar">
                <a href="?act=vu%15$s">
                    <img src="%4$s" alt="Avatar" />
                </a>
            </div>
            <div class="usertitle">
                %5$s
            </div>
            %18$s
            <div class="userstats">
                Posts: %6$s
                <br />
                Status: %7$s
                <br />
                Group: %8$s
                <br />
                Member: #%9$s
            </div>
        </td>
        <td class="post">
            <div class="post_buttons">
                %10$s %17$s
            </div>
            <div class="post_info">
                %11$s %12$s
            </div>
            <div class="post_content">
                %13$s%16$s
            </div>
            <div class="signature">
                %14$s
            </div>
        </td>
    </tr>
    <tr>
        <td class="postbottom">
            %30$s
            <a href="javascript:scroll(0,0)">
                ^ Top
            </a>
        </td>
    </tr>
</tbody>
EOT
    ,
    'topic-qedit-post' => <<<'EOT'
<form method="post"
    onsubmit="document.querySelector('#pdedit%3$s').editor.submit();return RUN.submitForm(this)">
    %1$s
    <textarea id="postdata%3$s" name="postdata">%2$s</textarea>
    <iframe id="pdedit%3$s" style="display:none"
        onload="new JAX.Editor(document.querySelector('#postdata%3$s'),this)">
    </iframe>
    <br />
    <input type="submit" value="Edit" />
    <a href="?act=post&amp;pid=%3$s">
        Full Edit
    </a>
</form>
EOT
    ,
    'topic-qedit-topic' => <<<'EOT'
<form method="post"
    onsubmit="document.querySelector('#pdedit').editor.submit();return RUN.submitForm(this)">
    %s
    Topic Title:
    <input type="text" name="ttitle" value="%s" />
    <br />
    Topic Description:
    <input type="text" name="tdesc" value="%s" />
    <br />
    <textarea id="postdata" name="postdata">%s</textarea>
    <iframe id="pdedit" onload="new JAX.Editor(document.querySelector('#postdata'),this)" style="display:none">
    </iframe>
    <br />
    <input type="submit" value="Edit" />
</form>
EOT
    ,
    'topic-edit-by' => <<<'EOT'
<br />
<strong>
    Edited by:
</strong>
%1$s, %2$s
EOT
    ,
    'topic-icon-wrapper' => <<<'EOT'
<div class="group_icon">
    <img src="%s" />
</div>
EOT
    ,
);
