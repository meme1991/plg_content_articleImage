<?php
# @Author: SPEDI srl
# @Date:   02-01-2018
# @Email:  sviluppo@spedi.it
# @Last modified by:   SPEDI srl
# @Last modified time: 15-02-2018
# @License: GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
# @Copyright: Copyright (c) SPEDI srl

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
if (version_compare(JVERSION, '1.6.0', 'ge')){
	jimport('joomla.html.parameter');
}

class plgContentArticleImage extends JPlugin {

	var $plg_name					= "articleimage";
	var $plg_tag					= "<img";

	// function plgContentArticleImage( &$subject, $params ){
	// 	parent::__construct( $subject, $params );
  //
	// 	// Define the DS constant under Joomla! 3.0+
	// 	if (!defined('DS')){
	// 		define('DS', DIRECTORY_SEPARATOR);
	// 	}
	// }

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
		$tmpl         = $mainframe->getTemplate();

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

		$document->addStyleSheet(JURI::base(true).'/plugins/content/articleimage/css/default.min.css');
		JHtml::_('jquery.framework');
		// magnificPopup
		$extensionPath = '/templates/'.$tmpl.'/dist/magnific/';
		if(file_exists(JPATH_SITE.$extensionPath)){
			$document->addStyleSheet(JUri::base(true).'/templates/'.$tmpl.'/dist/magnific/magnific-popup.min.css');
			$document->addScript(JUri::base(true).'/templates/'.$tmpl.'/dist/magnific/jquery.magnific-popup.min.js');
		}
		else{
			$document->addStyleSheet(JUri::base(true).'/plugins/content/'.$this->plg_name.'/dist/magnific/magnific-popup.min.css');
			$document->addScript(JUri::base(true).'/plugins/content/'.$this->plg_name.'/dist/magnific/jquery.magnific-popup.min.js');
		}

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

				// per vecchi JCE
				$style = (preg_match('/style="([^"]*)"/i',$value, $s)) ? 'style="'.$s[1].'"' : '';
				if(isset($s[1]) AND strpos($style, 'float')){
					if(strpos($style, 'left')){
						$float = 'float-left';
						$value = str_replace($style, '', $value);
					}
					if(strpos($style, 'right')){
						$float = 'float-right';
						$value = str_replace($style, '', $value);
					}
				}

				$titleLink = '';
				if(!empty($t[1])){
					$titleLink = 'title="'.$t[1].'"';
					$desc      = "<p class=\"bg-light px-2 py-1\">".$t[1]."</p>";
				}

				$a[$key]  = "<figure class=\"defaultVCNAbzN8 mb-0 ".$float."\">";
				$a[$key] .= $value;
				$a[$key] .= "<figcaption class=\"d-flex justify-content-center align-items-center\"><i class=\"far fa-search-plus fa-3x\"></i></figcaption>";
				$a[$key] .= "<a href=\"".$src[1]."\" ".$titleLink." class=\"magnific-article\" ".$title."></a>";
				$a[$key] .= "</figure>";
				if(!empty($t[1]) AND $float == '')
					$a[$key] .= $desc;

				// if(preg_match('/class="([^"]*)"/i',$value, $class))
				//
				// if(preg_match('/alt="([^"]*)"/i',$value, $alt))

				$row->text = str_replace($matches[0][$key], $a[$key], $row->text);

			}

		}


	} // END FUNCTION

} // END CLASS
