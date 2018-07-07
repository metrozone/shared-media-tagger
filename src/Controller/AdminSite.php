<?php
declare(strict_types = 1);

namespace Attogram\SharedMedia\Tagger\Controller;

/**
 * Class AdminSite
 */
class AdminSite extends ControllerBase
{
    protected function display()
    {
        $site = $this->smt->database->getSite();
        $site['id'] = !empty($site['id']) ? (int) $site['id'] : 1;
        $site['name'] = !empty($site['name']) ? htmlentities((string) $site['name']) : '';
        $site['header'] = !empty($site['header']) ? htmlentities((string) $site['header']) : '';
        $site['footer'] = !empty($site['footer']) ? htmlentities((string) $site['footer']) : '';
        $site['use_cdn'] = !empty($site['use_cdn']) ? $site['use_cdn'] : false;
        $site['curation'] = !empty($site['curation']) ? $site['curation'] :false;
        $site['updated'] = !empty($site['updated']) ? htmlentities((string) $site['updated']) : '';

        header('X-XSS-Protection:0');

        $this->smt->title = 'Site Admin';
        $this->smt->includeHeader();
        $this->smt->includeTemplate('MenuSmall');
        $this->smt->includeAdminMenu();

        if (isset($_POST) && $_POST) {
            $this->smt->saveSiteInfo();
        }

        $view = $this->getView('AdminSite');
        /** @noinspection PhpIncludeInspection */
        include($view);
        $this->smt->includeFooter();
    }
}