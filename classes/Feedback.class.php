<?php
/**
  * @since 02/2022
 */
class Feedback {
    
    public string $type;
    public string $message;
    public array $data;
    
    public function __construct(string $message='', string $type='info', array $data=array()) {
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
    }
    
    public function getMessage() {
        return !empty($this->message) ? $this->message : null;
    }

    public function setMessage(string $input) {
        return $this->message = $input;
    }
    public function getType() {
        return $this->type;
    }

    public function setType(string $input) {
        return $this->type = $input;
    }
    
    public function getData() {
        return $this->data;
    }

    public function addDatum(string $key, $datum) {
        if (!is_array($this->data)) $this->data = array();
        $this->data[$key] = $datum;
    }
    
    public function getDatum(string $key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
    
    public function messageToHtml() {
    	switch ($this->type) {
    		case 'success' :
    			$css_class = 'alert alert-success';
    			break;
    		case 'info' :
    			$css_class = 'alert alert-info';
    			break;
    		case 'warning' :
    			$css_class = 'alert alert-warning';
    			break;
    		case 'danger' :
    			$css_class = 'alert alert-danger';
    			break;
    	}
    	return '<div class="' . $css_class . '">'.ToolBox::toHtml($this->message).'</div>';
    }
    
    public function toJson() {
    	return json_encode($this, JSON_UNESCAPED_UNICODE);
    }
}