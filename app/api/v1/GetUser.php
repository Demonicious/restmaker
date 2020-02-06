<?php

class GetUser extends API_Method {
    public function __construct() {
        $this->setGroup('v1');
        $this->setRoute('get_user');
        $this->setMethod('GET');
        $this->setParameters(array(
            array(
                'name' => 'user_id',
                'default' => null,
                'required' => true,
            )
        ));
    }

    public function call() {
        $response = array(
            "code" => 200,
            "msg" => "Success"
        );

        return $this->respond($response);
    }
}