<?php
/**
* @package JFusion
* @subpackage Elements
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
* Require the Jfusion plugin factory
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');

/**
* Defines the jfusion usergroup assignments parameter
* @package JFusion
*/
class JElementDiscussionBotParam extends JElement
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'DiscussionBotParam';

    /**
     * Get an element
     *
     * @param string $name         name of element
     * @param string $value        value of element
     * @param JSimpleXMLElement &$node        node of element
     * @param string $control_name name of controller
     *
     * @return string|void html
     */
	function fetchElement($name, $value, &$node, $control_name)
	{
		$mainframe = JFactory::getApplication();

		$db			= JFactory::getDBO();
		$doc 		= JFactory::getDocument();
		$fieldName	= $control_name.'['.$name.']';

	    $query = 'SELECT params FROM #__plugins WHERE element = \'jfusion\' AND folder = \'content\'';
        $db->setQuery($query);
        $results = $db->loadResult();
        $pluginParams = new JParameter($results);

        $jname = $pluginParams->get('jname');

	 	if(empty($jname)) {
	 		return JText::_('NO_PLUGIN_SELECT');
	 	} else {
	 		static $js_loaded;
	 		if(empty($js_loaded)) {
                $js = <<<JS
                function jDiscussionParamSet(name, base64) {
					$(name + '_id').value = base64;
					$(name + '_img').src = 'components/com_jfusion/images/filesave.png';
					SqueezeBox.close();
				}
JS;
				$doc->addScriptDeclaration($js);
			    $js_loaded = 1;
	 		}

		    jimport( 'joomla.user.helper' );
		    $hash = JUtility::getHash( $name.JUserHelper::genRandomPassword());
		    $session = JFactory::getSession();
		    $session->set($hash, $value);

			$link = 'index.php?option=com_jfusion&amp;task=discussionbot&amp;tmpl=component&amp;jname='.$jname.'&amp;ename='.$name.'&amp;'.$name.'='.$hash;

			JHTML::_('behavior.modal', 'a.modal');

            if($pluginParams->get($name)) {
                $src = 'components/com_jfusion/images/tick.png';
            } else {
                $src = 'components/com_jfusion/images/clear.png';
            }

            $assign_paits = JText::_('ASSIGN_PAIRS');

            $html =<<<HTML
            <div class="button2-left">
                <div class="blank">
                    <a class="modal" id="{$name}_link" title="{$assign_paits}"  href="{$link}" rel="{handler: 'iframe', size: {x: 650, y: 375}}">{$assign_paits}</a>
                </div>
            </div>
            <img id="{$name}_img" src="{$src}">
            <input type="hidden" id="{$name}_id" name="{$fieldName}" value="{$value}" />
HTML;
			return $html;
	 	}
	}
}