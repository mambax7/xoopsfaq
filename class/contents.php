<?php

namespace XoopsModules\Xoopsfaq;

/*
 You may not change or alter any portion of this comment or credits of
 supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit
 authors.

 This program is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Contents (FAQ) and Handler Class Definitions
 *
 * @package   module\xoopsfaq\class\contents
 * @author    John Neill
 * @author    XOOPS Module Development Team
 * @copyright Copyright (c) 2001-2017 {@link http://xoops.org XOOPS Project}
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License
 * @since     ::   1.23
 */

use XoopsModules\Xoopsfaq;

defined('XOOPS_ROOT_PATH') || die('Restricted access');

/**
 * Contents handles CRUD operations for FAQs
 *
 * @author   ::    John Neill
 * @copyright:: Copyright (c) 2009
 */
class Contents extends \XoopsObject
{
    /**
     * @var string contains this modules directory name
     */
    protected $dirname;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dirname = basename(dirname(__DIR__));
        xoops_load('constants', $this->dirname);

        parent::__construct();
        $this->initVar('contents_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('contents_cid', XOBJ_DTYPE_INT, Xoopsfaq\Constants::DEFAULT_CATEGORY, false);
        $this->initVar('contents_title', XOBJ_DTYPE_TXTBOX, null, true, 255);
        $this->initVar('contents_contents', XOBJ_DTYPE_TXTAREA, null, false);
        $this->initVar('contents_publish', XOBJ_DTYPE_INT, time(), false);
        $this->initVar('contents_weight', XOBJ_DTYPE_INT, Xoopsfaq\Constants::DEFAULT_WEIGHT, false);
        $this->initVar('contents_active', XOBJ_DTYPE_INT, Xoopsfaq\Constants::ACTIVE, false);
        $this->initVar('dohtml', XOBJ_DTYPE_INT, Xoopsfaq\Constants::SET, false);
        $this->initVar('doxcode', XOBJ_DTYPE_INT, Xoopsfaq\Constants::SET, false);
        $this->initVar('dosmiley', XOBJ_DTYPE_INT, Xoopsfaq\Constants::SET, false);
        $this->initVar('doimage', XOBJ_DTYPE_INT, Xoopsfaq\Constants::SET, false);
        $this->initVar('dobr', XOBJ_DTYPE_INT, Xoopsfaq\Constants::SET, false);
    }

    /**
     * Display Content (FAQ)
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getVar('contents_title', 's');
    }

    /**
     * Display the Content (FAQ) Editor form for Admin
     *
     * @return void
     */
    public function displayForm()
    {
        echo $this->renderForm();
    }

    /**
     * Displays the Content (FAQ) Editor form for Admin
     */
    public function renderForm()
    {
        /** @var CategoryHandler $categoryHandler */
        /** @var Xoopsfaq\Helper $helper */
        $helper          = \XoopsModules\Xoopsfaq\Helper::getHelper($this->dirname);
        $categoryHandler = $helper->getHandler('Category');
        $catCount        = $categoryHandler->getCount();
        if (empty($catCount)) {
            xoops_error(_AM_XOOPSFAQ_ERROR_NO_CATS_EXIST, '');
            xoops_cp_footer();
            exit();
        }

        require_once $GLOBALS['xoops']->path('/class/xoopsformloader.php');

        $caption = ($this->isNew()) ? _AM_XOOPSFAQ_CREATE_NEW : sprintf(_AM_XOOPSFAQ_MODIFY_ITEM, $this->getVar('contents_title'));
        $form    = new \XoopsThemeForm($caption, 'content', $_SERVER['REQUEST_URI'], 'post', true);
        //        $form->addElement(new \XoopsFormHiddenToken());
        $form->addElement(new \XoopsFormHidden('op', 'save'));
        $form->addElement(new \XoopsFormHidden('contents_id', $this->getVar('contents_id', 'e')));

        // Active
        $contents_active = new \XoopsFormRadioYN(_AM_XOOPSFAQ_E_CONTENTS_ACTIVE, 'contents_active', $this->getVar('contents_active', 'e'), ' ' . _YES . '', ' ' . _NO . '');
        $contents_active->setDescription(_AM_XOOPSFAQ_E_CONTENTS_ACTIVE_DESC);
        $form->addElement($contents_active, false);

        // Title
        $contents_title = new \XoopsFormText(_AM_XOOPSFAQ_E_CONTENTS_TITLE, 'contents_title', 50, 150, $this->getVar('contents_title', 'e'));
        $contents_title->setDescription(_AM_XOOPSFAQ_E_CONTENTS_TITLE_DESC);
        $form->addElement($contents_title, true);

        // Category
        $catCriteria        = new \CriteriaCompo();
        $catCriteria->order = 'ASC';
        $catCriteria->setSort('category_order');
        $objects      = $categoryHandler->getList($catCriteria);
        $contents_cid = new \XoopsFormSelect(_AM_XOOPSFAQ_E_CONTENTS_CATEGORY, 'contents_cid', $this->getVar('contents_cid', 'e'), 1, false);
        $contents_cid->setDescription(_AM_XOOPSFAQ_E_CONTENTS_CATEGORY_DESC);
        $contents_cid->addOptionArray($objects);
        $form->addElement($contents_cid);

        // Weight
        $contents_weight = new \XoopsFormText(_AM_XOOPSFAQ_E_CONTENTS_WEIGHT, 'contents_weight', 5, 5, $this->getVar('contents_weight', 'e'));
        $contents_weight->setDescription(_AM_XOOPSFAQ_E_CONTENTS_WEIGHT_DESC);
        $form->addElement($contents_weight, false);

        // Editor
        $editorConfigs = [];
        $options_tray  = new \XoopsFormElementTray(_AM_XOOPSFAQ_E_CONTENTS_CONTENT, '<br>');
        if (class_exists('XoopsFormEditor')) {
            // $editorConfigs = array('editor' => $GLOBALS['xoopsConfig']['general_editor'],
            $editorConfigs     = [
                'editor' => $helper->getConfig('use_wysiwyg', 'dhtmltextarea'),
                'rows'   => 25,
                'cols'   => '100%',
                'width'  => '100%',
                'height' => '600px',
                'name'   => 'contents_contents',
                'value'  => $this->getVar('contents_contents', 'e'),
            ];
            $contents_contents = new \XoopsFormEditor('', 'contents_contents', $editorConfigs);
        } else {
            $contents_contents = new \XoopsFormDhtmlTextArea('', 'contents_contents', $this->getVar('contents_contents', 'e'), '100%', '100%');
        }
        $options_tray->addElement($contents_contents);
        $options_tray->setDescription(_AM_XOOPSFAQ_E_CONTENTS_CONTENT_DESC);

        xoops_load('XoopsEditorHandler');
        $editorHandler = \XoopsEditorHandler::getInstance();
        $editorList    = $editorHandler->getList(true);
        if (isset($editorConfigs['editor']) && in_array($editorConfigs['editor'], array_flip($editorList))) {
            $form->addElement(new \XoopsFormHidden('dohtml', Xoopsfaq\Constants::NOTSET));
            $form->addElement(new \XoopsFormHidden('dobr', Xoopsfaq\Constants::SET));
        } else {
            $html_checkbox = new \XoopsFormCheckBox('', 'dohtml', $this->getVar('dohtml', 'e'));
            $html_checkbox->addOption(1, _AM_XOOPSFAQ_E_DOHTML);
            $options_tray->addElement($html_checkbox);

            $breaks_checkbox = new \XoopsFormCheckBox('', 'dobr', $this->getVar('dobr', 'e'));
            $breaks_checkbox->addOption(1, _AM_XOOPSFAQ_E_BREAKS);
            $options_tray->addElement($breaks_checkbox);
        }

        $doimage_checkbox = new \XoopsFormCheckBox('', 'doimage', $this->getVar('doimage', 'e'));
        $doimage_checkbox->addOption(1, _AM_XOOPSFAQ_E_DOIMAGE);
        $options_tray->addElement($doimage_checkbox);

        $xcodes_checkbox = new \XoopsFormCheckBox('', 'doxcode', $this->getVar('doxcode', 'e'));
        $xcodes_checkbox->addOption(1, _AM_XOOPSFAQ_E_DOXCODE);
        $options_tray->addElement($xcodes_checkbox);

        $smiley_checkbox = new \XoopsFormCheckBox('', 'dosmiley', $this->getVar('dosmiley', 'e'));
        $smiley_checkbox->addOption(1, _AM_XOOPSFAQ_E_DOSMILEY);
        $options_tray->addElement($smiley_checkbox);

        $form->addElement($options_tray);

        $contents_publish = new \XoopsFormTextDateSelect(_AM_XOOPSFAQ_E_CONTENTS_PUBLISH, 'contents_publish', 20, (int)$this->getVar('contents_publish'), $this->isNew());
        $contents_publish->setDescription(_AM_XOOPSFAQ_E_CONTENTS_PUBLISH_DESC);
        $form->addElement($contents_publish);

        $form->addElement(new \XoopsFormButtonTray('contents_form', _SUBMIT, 'submit'));

        return $form->render();
    }

    /**
     * Get the FAQ Active/Inactive icon to display
     *
     * @return string HTML <img> tag representing current active status
     */
    public function getActiveIcon()
    {
        if ($this->getVar('contents_active') > Xoopsfaq\Constants::INACTIVE) {
            $icon = '<img src="' . \Xmf\Module\Admin::iconUrl('green.gif', '16') . '" alt="' . _YES . '">';
        } else {
            $icon = '<img src="' . \Xmf\Module\Admin::iconUrl('red.gif', '16') . '" alt="' . _NO . '">';
        }
        return $icon;
    }

    /**
     * Get the timestamp for when Content (FAQ) was published
     *
     * @param int|string Unix timestamp
     *
     * @return string|bool formatted timestamp on success, false on failure
     */
    public function getPublished($timestamp = '')
    {
        if (!$this->getVar('contents_publish')) {
            return '';
        }
        return formatTimestamp($this->getVar('contents_publish'), $timestamp);
    }
}
