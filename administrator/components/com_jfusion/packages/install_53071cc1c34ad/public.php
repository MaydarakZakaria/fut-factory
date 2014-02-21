<?php

/**
* @package JFusion_mediawiki
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

//require_once (JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.curlframeless.php');

/**
 * JFusion Public Class for mediawiki 1.1.x
 * For detailed descriptions on these functions please check the model.abstractpublic.php
 * @package JFusion_mediawiki
 */
class JFusionPublic_mediawiki extends JFusionPublic {
    /**
     * @return string
     */
    function getJname()
	{
		return 'mediawiki';
	}

    /**
     * @param $data
     */
    function _parseBody(&$data)
	{
	    $regex_body		= array();
	    $replace_body	= array();

		$regex_body[]	= '#addButton\("/(.*?)"#mS';
		$replace_body[]	= 'addButton("'.$data->integratedURL.'$1"';

	    $data->body = preg_replace($regex_body, $replace_body, $data->body);
	}

    /**
     * getSearchQueryColumns
     *
     * @return object
     */
    function getSearchQueryColumns()
	{
		$columns = new stdClass();
		$columns->title = 'p.page_title';
		$columns->text = 't.old_text';
		return $columns;
	}

    /**
     * @param object $pluginParam
     * @return string
     */
    function getSearchQuery(&$pluginParam)
	{
		$query = 'SELECT p.page_id , p.page_title AS title, t.old_text as text,
					STR_TO_DATE(p.page_touched, "%Y%m%d%H%i%S") AS created,
					p.page_title AS section
					FROM #__page AS p
					INNER JOIN #__revision AS r ON r.rev_page = p.page_id AND r.rev_id = p.page_latest
					INNER JOIN #__text AS t on t.old_id = r.rev_text_id';
		return $query;
	}

    /**
     * Add on a plugin specific clause;
     * @TODO permissions
     *
     * @param string &$where reference to where clause already generated by search bot; add on plugin specific criteria
     * @param object &$pluginParam custom plugin parameters in search.xml
     * @param string $ordering
     *
     * @return void
     */
	function getSearchCriteria(&$where, &$pluginParam, $ordering)
	{
	}

    /**
     * @param string $results
     * @param object $pluginParam
     *
     * @return void
     */
    function filterSearchResults(&$results, &$pluginParam)
	{
	}

    /**
     * @param mixed $post
     *
     * @return string
     */
    function getSearchResultLink($post)
	{
		return 'index.php?title='.$post->title;
	}
}