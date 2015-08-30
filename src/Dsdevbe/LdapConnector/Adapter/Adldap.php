<?php

namespace Dsdevbe\LdapConnector\Adapter;

use adLDAP\adLDAP as adLDAPService;
use Dsdevbe\LdapConnector\Model\User as UserModel;

class Adldap implements LdapInterface
{
    protected $_ldap;

    protected $_username;

    protected $_password;

    protected $_more_detail;

    protected function mapDataToUserModel($username, array $groups, $moreFields = false)
    {
	$dataUser = [
            'username' => $username,
            'password' => $this->_password,
        ];
        
        if ($moreFields && is_array($moreFields)) {
            foreach ($moreFields as $field => $value) {
                $dataUser[$field] = $value;
            }
        }
        
        $model = new UserModel($dataUser);
        $model->setGroups($groups);
        return $model;

    }

    public function __construct($config)
    {
        $this->_ldap = new adLDAPService($config);
	$this->_more_detail = $config['fields'];	
    }

    /**
     * @param String $username
     * @param String $password
     *
     * @return bool
     */
    public function connect($username, $password)
    {
        $this->_username = $username;
        $this->_password = $password;

        return $this->_ldap->authenticate($username, $password);
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return !!$this->_ldap->getLdapBind();
    }

    /**
     * @param String $username
     *
     * @return UserModel
     */
    public function getUserInfo($username)
    {
        $user = $this->_ldap->user()->info($username);

        if (!$user) {
            return;
        }

	$moreFields = array();
        if (isset($user[0]) && count($this->_more_detail) > 0) {
            foreach ($this->_more_detail as $field) {
                $moreFields[$field] = isset($user[0][$field][0]) ? $user[0][$field][0] : '';
            }            
        }

        $groups = $this->_ldap->user()->groups($username);
        return $this->mapDataToUserModel($username, $groups, $moreFields);
    }
}
