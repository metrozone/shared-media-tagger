<?php
declare(strict_types = 1);
/**
 * Shared Media Tagger
 * Admin Home
 *
 * @var \Attogram\SharedMedia\Tagger\TaggerAdmin $smt
 * @var array $data
 */

use Attogram\SharedMedia\Tagger\Config;
use Attogram\SharedMedia\Tagger\Tools;

?>
<div class="box white">
<p>
    Site: <b><a href="./site"><?= Config::$siteName ?></a></b>
    <ul>
    <li><b><?= $data['messageCount'] ?></b>
        <a target="sqlite" href="sqladmin?table=contact&action=row_view">Messages</a></li>
    <li><b><?= sizeof($this->smt->database->getTags()) ?></b> <a href="./tags">Tags</a></li>
    <li><b><?= number_format((float) $this->smt->database->getImageCount()) ?></b> Files</li>
    <li><b><?= number_format((float) $this->smt->database->getBlockCount()) ?></b> Blocked Files</li>
    <li><b><?= number_format((float) $this->smt->database->getTotalFilesReviewedCount()) ?></b> Files reviewed</li>
    <li><b><?= number_format((float) $this->smt->database->getTaggingCount()) ?></b> Tagging Count</li>
    <li><b><?= number_format((float) $this->smt->database->getTotalReviewCount()) ?></b> Total Review Count</li>
    <li><b><?= number_format((float) $this->smt->database->getUserTagCount()) ?></b> User Tag Count</li>
    <li><b><?= number_format((float) $this->smt->database->getUserCount()) ?></b> Users</li>
    </ul>
</p>

<p>
    Installation:
    <ul>
    <li>Server: <?= Config::$server ?></li>
    <li>URL: <a href="<?= Tools::url('home') ?>"><?= Tools::url('home') ?></a></li>
    <li>Protocol: <?= Config::$protocol ?></li>
    <li>Directory: <?= Config::$installDirectory ?></li>
    <li>Setup: <?= (Config::$setup ? print_r(Config::$setup, true) : 'none') ?></li>
    </ul>
</p>

<p>Discovery / Restrictions:
    <ul>
    <li>/public/.htaccess:
        <?= (
    is_readable(Config::$installDirectory . '/public/.htaccess')
            ? '✔ACTIVE: '
            : '❌MISSING'
        ) ?></li>
    <li><a href="<?= Tools::url('sitemap') ?>">sitemap.xml</a></li>
    <li><a href="<?= Tools::url('home') ?>robots.txt">robots.txt</a>:
        <span style="font-family:monospace;"><?= $this->smt->checkRobotstxt() ?></span>
    </li>
    </ul>
</p>

<p>About Shared Media Tagger:
    <ul>
    <li> Github: <a target="commons"
                    href="https://github.com/attogram/shared-media-tagger">attogram/shared-media-tagger</a></li>
    <li><a target="commons"
           href="https://github.com/attogram/shared-media-tagger/blob/master/README.md">README</a></li>
    <li><a target="commons"
           href="https://github.com/attogram/shared-media-tagger/blob/master/LICENSE.md">LICENSE</a></li>
    </ul>
</p>

</div>
