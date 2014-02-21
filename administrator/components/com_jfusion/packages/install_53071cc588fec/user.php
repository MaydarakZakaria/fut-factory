<?php


/**
 * JFusion User Class for PrestaShop
 * 
 * PHP version 5
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage PrestaShop
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */


// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * load the jplugin model
 */
require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jfusion' . DS . 'models' . DS . 'model.jplugin.php';

/**
 * JFusion User Class for PrestaShop
 * For detailed descriptions on these functions please check the model.abstractuser.php
 * 
 * @category   JFusion
 * @package    JFusionPlugins
 * @subpackage PrestaShop
 * @author     JFusion Team <webmaster@jfusion.org>
 * @copyright  2008 JFusion. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.jfusion.org
 */
class JFusionUser_prestashop extends JFusionUser {
    /**
     * @param object $userinfo
     *
     * @return null|object
     */
    function getUser($userinfo) {
	    //get the identifier
        $identifier = $userinfo;
        if (is_object($userinfo)) {
            $identifier = $userinfo->email;
        }
        // Get user info from database
		$db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT id_customer as userid, email, email as username, passwd as password, firstname, lastname, active FROM #__customer WHERE email =' . $db->Quote($identifier) ;
        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result) {
	        /**
	         * @ignore
	         * @var $helper JFusionHelper_prestashop
	         */
	        $helper = JFusionFactory::getHelper($this->getJname());

	        $result->block = 0;
	        $result->activation = '';
            $query = 'SELECT id_group FROM #__customer_group WHERE id_customer =' . $db->Quote($result->userid);
            $db->setQuery($query);
            $groups = $db->loadObjectList();

            if ($groups) {
                foreach($groups as $group) {
                    $result->groups[] = $result->group_id = $group->id_group;
	                $result->groupnames[] = $result->group_name = $helper->getGroupName($result->group_id);
                }
            }

	        if ($result->active) {
		        $result->activation = '';
	        } else {
		        jimport('joomla.user.helper');
		        $result->activation = JUserHelper::genRandomPassword();
	        }
        }

        // read through params for cookie key (the salt used)
        return $result;
    }

    /**
     * returns the name of this JFusion plugin
     *
     * @return string name of current JFusion plugin
     */    
    function getJname() 
    {
        return 'prestashop';
    }

    /**
     * @param object $userinfo
     *
     * @return array
     */
    function deleteUser($userinfo) {
        /* Warning: this function mimics the original prestashop function which is a suggestive deletion, 
		all user information remains in the table for past reference purposes. To delete everything associated
		with an account and an account itself, you will have to manually delete them from the table yourself. */
		// get the identifier
        $identifier = $userinfo;
        if (is_object($userinfo)) {
            $identifier = $userinfo->id_customer;
        }
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__customer SET deleted ="1" WHERE id_customer =' . $db->Quote($identifier);
        $db->setQuery($query);
		$status['debug'][] = 'Deleted user';
		return $status;
    }

    /**
     * @param object $userinfo
     * @param string $options
     *
     * @return array
     */
    function destroySession($userinfo, $options) {
	    $status = array('error' => array(),'debug' => array());
	    $params = JFusionFactory::getParams($this->getJname());

	    $status = JFusionJplugin::destroySession($userinfo, $options, $this->getJname(), $params->get('logout_type'));

	    return $status;
    }

    /**
     * @param object $userinfo
     * @param array $options
     * @param bool $framework
     *
     * @return array
     */
    function createSession($userinfo, $options, $framework = true) {
	    if (!empty($userinfo->block) || !empty($userinfo->activation)) {
		    $status['error'][] = JText::_('FUSION_BLOCKED_USER');
	    } else {
		    $params = JFusionFactory::getParams($this->getJname());
		    $status = JFusionJplugin::createSession($userinfo, $options, $this->getJname(), $params->get('brute_force'));
	    }
        return $status;
	}

    /**
     * @param object $userinfo
     * @param object $existinguser
     * @param array $status
     *
     * @return void
     */
    function updatePassword($userinfo, &$existinguser, &$status) {
	    /**
	     * @ignore
	     * @var $helper JFusionHelper_prestashop
	     */
	    $helper = JFusionFactory::getHelper($this->getJname());
	    $helper->loadFramework();

	    $existinguser->password = Tools::encrypt($userinfo->password_clear);

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__customer SET passwd =' . $db->Quote($existinguser->password) . ' WHERE id_customer =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password, 0, 6) . '********';
        }
    }

    /**
     * @param object $userinfo
     * @param array $status
     *
     * @return void
     */
    function createUser($userinfo, &$status) {
		$db = JFusionFactory::getDatabase($this->getJname());
	    $params = JFusionFactory::getParams($this->getJname());
        $errors = array();


	    $params = JFusionFactory::getParams($this->getJname());
	    $usergroups = JFusionFunction::getCorrectUserGroups($this->getJname(), null);

	    if (empty($usergroups)) {
		    $status['error'][] = JText::_('ERROR_CREATE_USER') . ' ' . JText::_('USERGROUP_MISSING');
	    } else {

		    /**
		     * @ignore
		     * @var $helper JFusionHelper_prestashop
		     */
		    $helper = JFusionFactory::getHelper($this->getJname());
		    $helper->loadFramework();

		    /* split full name into first and with/or without middlename, and lastname */
		    $users_name = $userinfo->name;
		    list( $uf_name, $um_name, $ul_name ) = explode( ' ', $users_name, 3 );
		    if ( is_null($ul_name) ) // meaning only two names were entered
		    {
			    $end_name = $um_name;
		    }
		    else
		    {
			    $end_name = explode( ' ', $ul_name );
			    $size = sizeof($ul_name);
			    $end_name = $ul_name[$size-1];
		    }
		    // now have first name as $uf_name, and last name as $end_name

		    if (isset($userinfo->password_clear)) {
			    $password = Tools::encrypt($userinfo->password_clear);
		    } else {
			    $password = $userinfo->password;
		    }

		    /* user variables submitted through form (emulated) */
		    $user_variables = array(
			    'id_gender' => "1", // value of either 1 for male, 2 for female
			    'firstname' => $uf_name, // alphanumeric values between 6 and 32 characters long
			    'lastname' => $end_name, // alphanumeric values between 6 and 32 characters long
			    'customer_firstname' => $uf_name, // alphanumeric values between 6 and 32 characters long
			    'customer_lastname' => $end_name, // alphanumeric values between 6 and 32 characters long
			    'email' => $userinfo->email, // alphanumeric values as well as @ and . symbols between 6 and 128 characters long
			    'passwd' => $password, // alphanumeric values between 6 and 32 characters long
			    'days' => "01", // numeric character between 1 and 31
			    'months' => "01", // numeric character between 1 and 12
			    'years' => "2000", // numeric character between 1900 and latest year
			    'newsletter' => 0, // value of either 0 for no newsletters, or 1 to relieve newsletters
			    'optin' => 0, // value of either 0 for no third party options, or 1 to relieve third party options
			    'company' => "", // alphanumeric values between 6 and 32 characters long
			    'address1' => "Update with your real address", // alphanumeric values between 6 and 128 characters long
			    'address2' => "", // alphanumeric values between 6 and 128 characters long
			    'postcode' => "Postcode", // alphanumeric values between 7 and 12 characters long
			    'city' => "Not known", // alpha values between 6 and 64 characters long
			    'id_country' => "17", // numeric character between 1 and 244 (normal preset)
			    'id_state' => "0", // numeric character between 1 and 65 (normal preset)
			    'other' => "", // alphanumeric values with mysql text limit characters long
			    'phone' => "", // numeric values between 11 and 16 characters long
			    'phone_mobile' => "", // numeric values between 11 and 16 characters long
			    'alias' => "My address", // alphanumeric values between 6 and 32 characters long
			    'dni' => "", // alphanumeric values between 6 and 16 characters long
		    );

		    $ps_customer = new stdClass;
		    $ps_customer->id_customer = null;
		    $ps_customer->id_gender = $user_variables['id_gender'];
		    $ps_customer->id_default_group = $usergroups[0];
		    $ps_customer->secure_key = md5(uniqid(rand(), true));
		    $ps_customer->email = $user_variables['email'];
		    $ps_customer->passwd = md5($params->get('cookie_key') . $user_variables['passwd']);
		    $ps_customer->last_passwd_gen = date('Y-m-d h:m:s',strtotime("-6 hours"));
		    $ps_customer->birthday = date('Y-m-d',mktime(0,0,0,$user_variables['months'],$user_variables['days'],$user_variables['years']));
		    $ps_customer->lastname = $user_variables['lastname'];
		    $ps_customer->newsletter = $_SERVER['REMOTE_ADDR'];
		    $ps_customer->ip_registration_newsletter = date('Y-m-d h:m:s');
		    $ps_customer->optin = $user_variables['optin'];
		    $ps_customer->firstname = $user_variables['firstname'];
		    $ps_customer->active = 1;
		    $ps_customer->deleted = 0;
		    $ps_customer->date_add = date('Y-m-d h:m:s');
		    $ps_customer->date_upd = date('Y-m-d h:m:s');

		    /* array to go into table ps_address */
		    $ps_address = new stdClass;
		    $ps_address->id_address = null;
		    $ps_address->id_country = $user_variables['id_country'];
		    $ps_address->id_state = $user_variables['id_state'];
		    $ps_address->id_manufacturer = 0;
		    $ps_address->id_supplier = 0;
		    $ps_address->alias = $user_variables['alias'];
		    $ps_address->company = $user_variables['company'];
		    $ps_address->lastname = $user_variables['customer_lastname'];
		    $ps_address->firstname = $user_variables['customer_firstname'];
		    $ps_address->address1 = $user_variables['address1'];
		    $ps_address->address2 = $user_variables['address2'];
		    $ps_address->postcode = $user_variables['postcode'];
		    $ps_address->city = $user_variables['city'];
		    $ps_address->other = $user_variables['other'];
		    $ps_address->phone = $user_variables['phone'];
		    $ps_address->phone_mobile = $user_variables['phone_mobile'];
		    $ps_address->date_add = date('Y-m-d h:m:s');
		    $ps_address->date_upd = date('Y-m-d h:m:s');
		    $ps_address->active = 1;
		    $ps_address->deleted = 0;

		    if (!Validate::isName($user_variables['firstname'])){
			    $status['error'][] = JText::_('USER_CREATION_ERROR').' '.Tools::displayError('first name wrong');
		    } elseif (!Validate::isName($user_variables['lastname'])){
			    $status['error'][] = JText::_('USER_CREATION_ERROR').' '.Tools::displayError('second name wrong');
		    } elseif (!Validate::isName($user_variables['customer_firstname'])){
			    $status['error'][] = JText::_('USER_CREATION_ERROR').' '.Tools::displayError('customer first name wrong');
		    } elseif (!Validate::isName($user_variables['customer_lastname'])){
			    $status['error'][] = JText::_('USER_CREATION_ERROR').' '.Tools::displayError('customer second name wrong');
		    } elseif (!Validate::isEmail($user_variables['email'])){
			    $status['error'][] = JText::_('USER_CREATION_ERROR').' '.Tools::displayError('e-mail not valid');
		    } elseif (!Validate::isPasswd($user_variables['passwd'])){
			    $status['error'][] = JText::_('USER_CREATION_ERROR').' '.Tools::displayError('invalid password');
		    } else {
			    /* enter customer account into prestashop database */ // if all information is validated
			    if ($db->insertObject('#__customer', $ps_customer, 'id_customer')) {
				    // enter customer group into database
				    $ps_address->id_customer = $ps_customer->id_customer;

				    foreach($usergroups as $value) {
					    $ps_customer_group = new stdClass;
					    $ps_customer_group->id_customer = $ps_customer->id_customer;
					    $ps_customer_group->id_group = $value;
					    if (!$db->insertObject('#__customer_group', $ps_customer_group)) {
						    $status['error'][] = JText::_('USER_CREATION_ERROR').' '. $db->stderr();
					    }
				    }

				    $db->insertObject('#__address', $ps_address);

				    $status['debug'][] = JText::_('USER_CREATION');
				    $status['userinfo'] = $this->getUser($userinfo);
			    } else {
				    $status['error'][] = JText::_('USER_CREATION_ERROR') .' '. $db->stderr();
			    }
		    }
	    }
    }

    /**
     * @param object $userinfo
     * @param object $existinguser
     * @param array $status
     *
     * @return void
     */
    function updateEmail($userinfo, &$existinguser, &$status) {
        //we need to update the email
		$params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__customer SET email =' . $db->Quote($userinfo->email) . ' WHERE id_customer =' . (int)$existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        } else {
            $status['debug'][] = JText::_('EMAIL_UPDATE') . ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }

    /**
     * @param object $userinfo
     * @param object $existinguser
     * @param array $status
     *
     * @return void
     */
    function activateUser($userinfo, &$existinguser, &$status) {
        /* change the 'active' field of the customer in the ps_customer table to 1 */
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__customer SET active =\'1\' WHERE id_customer =\'' . (int)$existinguser->userid . '\'';
        $db->setQuery($query);
	    $db->query();
    }

    /**
     * @param object $userinfo
     * @param object $existinguser
     * @param array $status
     *
     * @return void
     */
    function inactivateUser($userinfo, &$existinguser, &$status) {
        /* change the 'active' field of the customer in the ps_customer table to 0 */
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__customer SET active =\'0\' WHERE id_customer =\'' . (int)$existinguser->userid . '\'';
        $db->setQuery($query);
	    $db->query();
    }

    /**
     * @param object $userinfo
     * @param object $existinguser
     * @param array $status
     *
     * @return void
     */
    function updateUsergroup($userinfo, &$existinguser, &$status) {
        $usergroups = JFusionFunction::getCorrectUserGroups($this->getJname(),$userinfo);
        if (empty($usergroups)) {
            $status['error'][] = JText::_('GROUP_UPDATE_ERROR') . ": " . JText::_('USERGROUP_MISSING');
        } else {
            $db = JFusionFactory::getDatabase($this->getJname());
            // now delete the user
            $query = 'DELETE FROM #__customer_group WHERE id_customer = ' . $existinguser->userid;
            $db->setQuery($query);
            $db->query();
            if (!$db->query()) {
                $status['error'][] = JText::_('GROUP_UPDATE_ERROR') . $db->stderr();
            } else {
	            $query = 'UPDATE #__customer SET id_default_group = '.$db->Quote($usergroups[0]).' WHERE id_customer = \'' . (int)$existinguser->userid . '\'';
	            $db->setQuery($query);
	            $db->query();

                foreach($usergroups as $value) {
                    $group = new stdClass;
                    $group->id_customer = $existinguser->userid;
                    $group->id_group = $value;
                    if (!$db->insertObject('#__customer_group', $group)) {
                        $status['error'][] = JText::_('GROUP_UPDATE_ERROR') . $db->stderr();
                    } else {
                        $status['debug'][] = JText::_('GROUP_UPDATE'). ': ' . implode (' , ', $existinguser->groups) . ' -> ' . implode (' , ', $usergroups);
                    }
                }
            }
        }
    }
}