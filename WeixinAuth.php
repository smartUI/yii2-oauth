<?php

namespace xj\oauth;

use yii\authclient\OAuth2;
use yii\authclient\OAuthToken;

/**
 * Weixin OAuth
 * @author xjflyttp <xjflyttp@gmail.com>
 * @see https://open.weixin.qq.com/cgi-bin/showdocument?action=doc&id=open1419316505&t=0.1933593254077447
 */
class WeixinAuth extends OAuth2 implements IAuth
{

    public $authUrl = 'https://open.weixin.qq.com/connect/qrconnect';
    public $tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    public $apiBaseUrl = 'https://api.weixin.qq.com';
    public $scope = 'snsapi_login';

    /**
     * Composes user authorization URL.
     * @param array $params additional auth GET params.
     * @return string authorization URL.
     */
    public function buildAuthUrl(array $params = [])
    {
        $defaultParams = [
            'appid' => $this->clientId,
            'redirect_uri' => $this->getReturnUrl(),
            'response_type' => 'code',
        ];
        if (!empty($this->scope)) {
            $defaultParams['scope'] = $this->scope;
        }

        return $this->composeUrl($this->authUrl, array_merge($defaultParams, $params));
    }

    /**
     * Fetches access token from authorization code.
     * @param string $authCode authorization code, usually comes at $_GET['code'].
     * @param array $params additional request params.
     * @return OAuthToken access token.
     */
    public function fetchAccessToken($authCode, array $params = [])
    {
        $defaultParams = [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret,
            'code' => $authCode,
            'grant_type' => 'authorization_code',
        ];
        $response = $this->sendRequest('POST', $this->tokenUrl, array_merge($defaultParams, $params));
        $token = $this->createToken(['params' => $response]);
        $this->setAccessToken($token);

        return $token;
    }

    /**
     * @inheritdoc
     */
    protected function apiInternal($accessToken, $url, $method, array $params, array $headers)
    {
        $params['access_token'] = $accessToken->getToken();
        $params['openid'] = $this->getOpenid();
        return $this->sendRequest($method, $url, $params, $headers);
    }

    /**
     *
     * @return []
     * @see https://open.weixin.qq.com/cgi-bin/showdocument?action=doc&id=open1419316518&t=0.14920092844688204
     */
    protected function initUserAttributes()
    {
        return $this->api('sns/userinfo');
    }

    /**
     * get UserInfo
     * @return array
     */
    public function getUserInfo()
    {
        return $this->getUserAttributes();
    }

    /**
     * @return string
     */
    public function getOpenid()
    {
        return $this->getAccessToken()->getParam('openid');
    }

    protected function defaultName()
    {
        return 'Weixin';
    }

    protected function defaultTitle()
    {
        return 'Weixin';
    }
}
