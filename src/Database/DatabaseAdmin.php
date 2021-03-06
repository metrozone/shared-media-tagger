<?php
declare(strict_types = 1);

namespace Attogram\SharedMedia\Tagger\Database;

use Attogram\SharedMedia\Tagger\Commons;
use Attogram\SharedMedia\Tagger\Tools;

/**
 * Class DatabaseAdmin
 */
class DatabaseAdmin extends Database
{
    public $categoryId;

    /** @var Commons */
    private $commons;

    /**
     * DatabaseAdmin constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Commons $commons
     */
    public function setCommons($commons)
    {
        $this->commons = $commons;
    }

    // Media

    /**
     * @param array $media
     * @return bool
     */
    public function saveMediaToDatabase(array $media)
    {
        if (!$media || !is_array($media)) {
            Tools::error('saveMediaToDatabase: no media array');

            return false;
        }
        $errors = [];
        $this->beginTransaction();
        foreach ($media as $id => $mediaFile) {
            $new = [];
            $new[':pageid'] = !empty($mediaFile['pageid']) ? $mediaFile['pageid'] : '';
            $new[':title'] = !empty($mediaFile['title']) ? $mediaFile['title'] : '';
            $new[':url'] = !empty($mediaFile['imageinfo'][0]['url']) ? $mediaFile['imageinfo'][0]['url'] : '';
            if (!isset($new[':url']) || $new[':url'] == '') {
                Tools::error(
                    '::save_media_to_database: ERROR: NO URL: SKIPPING: pageid='
                    . (!empty($new[':pageid']) ? $new[':pageid'] : '?')
                    . ' title='
                    . (!empty($new[':title']) ? $new[':title'] : '?')
                );
                $errors[ $new[':pageid'] ] = $new[':title'];
                continue;
            }
            $new[':descriptionurl'] = !empty($mediaFile['imageinfo'][0]['descriptionurl'])
                ? $mediaFile['imageinfo'][0]['descriptionurl'] : '';
            $new[':descriptionshorturl'] = !empty($mediaFile['imageinfo'][0]['descriptionshorturl'])
                ? $mediaFile['imageinfo'][0]['descriptionshorturl'] : '';
            $new[':imagedescription'] = !empty($mediaFile['imageinfo'][0]['extmetadata']['ImageDescription']['value'])
                ? $mediaFile['imageinfo'][0]['extmetadata']['ImageDescription']['value'] : '';
            $new[':artist'] = !empty($mediaFile['imageinfo'][0]['extmetadata']['Artist']['value'])
                ? $mediaFile['imageinfo'][0]['extmetadata']['Artist']['value'] : '';
            $new[':datetimeoriginal'] = !empty($mediaFile['imageinfo'][0]['extmetadata']['DateTimeOriginal']['value'])
                ? $mediaFile['imageinfo'][0]['extmetadata']['DateTimeOriginal']['value'] : '';
            $new[':licenseshortname'] = !empty($mediaFile['imageinfo'][0]['extmetadata']['LicenseShortName']['value'])
                ? $mediaFile['imageinfo'][0]['extmetadata']['LicenseShortName']['value'] : '';
            $new[':usageterms'] = !empty($mediaFile['imageinfo'][0]['extmetadata']['UsageTerms']['value'])
                ? $mediaFile['imageinfo'][0]['extmetadata']['UsageTerms']['value'] : '';
            $new[':attributionrequired'] =
                !empty($mediaFile['imageinfo'][0]['extmetadata']['AttributionRequired']['value'])
                ? $mediaFile['imageinfo'][0]['extmetadata']['AttributionRequired']['value'] : '';
            $new[':restrictions'] = !empty($mediaFile['imageinfo'][0]['extmetadata']['Restrictions']['value'])
                ? $mediaFile['imageinfo'][0]['extmetadata']['Restrictions']['value'] : '';
            $new[':licenseuri'] = Tools::openContentLicenseUri($new[':licenseshortname']);
            $new[':licensename'] = Tools::openContentLicenseName($new[':licenseuri']);
            $new[':size'] = !empty($mediaFile['imageinfo'][0]['size'])
                ? $mediaFile['imageinfo'][0]['size'] : '';
            $new[':width'] = !empty($mediaFile['imageinfo'][0]['width'])
                ? $mediaFile['imageinfo'][0]['width'] : '';
            $new[':height'] = !empty($mediaFile['imageinfo'][0]['height'])
                ? $mediaFile['imageinfo'][0]['height'] : '';
            $new[':sha1'] = !empty($mediaFile['imageinfo'][0]['sha1'])
                ? $mediaFile['imageinfo'][0]['sha1'] : '';
            $new[':mime'] = !empty($mediaFile['imageinfo'][0]['mime'])
                ? $mediaFile['imageinfo'][0]['mime'] : '';
            $new[':thumburl'] = !empty($mediaFile['imageinfo'][0]['thumburl'])
                ? $mediaFile['imageinfo'][0]['thumburl'] : '';
            $new[':thumbwidth'] = !empty($mediaFile['imageinfo'][0]['thumbwidth'])
                ? $mediaFile['imageinfo'][0]['thumbwidth'] : '';
            $new[':thumbheight'] = !empty($mediaFile['imageinfo'][0]['thumbheight'])
                ? $mediaFile['imageinfo'][0]['thumbheight'] : '';
            $new[':thumbmime'] = !empty($mediaFile['imageinfo'][0]['thumbmime'])
                ? $mediaFile['imageinfo'][0]['thumbmime'] : '';
            $new[':user'] = !empty($mediaFile['imageinfo'][0]['user'])
                ? $mediaFile['imageinfo'][0]['user'] : '';
            $new[':userid'] = !empty($mediaFile['imageinfo'][0]['userid'])
                ? $mediaFile['imageinfo'][0]['userid'] : '';
            $new[':duration'] = !empty($mediaFile['imageinfo'][0]['duration'])
                ? $mediaFile['imageinfo'][0]['duration'] : '';
            $new[':timestamp'] = !empty($mediaFile['imageinfo'][0]['timestamp'])
                ? $mediaFile['imageinfo'][0]['timestamp'] : '';
            $sql = "INSERT OR REPLACE INTO media (
                        pageid, title, url,
                        descriptionurl, descriptionshorturl, imagedescription,
                        artist, datetimeoriginal,
                        licenseuri, licensename, licenseshortname, usageterms, attributionrequired, restrictions,
                        size, width, height, sha1, mime,
                        thumburl, thumbwidth, thumbheight, thumbmime,
                        user, userid, duration, timestamp
                    ) VALUES (
                        :pageid, :title, :url,
                        :descriptionurl, :descriptionshorturl, :imagedescription,
                        :artist, :datetimeoriginal,
                        :licenseuri, :licensename, :licenseshortname, :usageterms, :attributionrequired, :restrictions,
                        :size, :width, :height, :sha1, :mime,
                        :thumburl, :thumbwidth, :thumbheight, :thumbmime,
                        :user, :userid, :duration, :timestamp
                    )";
            $response = $this->queryAsBool($sql, $new);
            if ($response === false) {
                Tools::error('saveMediaToDatabase: STOPPING IMPORT: FAILED insert into media table');

                return false;
            }
            Tools::notice('SAVED MEDIA: ' . $new[':pageid'] . ' = <a href="' . Tools::url('info')
                . '/' . $new[':pageid'] . '">' . Tools::stripPrefix($new[':title']) . '</a>');
            if (!$this->linkMediaCategories($new[':pageid'])) {
                Tools::error('::: FAILED to link media categories - p:' . $new[':pageid']);
            }
        }
        $this->commit();
        $this->vacuum();
        if ($errors) {
            Tools::error($errors);
        }

        return true;
    }

    /**
     * @param string $category
     * @return bool
     */
    public function getMediaFromCategory($category = '')
    {
        $category = trim($category);
        if (!$category) {
            return false;
        }
        $category = ucfirst($category);
        if (!preg_match('/^[Category:]/i', $category)) {
            $category = 'Category:' . $category;
        }
        $categorymembers = $this->commons->getApiCategorymembers($category);
        if (!$categorymembers) {
            Tools::error('::getMediaFromCategory: No Media Found');

            return false;
        }
        $blocked = $this->queryAsArray(
            'SELECT pageid FROM block WHERE pageid IN ('
            . implode($categorymembers, ',')
            . ')'
        );
        if ($blocked) {
            Tools::error('ERROR: ' . sizeof($blocked) . ' BLOCKED MEDIA FILES');
            foreach ($blocked as $bpageid) {
                if (($key = array_search($bpageid['pageid'], $categorymembers)) !== false) {
                    unset($categorymembers[$key]);
                }
            }
        }
        $chunks = array_chunk($categorymembers, 50);
        foreach ($chunks as $chunk) {
            $this->saveMediaToDatabase($this->commons->getApiImageinfo($chunk));
        }
        $this->updateCategoryLocalFilesCount($category);
        $this->saveCategoryInfo($category);

        return true;
    }

    /**
     * @param int|string $pageid
     * @param bool $noBlock
     * @return bool|string
     */
    public function deleteMedia($pageid, $noBlock = false)
    {
        if (!$pageid || !Tools::isPositiveNumber($pageid)) {
            Tools::error('delete_media: Invalid PageID');
            return false;
        }
        $response = '<div style="white-space:nowrap;font-family:monospace;color:black;background-color:lightsalmon;">'
            . 'Deleting Media :pageid = ' . $pageid;
        $media = $this->getMedia($pageid);
        if (!$media) {
            $response .= '<p>Media Not Found</p></div>';

            return $response;
        }
        $sqls = [];
        $sqls[] = 'DELETE FROM media WHERE pageid = :pageid';
        $sqls[] = 'DELETE FROM category2media WHERE media_pageid = :pageid';
        $sqls[] = 'DELETE FROM tagging WHERE media_pageid = :pageid';
        $bind = [':pageid' => $pageid];
        foreach ($sqls as $sql) {
            if ($this->queryAsBool($sql, $bind)) {
                //$response .= '<br />OK: ' . $sql;
            } else {
                $response .= '<br />ERROR: ' . $sql;
            }
        }
        if ($noBlock) {
            return $response . '</div>';
        }
        $sql = 'INSERT INTO block (pageid, title, thumb) VALUES (:pageid, :title, :thumb)';
        $bind = [
            ':pageid' => $pageid,
            ':title' => !empty($media[0]['title']) ? $media[0]['title'] : null,
            ':thumb' => !empty($media[0]['thumburl']) ? $media[0]['thumburl'] : null,
        ];
        if ($this->queryAsBool($sql, $bind)) {
            //$response .= '<br />OK: ' . $sql;
        } else {
            $response .= '<br />ERROR: ' . $sql;
        }

        return $response . '</div>';
    }

    /**
     * @param int|string $pageid
     * @return bool
     */
    public function linkMediaCategories($pageid)
    {
        if (!$pageid || !Tools::isPositiveNumber($pageid)) {
            Tools::error('link_media_categories: invalid pageid');

            return false;
        }
        if (!$this->commons->getCategoriesFromMedia($pageid)) {
            Tools::error('linkMediaCategories: unable to get categories from API');

            return false;
        }
        // Remove any old category links for this media
        $this->queryAsBool(
            'DELETE FROM category2media WHERE media_pageid = :pageid',
            [':pageid' => $pageid]
        );
        foreach ($this->commons->categories as $category) {
            if (!isset($category['title']) || !$category['title']) {
                Tools::error('linkMediaCategories: ERROR: missing category title');
                continue;
            }
            if (!isset($category['ns']) || $category['ns'] != '14') {
                Tools::error('linkMediaCategories: ERROR: invalid category namespace');
                continue;
            }
            $categoryId = $this->getCategoryIdFromName($category['title']);
            if (!$categoryId) {
                if (!$this->insertCategory($category['title'], true, 1)) {
                    Tools::error('linkMediaCategories: FAILED to insert ' . $category['title']);
                    continue;
                }
                $categoryId = $this->categoryId;
            }
            if (!$this->linkMediaToCategory($pageid, $categoryId)) {
                Tools::error('linkMediaCategories: FAILED to link category');
                continue;
            }
        }

        return true;
    }

    /**
     * @param int|string $pageid
     * @param int|string $categoryId
     * @return bool
     */
    private function linkMediaToCategory($pageid, $categoryId)
    {
        $response = $this->queryAsBool(
            'INSERT INTO category2media (category_id, media_pageid) VALUES (:category_id, :pageid)',
            ['category_id' => $categoryId, 'pageid' => $pageid]
        );
        if (!$response) {
            return false;
        }

        return true;
    }

    // Category

    /**
     * @param string $name
     * @param bool $fillInfo
     * @param int $localFiles
     * @return bool
     */
    public function insertCategory($name = '', $fillInfo = true, $localFiles = 0)
    {
        if (!$name) {
            Tools::error('insert_category: no name found');

            return false;
        }

        if (!$this->queryAsBool(
            'INSERT INTO category (
                name, local_files, hidden, missing
            ) VALUES (
                :name, :local_files, :hidden, :missing
            )',
            [
                ':name' => $name,
                ':local_files' => $localFiles,
                ':hidden' => '0',
                ':missing' => '0'
            ]
        )
        ) {
            Tools::error('insert_category: FAILED to insert: ' . $name);

            return false;
        }
        $this->categoryId = $this->lastInsertId;
        if ($fillInfo) {
            $this->saveCategoryInfo($name);
        }
        Tools::notice(
            'SAVED CATEGORY: ' . $this->categoryId . ' = +<a href="'
            . Tools::url('category') . '/'
            . Tools::categoryUrlencode(Tools::stripPrefix($name))
            . '">' . htmlentities((string) Tools::stripPrefix($name)) . '</a>'
        );

        return true;
    }

    /**
     * @param string $categoryName
     * @return bool
     */
    public function saveCategoryInfo($categoryName)
    {
        $categoryName = Tools::categoryUrldecode($categoryName);
        $categoryRow = $this->getCategory($categoryName);
        if (!$categoryRow) {
            if (!$this->insertCategory($categoryName, false, 1)) {
                Tools::error('saveCategoryInfo: new category INSERT FAILED: ' . $categoryName);

                return false;
            }
            Tools::notice('saveCategoryInfo: NEW CATEGORY: '  . $categoryName);
            $categoryRow = $this->getCategory($categoryName);
        }
        $categoryInfo = $this->commons->getCategoryInfo($categoryName);
        foreach ($categoryInfo as $onesy) {
            $categoryInfo = $onesy; // is always just 1 result
        }
        $bind = [];
        if (!empty($categoryInfo['pageid']) && ($categoryInfo['pageid'] != $categoryRow['pageid'])) {
            $bind[':pageid'] = $categoryInfo['pageid'];
        }
        if ($categoryInfo['categoryinfo']['files'] != $categoryRow['files']) {
            $bind[':files'] = $categoryInfo['categoryinfo']['files'];
        }
        if ($categoryInfo['categoryinfo']['subcats'] != $categoryRow['subcats']) {
            $bind[':subcats'] = $categoryInfo['categoryinfo']['subcats'];
        }
        $hidden = 0;
        if (isset($categoryInfo['categoryinfo']['hidden'])) {
            $hidden = 1;
        }
        if ($hidden != $categoryRow['hidden']) {
            $bind[':hidden'] = $hidden;
        }
        $missing = 0;
        if (isset($categoryInfo['categoryinfo']['missing'])) {
            $missing = 1;
        }
        if ($missing != $categoryRow['missing']) {
            $bind[':missing'] = $missing;
        }
        if (!$bind) {
            //Tools::debug('saveCategoryInfo: Category OK. nothing to update');
            return true;
        }
        $sql = 'UPDATE category SET ';
        $sets = [];
        foreach (array_keys($bind) as $set) {
            $sets[] = str_replace(':', '', $set) . ' = ' . $set;
        }
        $sql .= implode($sets, ', ');
        $sql .= ' WHERE id = :id';
        $bind[':id'] = $categoryRow['id'];
        $result = $this->queryAsBool($sql, $bind);
        if ($result) {
            return true;
        }
        Tools::error(
            'saveCategoryInfo: UPDATE/INSERT FAILED: ' . print_r($this->lastError, true)
        );

        return false;
    }

    /**
     * @param string $categoryName
     * @return bool
     */
    public function updateCategoryLocalFilesCount($categoryName)
    {
        $sql = 'UPDATE category SET local_files = :local_files WHERE id = :id';
        $bind[':local_files'] = $this->getCategorySize($categoryName);
        if (is_int($categoryName)) {
            $bind['id'] = $categoryName;
        } else {
            $bind[':id'] = $this->getCategoryIdFromName($categoryName);
        }

        if (!$bind[':id']) {
            Tools::error("update_category_local_files_count( $categoryName ) - Category Not Found in Database");

            return false;
        }
        if ($this->queryAsBool($sql, $bind)) {
            Tools::notice('UPDATE CATEGORY SIZE: ' . $bind[':local_files'] . ' files in ' . $categoryName);

            return true;
        }
        Tools::error("update_category_local_files_count( $categoryName ) - UPDATE ERROR");

        return false;
    }

    /**
     * updateCategoriesLocalFilesCount
     */
    public function updateCategoriesLocalFilesCount()
    {
        $sql = '
            SELECT c.id, c.local_files, count(c2m.category_id) AS size
            FROM category AS c
            LEFT JOIN category2media AS c2m ON c.id = c2m.category_id
            GROUP BY c.id
            ORDER by c.local_files ASC';

        $categoryNewSizes = $this->queryAsArray($sql);
        if (!$categoryNewSizes) {
            Tools::error('NOT FOUND: Updated 0 Categories Local Files count');

            return;
        }
        $updates = 0;
        $this->beginTransaction();
        foreach ($categoryNewSizes as $cat) {
            if ($cat['local_files'] == $cat['size']) {
                continue;
            }
            if ($this->insertCategoryLocalFilesCount($cat['id'], $cat['size'])) {
                $updates++;
            } else {
                Tools::error('ERROR: UPDATE FAILED: Category ID:' . $cat['id'] . ' local_files=' . $cat['size']);
            }
        }
        $this->commit();
        Tools::notice('Updated ' . $updates . ' Categories Local Files count');
        $this->vacuum();
    }

    /**
     * @param int|string $categoryId
     * @param int|string $categorySize
     * @return bool
     */
    private function insertCategoryLocalFilesCount($categoryId, $categorySize)
    {
        $sql = 'UPDATE category SET local_files = :category_size WHERE id = :category_id';
        $bind[':category_size'] = $categorySize;
        $bind[':category_id'] = $categoryId;
        if ($this->queryAsBool($sql, $bind)) {
            return true;
        }

        return false;
    }

    /**
     * @param int|string $categoryId
     * @return bool
     */
    public function deleteCategory($categoryId)
    {
        if (!Tools::isPositiveNumber($categoryId)) {
            return false;
        }
        $bind = [':category_id' => $categoryId];
        if ($this->queryAsBool('DELETE FROM category WHERE id = :category_id', $bind)) {
            Tools::notice('DELETED Category #' . $categoryId);
        } else {
            Tools::error('UNABLE to delete category #' . $categoryId);

            return false;
        }
        if ($this->queryAsBool('DELETE FROM category2media WHERE category_id = :category_id', $bind)) {
            Tools::notice('DELETED Links to Category #' . $categoryId);
        } else {
            Tools::error('UNABLE to delete links to category #' . $categoryId);

            return false;
        }

        return true;
    }

    // Empty / Drop

    /**
     * @param array $sqls
     * @return array
     */
    private function runSqls($sqls)
    {
        $response = [];
        foreach ($sqls as $sql) {
            if ($this->queryAsBool($sql)) {
                $response[] = 'OK: ' . $sql;
            } else {
                $response[] = 'FAIL: ' . $sql;
            }
        }
        $this->vacuum();

        return $response;
    }

    /**
     * @return array
     */
    public function emptyCategoryTables()
    {
        return $this->runSqls(
            [
                'DELETE FROM category2media',
                'DELETE FROM category',
            ]
        );
    }

    /**
     * @return array
     */
    public function emptyTaggingTables()
    {
        return $this->runSqls(
            [
                'DELETE FROM tagging',
            ]
        );
    }

    /**
     * @return array
     */
    public function emptyMediaTables()
    {
        return $this->runSqls(
            [
                'DELETE FROM tagging',
                'DELETE FROM category2media',
                'DELETE FROM media',
                'DELETE FROM block',
            ]
        );
    }

    /**
     * @return array
     */
    public function emptyUserTables()
    {
        return $this->runSqls(
            [
                'DELETE FROM user',
                'DELETE FROM tagging',
            ]
        );
    }

    /**
     * @return array
     */
    public function dropTables()
    {
        return $this->runSqls(
            [
                'DROP TABLE IF EXISTS block',
                'DROP TABLE IF EXISTS category',
                'DROP TABLE IF EXISTS category2media',
                'DROP TABLE IF EXISTS media',
                'DROP TABLE IF EXISTS site',
                'DROP TABLE IF EXISTS tag',
                'DROP TABLE IF EXISTS tagging',
                'DROP TABLE IF EXISTS user',
            ]
        );
    }

    // Block

    /**
     * @return int
     */
    public function getBlockCount()
    {
        $count = $this->queryAsArray('SELECT count(pageid) AS count FROM block');
        if (isset($count[0]['count'])) {
            return $count[0]['count'];
        }

        return 0;
    }
}
