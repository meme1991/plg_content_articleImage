<?php
/**
 * @Author: SPEDI srl (adapted)
 * @Date:   02-01-2018
 * @Last modified: adapted for Joomla 5
 * @License: GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\File;

/**
 * Content plugin to wrap images with a figure + magnific popup link
 */
class plgContentArticleImage extends CMSPlugin
{
    protected $plg_name = 'articleimage';
    protected $plg_tag  = '<img';

    /**
     * Event triggered before content is displayed
     *
     * @param   string  $context
     * @param   object  &$row
     * @param   mixed   &$params
     * @param   int     $page
     * @return  void
     */
    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        $this->renderArticleImage($row, $params, $page);
    }

    /**
     * Main function that processes the article text
     *
     * @param   object  &$row
     * @param   mixed   &$params
     * @param   int     $page
     * @return  void
     */
    protected function renderArticleImage(&$row, &$params, $page = 0)
    {
        // API
        $app      = Factory::getApplication();
        $doc      = Factory::getDocument();
        $db       = Factory::getDbo();
        $tmpl     = $app->getTemplate();
        $wa       = $doc->getWebAssetManager();

        // Check plugin enabled
        if (!PluginHelper::isEnabled('content', $this->plg_name)) {
            return;
        }

        // Allowed formats
        $allowedFormats = array('', 'html', 'feed', 'json');
        $format = $app->input->getCmd('format', '');
        if (!in_array($format, $allowedFormats, true)) {
            return;
        }

        // Quick check for performance
        if (!isset($row->text) || strpos($row->text, $this->plg_tag) === false) {
            return;
        }

        // Find all <img ...> tags
        $count_matches = preg_match_all('/<img[^>]+>/i', $row->text, $matches);
        if ($count_matches === 0) {
            return;
        }

        // Register / use plugin CSS
        // Prefer template's magnific assets if present, otherwise use plugin's
        // $pluginBase = Uri::base(true) . '/plugins/content/' . $this->plg_name;
        $templateMagnificPath = JPATH_SITE . '/templates/' . $tmpl . '/dist/magnific/';

        // Use WebAssetManager for assets (jQuery + CSS/JS)
        // Ensure jQuery is loaded
        try {
            $wa->useScript('jquery');
        } catch (\Exception $e) {
            // fallback to HTMLHelper if needed
            HTMLHelper::_('jquery.framework');
        }

        // Register and use plugin CSS
        if (file_exists($templateMagnificPath)) {
            // template provides magnific files
            $wa->registerAndUseStyle('template.light.magnific-css', 'media/templates/site/' . $tmpl . '/dist/magnific/magnific-popup.min.css');
            $wa->registerAndUseScript('template.light.magnific-js', 'media/templates/site/' . $tmpl . '/dist/magnific/jquery.magnific-popup.min.js');
        } else {
            // plugin fallback
            $wa->registerAndUseStyle('plg.content.' . $this->plg_name . '.magnific-css', 'media/plg_content_'. $this->plg_name .'/dist/magnific/magnific-popup.min.css');
            $wa->registerAndUseScript('plg.content.' . $this->plg_name . '.magnific-js', 'media/plg_content_'. $this->plg_name .'/dist/magnific/jquery.magnific-popup.min.js');
        }

        // plugin CSS (main)
        $wa->registerAndUseStyle('plg.content.' . $this->plg_name . '.css', 'media/plg_content_'. $this->plg_name .'/dist/css/default.min.css');

        // Add the small init script for magnific popup
        $doc->addScriptDeclaration("
            jQuery(document).ready(function($){
                $('.magnific-article').magnificPopup({
                    type: 'image',
                    closeOnContentClick: true
                });
            });
        ");

        // Process each image tag
        foreach ($matches[0] as $key => $value) {
            // get class attribute if any
            $class = array();
            preg_match('/class="([^"]*)"/i', $value, $class);

            // skip images with class plg-no-lightbox
            if (!empty($class[1]) && strpos($class[1], 'plg-no-lightbox') !== false) {
                continue;
            }

            // get src
            $src = array();
            preg_match('/src="([^"]*)"/i', $value, $src);
            $srcVal = isset($src[1]) ? $src[1] : '';

            // title (for link and optional caption)
            $t = array();
            $title = '';
            $desc  = '';
            if (preg_match('/title="([^"]*)"/i', $value, $t)) {
                $title = 'title="' . htmlspecialchars($t[1], ENT_QUOTES, 'UTF-8') . '"';
                $desc  = "<p class=\"bg-light px-2 py-1\">" . htmlspecialchars($t[1], ENT_QUOTES, 'UTF-8') . "</p>";
            }

            // handle float classes from editors (pull-left/pull-right) -> convert to float-left/float-right
            $float = '';
            if (!empty($class[1]) && strpos($class[1], 'pull-left') !== false) {
                $float = 'float-left';
                $value = str_replace('pull-left', '', $value);
            }
            if (!empty($class[1]) && strpos($class[1], 'pull-right') !== false) {
                $float = 'float-right';
                $value = str_replace('pull-right', '', $value);
            }

            // style float fallback (old editors)
            $s = array();
            if (preg_match('/style="([^"]*)"/i', $value, $s)) {
                $styleVal = isset($s[1]) ? $s[1] : '';
                if (stripos($styleVal, 'float') !== false) {
                    if (stripos($styleVal, 'left') !== false) {
                        $float = 'float-left';
                    }
                    if (stripos($styleVal, 'right') !== false) {
                        $float = 'float-right';
                    }
                    // remove inline float style
                    $value = str_replace($s[0], '', $value);
                }
            }

            // build replacement HTML
            $titleLink = (!empty($t[1])) ? 'title="' . htmlspecialchars($t[1], ENT_QUOTES, 'UTF-8') . '"' : '';
            $a = array();
            $a[] = '<figure class="defaultVCNAbzN8 mb-0 ' . $float . '">';
            $a[] = $value;
            $a[] = '<figcaption class="d-flex justify-content-center align-items-center"><i class="far fa-search-plus fa-3x"></i></figcaption>';
            $a[] = '<a href="'. htmlspecialchars($srcVal, ENT_QUOTES, 'UTF-8') .'" '. $titleLink .' class="magnific-article" ' . $title . '></a>';
            $a[] = '</figure>';
            if (!empty($t[1]) && $float === '') {
                $a[] = $desc;
            }
            $replacement = implode('', $a);

            // replace only the first occurrence of this exact tag (to avoid unintended replacements)
            $row->text = preg_replace('/' . preg_quote($matches[0][$key], '/') . '/', $replacement, $row->text, 1);
        }
    }
}
