<?php
/**
 * @version		1.0.0
 * @package		SPEDI plugins
 * @author    	JoomlaWorks - http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2014 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
if (version_compare(JVERSION, '1.6.0', 'ge')){
	jimport('joomla.html.parameter');
}

class plgContentArticleImage extends JPlugin {

	var $plg_name					= "articleimage";
	var $plg_tag					= "<img";

	function plgContentArticleImage( &$subject, $params ){
		parent::__construct( $subject, $params );

		// Define the DS constant under Joomla! 3.0+
		if (!defined('DS')){
			define('DS', DIRECTORY_SEPARATOR);
		}
	}

	// Joomla! 2.5+
	function onContentPrepare($context, &$row, &$params, $page = 0){
		$this->renderArticleImage($row, $params, $page = 0);
	}

	// The main function
	function renderArticleImage(&$row, &$params, $page = 0){

		// API
		jimport('joomla.filesystem.file');
		$mainframe    = JFactory::getApplication();
		$document     = JFactory::getDocument();
		$db           = JFactory::getDbo();
		//$siteTemplate = $mainframe->getTemplate();

		// Check se il plugin è attivato
		if (JPluginHelper::isEnabled('content', $this->plg_name) == false) return;

		// Salvare se il formato della pagina non è quello che vogliamo
		$allowedFormats = array('', 'html', 'feed', 'json');
		if (!in_array(JRequest::getCmd('format'), $allowedFormats)) return;

		// Controllo semplice delle prestazioni per determinare se il plugin dovrebbe elaborare ulteriormente
		if (JString::strpos($row->text, $this->plg_tag) === false) return;

		// // Start Plugin
		// $regex_one		= '/({spPhGallery\s*)(.*?)(})/si';
		// $regex_all		= '/{spPhGallery\s*.*?}/si';
		// //$matches 		= array();
		// $count_matches	= preg_match_all($regex_all,$row->text,$matches,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);

		// estraggo i tag d'immagine
		$count_matches = preg_match_all('/<img[^>]+>/i',$row->text, $matches);
		// ---> in $matches[0] ho tutti i tag <img>

		// mi fermo se non ci sono occorrenze
		if(($count_matches) == 0) return;

		$tmpl     = JFactory::getApplication()->getTemplate();
		$document->addStyleSheet(JUri::base(true).'/templates/'.$tmpl.'/dist/magnific/magnific-popup.min.css');
		//$document->addStyleSheet(JURI::base(true).'/plugins/content/articleimage/css/default.min.css');
		JHtml::_('jquery.framework');
		$document->addScript(JUri::base(true).'/templates/'.$tmpl.'/dist/magnific/jquery.magnific-popup.min.js');
		$document->addScriptDeclaration("
			jQuery(document).ready(function(a){
			  a('.magnific-article').magnificPopup({
			    type: 'image',
					closeOnContentClick: true
			  })
			});
		");

		foreach ($matches[0] as $key => $value) {

			preg_match('/class="([^"]*)"/i',$value, $class);

			if(empty($class[1]) || (strpos($class[1], 'plg-no-lightbox') === false)){
				preg_match('/src="([^"]*)"/i',$value, $src);

				$title = (preg_match('/title="([^"]*)"/i',$value, $t)) ? 'title="'.$t[1].'"' : '';

				// se l'editor è tinymice
				$float = '';
				if(!empty($class[1]) && strpos($class[1], 'pull-left') !== false){
					$float = 'float-left';
					$value = str_replace('pull-left', '', $value);
				}
				if(!empty($class[1]) && strpos($class[1], 'pull-right') !== false){
					$float = 'float-right';
					$value = str_replace('pull-right', '', $value);
				}

				$titleLink = '';
				if(!empty($t[1])){
					$titleLink = 'title="'.$t[1].'"';
					$desc      = "<p class=\"bg-light px-2 py-1\">".$t[1]."</p>";
				}

				$a[$key]  = "<figure class=\"default mb-0 ".$float."\">";
				$a[$key] .= $value;
				$a[$key] .= "<figcaption class=\"d-flex justify-content-center align-items-center\"><i class=\"fa fa-search-plus fa-3x\" aria-hidden=\"true\"></i></figcaption>";
				$a[$key] .= "<a href=\"".$src[1]."\" ".$titleLink." class=\"magnific-article\" ".$title."></a>";
				$a[$key] .= "</figure>";
				if(!empty($t[1]))
					$a[$key] .= $desc;

				// if(preg_match('/class="([^"]*)"/i',$value, $class))
				//
				// if(preg_match('/alt="([^"]*)"/i',$value, $alt))

				$row->text = str_replace($matches[0][$key], $a[$key], $row->text);

			}

		}


	} // END FUNCTION

} // END CLASS
