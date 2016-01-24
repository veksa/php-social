<?php

namespace Social\Api;


use Social\Auth\Token;
use Social\SexType;

class ApiMr extends Api
{
    private $profileUrl = 'http://www.appsmail.ru/platform/api';

    private $appId;
    private $appSecret;

    public function __construct($appId, $appSecret, Token $token)
    {
        parent::__construct($token);

        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public function getProfile()
    {
        $token = $this->getToken();

        $parameters = array(
            'client_id' => $this->appId,
            'uids' => $token->getIdentifier(),
            'secure' => '1',
            'method' => 'users.getInfo',
            'session_key' => $token->getAccessToken(),
        );

        $parameters['sig'] = $this->generateSig($parameters);

        $body = $this->execGet($this->profileUrl, $parameters);
        $data = json_decode($body, true);

        if (isset($data[0])) {
            $data = $data[0];
        }

        if (!isset($data['uid'])) {
            $this->setError('wrong_response');

            return null;
        }

        return $this->createUser($data);
    }

    protected function createUser($data)
    {
        $user = new User();
        $user->id = $data['uid'];
        $user->firstName = $data['first_name'];
        $user->lastName = $data['last_name'];
        $user->nickname = $data['nick'];
        $user->email = $data['email'];
        $user->screenName = $data['nick'];
        $user->profileUrl = $data['link'];
        $user->photoUrl = $data['pic_small'];
        $user->photoBigUrl = $data['pic_big'];
        $user->sex = $data['sex'] == 0 ? SexType::MALE : SexType::FEMALE;

        if (isset($data['birthday'])) {
            $user->birthDate = date('Y-m-d', strtotime($data['birthday']));
        }

        $user->info = $data;

        return $user;
    }

    private function generateSig($parameters)
    {
        ksort($parameters);
        $params = '';
        foreach ($parameters as $key => $value) {
            $params .= $key . '=' . $value;
        }

        return md5($params . $this->appSecret);
    }
    
    public function sendMultipost($data)
    {
        $token = $this->getToken();

        $parameters = [
            'method' => 'multipost.send',
            'client_id' => $this->appId,
            'uids' => $token->getIdentifier(),
            'secure' => '1',
            'session_key' => $token->getAccessToken(),
            'uid2' => $data['uid2']
        ];
        
        if (isset($data['text'])){
            $parameters['text'] = $data['text'];
        }
        if (isset($data['photo'])){
            $parameters['photo'] = $data['photo'];
        }
        if (isset($data['video'])){
            $parameters['video'] = $data['video'];
        }
        if (isset($data['audio'])){
            $parameters['audio'] = $data['audio'];
        }

        $parameters['sig'] = $this->generateSig($parameters);

        $body = $this->execGet($this->profileUrl, $parameters);
        $data = json_decode($body, true);
        
        if (isset($data['id'])) {
            return $data;
        }

        return false;
    }
    
    public function upload($data)
    {
        $token = $this->getToken();

        $parameters = [
            'method' => 'photos.upload',
            'client_id' => $this->appId,
            'uids' => $token->getIdentifier(),
            'secure' => '1',
            'session_key' => $token->getAccessToken(),
            'aid' => $data['uid']
        ];
        
        if (isset($data['img_file'])){
            $parameters['img_file'] = $data['img_file'];
        }

        $parameters['sig'] = $this->generateSig($parameters);
        
        $body = $this->execPost($this->profileUrl, $parameters);
        $data = json_decode($body, true);
        print_r($data); die();
        if (isset($data['id'])) {
            return $data;
        }

        return false;
    }
}