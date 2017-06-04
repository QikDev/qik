<?php 

namespace Qik\Core;

use Qik\Core\{APIControllerInterface, APIServer};
use Qik\Utility\{Utility};

abstract class APIController implements APIControllerInterface
{
	protected static $server;
	protected $filter;
	protected $response;

	public $_command;
	public $_requestType;
	public $_requestData;
	public $_user;

	public static function Configure(APIServer $server) {
		self::$server = $server;
	}

	public function Init() {
		$this->response = self::$server->GetResponse();
	}
	
	public function SetVars($vars = null)
	{
		if (empty($vars))
			$vars = array();

		$this->_vars = $vars;
	}

	public function GetAuthenticatedUser()
	{
		if (is_object($this->_user) && !empty($this->_user->id))
			return $this->_user;

		$header = xApi::GetRequestHeaderData('Authorization');
		$authorization = explode(' ', $header);

		if (count($authorization) < 2 || $authorization[0] != 'Bearer')
			return new SiteUser();
		else
		{
			$user = new User();
			$users = $user->GetRecords(array('authenticationToken'=>$authorization[1]));

			if (count($users) > 0)
				return new SiteUser($users[0]['id']);
			else
				return new SiteUser();
		}
	}

	public function SendUnauthorized()
	{
		$this->return->SendUnauthorized();
	}

	public function RequireAuthentication()
	{
		$user = $this->GetAuthenticatedUser();
		$this->return->DisableCache();

		if (empty($user->id))
			$this->SendUnauthorized();
	}

	public function GetOriginKey()
    {
        // expects CTE-Origin-Key to be set as a header
        return xApi::GetRequestHeaderData('CTE-Origin-Key');
    }

    public function SetRequestOrigin()
    {
        $this->_origin = new OrganizationOrigin($this->GetOriginKey());

        return $this->_origin;
    }

    public function GetOrganizationOrigin()
	{
		return $this->_origin;
	}

    public function VerifyOrigin()
    {
        if (empty($this->_origin->id))
            $this->SendUnverifiedOrigin();

        return true;
	}
}