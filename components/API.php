<?php

class API_Method {
    public $group = '';
    public $route = '';
    public $method = 'GET';
    public $expectedParameters = array();
    public $input;
    public $current_uri;

    public function setGroup($group = "default") {
        $this->group = $group;
        return $this;
    }
    public function setRoute($route) {
        $this->route = $route;
        return $this;
    }
    public function setMethod($method = 'GET') {
        $error = false;
        switch(strtolower($method)) {
            case "post":
            break;
            case "get":
            break;
            case "put":
            break;
            case "patch":
            break;
            case "delete":
            break;
            default:
                $error = true;
        }
        if(!$error) {
            $this->method = strtoupper($method);
        }
        return $this;
    }
    public function setParameters($arrayOfParams) {
        if(count($arrayOfParams) > 0) { 
            foreach($arrayOfParams as $param) {
                $this->expectedParameters[$param['name']] = ['default' => $param['default'], 'required' => $param['required']];
            }
        }
        return $this;
    }

    public function respond($response = array()) {
        echo json_encode($response);
        return true;
    }

    public function emitInvalidError() {
        $contents = file_get_contents(__DIR__.'/view/invalid_err.html');
        $contents = str_replace('{METHOD}', $this->method, $contents);
        $contents = str_replace('{URI}', $this->current_uri, $contents);
        $li_items = "";
        foreach($this->expectedParameters as $index => $param) {
            $li_items .= '<li>';
            $li_items .= '<span class="bold">'.$index.'</span>';
            if($param['required']) $li_items .= ' - Required';
            $li_items .= '</li>';
        }
        $contents = str_replace('{LI_ITEMS}', $li_items, $contents);
        die($contents);
    }

    public function handle($vars, $headers, $uri) {
        $this->current_uri = $uri;
        $this->input = new StdClass();
        $this->input->headers = $headers;
        $this->input->post = $_POST;
        $this->input->get = $_GET;
        
        if($this->method == 'POST') {
            foreach($this->expectedParameters as $name => $info) {
                if(isset($_POST[$name])) {
                    $this->parameters[$name] = $_POST[$name];
                } else {
                    if($info['required']) {
                        $this->emitInvalidError();
                    } else {
                        $this->parameters[$name] = $info['default'];
                    }
                }
            }
        } else {
            foreach($this->expectedParameters as $name => $info) {
                if(isset($_GET[$name])) {
                    $this->parameters[$name] = $_GET[$name];
                } else {
                    if($info['required']) {
                        $this->emitInvalidError();
                    } else {
                        $this->parameters[$name] = $info['default'];
                    }
                }
            }
        }
        return $this->call();
    }
}