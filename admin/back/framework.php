<?php
	if(!isset($this_is_page) || $this_is_page != true) { exit(0); }

class Framework {
	private static $userClass = 'BaseUser';
	static function setUserClass($class) {
		self::$userClass = $class;
	}
	static function getUserClass() {
		return self::$userClass;
	}
	private static $DBConn = null;
	static function setDBConn($conn) {
		self::$DBConn = $conn;
	}
	static function getDBConn() {
		return self::$DBConn;
	}
	static function getRequestMethod() {
		return $_SERVER['REQUEST_METHOD'];
	}
	static function getRequestData() {
		return $_REQUEST;
	}
	static function getPost() {
		return $_POST;
	}
	static function getGet() {
		return $_GET;
	}
	static function redirect($url, $code = 301) {
		http_response_code($code);
		header("Location: $url");
		exit(0);
	}
	static function httpError($code) {
		http_response_code($code);
		exit(0);
	}

	static function uuid4() {
		// code get from here: http://stackoverflow.com/q/2040240
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}

}

class TemplateFunctions {
	private $parts = array();
	private $starts = array();
	private $appends = array();
	
	function start($name) {
		array_push($this->starts, $name);
		ob_start();
	}
	function append($name) {
		array_push($this->appends, $name);
		$this->start($name);
	}
	function end() {
		$name = array_pop($this->starts);
		
		if(end($this->appends) == $name) {
			array_pop($this->appends);
			$this->parts[$name] = ( isset($this->parts[$name]) ? $this->parts[$name] : '' ) . ob_get_contents();
		} else {
			$this->parts[$name] = ob_get_contents();
		}
		
		ob_end_clean();
	}
	function part($name) {
		return isset($this->parts[$name]) ? $this->parts[$name] : '';
	}

	public $parent = null;

	function extend($parent_template_file) {
		$this->parent = $parent_template_file;
	}
}

class Template {
	function __construct($template_file) {
		$this->template_file = $template_file;
	}
	function render($context = []) {
		$context['this_is_page'] = true;
		extract($context);
		$t = new TemplateFunctions();
		ob_start();
		include $this->template_file;
		if($t->parent) {
			include $t->parent;
		}
		$content = ob_get_contents();
		ob_end_clean();
	
		return $content;
	}
}

class BaseUser {
	public $id = null;
	public $password_hash = null;
	public $email = null;
	public $role = null;
	public $passwordHash = null;
	public $recovery_code = null;
	
	function __construct($user_id = null, $fields = []) {
		$this->id = $user_id;;
		foreach($fields as $field_name => $field_value) {
			$this->{$field_name} = $field_value;
		}
	}

	static function getUser($user_id) {
		if($user_id === null) {
			$userClass = Framework::getUserClass();
			return new $userClass(null);
		} else {
			throw new Exception('getUser should be implemented in your own user class');
		}
	}

	static function getAuthorize($login, $password) { return null; }
	static function getRecoveryCode($email) { return new Exception(); }
	static function passwordRecovery($recovery_code) { return new Exception(); }
	
	function is_authorized() {
		return $this->id !== null;
	}

	static function getPasswordHash($password) {
		return hash('sha512', $password);
	}

	function setPassword($password) {
		$this->passwordHash = hash('sha512', $password);
	}

	function checkRole($roles) {
		return in_array($this->role, $roles);
	}
}

class Session {
	function __construct() {
		$lifetime = 5*24*60*60;



		// http://stackoverflow.com/a/8311400
		session_start(); // ready to go!

		$now = time();
		if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) {
			// this session has worn out its welcome; kill it and start a brand new one
			session_unset();
			session_destroy();
			session_start();
		}

		// either new or old, it should live at most for another hour
		$_SESSION['discard_after'] = $now + $lifetime;







		$userClass = Framework::getUserClass();

		$this->user = $userClass::getUser(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
		
		if(!isset($_SESSION['messages'])) {
			$_SESSION['messages'] = [];
		}

	}

	function get($sess_var) {
		return $_SESSION[$sess_var];
	}
	function set($sess_var, $value) {
		$_SESSION[$sess_var] = $value;
	}

	function setUser($user) {
		if(is_int($user)) {
			$user = new User($user);
		}
		$this->set('user_id', $user->id);
		$this->user = $user;
	}

	function destroy() {
		if(session_status() != PHP_SESSION_NONE) {
			session_unset();
			session_destroy();
		}
	}

	function message($message) {
		array_push($_SESSION['messages'], $message);
	}

	function fetch_messages() {
		$res = $_SESSION['messages'];
		$_SESSION['messages'] = [];
		return $res;
	}
}

class Label {
	public $for = null;
	public $attrs = [];
	function __construct($text) {
		$this->text = $text;
	}
	function __toString() {
		$attrsString = '';
		foreach($this->attrs as $attr => $value) {
			$attrsString .= " $attr='$value' ";
		}
		$res = "<label for='{$this->for}' {$attrsString}>{$this->text}</label>";
		return $res;
	}
}
class Field {
	public $tag = 'input';
	public $tag_pair = false;
	public $attrs = ['type' => 'text'];
	public $required = true;
	public $validator = null;
	public $is_valid = true;
	public $error = null;
	public $label = null;
	public $value = null;
	public $template = null;
	public $help_text = null;

	function __construct($options = []) {
		if(isset($options['label'])) {
			$this->label = $options['label'];
		}
		if(isset($options['value'])) {
			$this->value = $options['value'];
		}
		if(isset($options['required'])) {
			$this->required = $options['required'];
		}

		if(isset($options['validator'])) {
			$this->validator = $options['validator'];
		}
	}

	function __toString() {
		$attrsString = '';
		foreach($this->attrs as $attr => $value) {
			$attrsString .= " $attr='$value' ";
		}
		$res = "<{$this->tag} {$attrsString} " . (isset($this->value) ? " value='{$this->value}' " : '') .  ">" . ($this->tag_pair ? "</{$this->tag}>": '');

		return $res;
	}

	function validate() {
		if($this->required && empty($this->value)) {
			$this->is_valid = false;
			$this->error = 'Field required';
		} else if ($this->validator) {
			if(is_callable($this->validator)) {
				$validator = $this->validator;
				$this->is_valid = $validator($this->value);
			} else {
				if(!empty($this->value)) {
					$this->is_valid = preg_match($this->validator, $this->value) == 1 ? true : false;
				}
			}

			if(!$this->is_valid) {
				// set default error, if validator didn't set one
				if(!$this->error) {
					$this->error = 'Field invalid';
				}
			}
		} else {
			$this->is_valid = true;
		}

		return $this->is_valid;
	}

	function render() {
		if($this->template) {
			return $this->template->render(['field' => $this]);
		}
		return $this->__toString();
	}

}

class EmailField extends Field {
	public $validator = '/^[a-z0-9\._-]+@[a-z0-9\._-]+\.[a-z]{2,5}$/';
}

class PasswordField extends Field {
	public $attrs = ['type' => 'password'];
}

class HiddenField extends Field {
	public $attrs = ['type' => 'hidden'];
}
class DateField extends Field {
	function validator() {
		$date = date_parse($this->value);
		if($date == false) {
			return false;
		} else {
			$this->value = "$date[year]-$date[month]-$date[day]";
			return true;
		}
	}
	public $attrs = ['type' => 'date'];
}
class CharField extends Field {
	public $attrs = ['type' => 'text'];
}
class TextField extends Field {
	public $tag = 'textarea';
	public $tag_pair = true;
	public $attrs = [ 'rows' => 5 ];

	function __toString() {
		$attrsString = '';
		foreach($this->attrs as $attr => $value) {
			$attrsString .= " $attr='$value' ";
		}
		
		$res = "<{$this->tag} {$attrsString}>" . ($this->value ? $this->value : '') . ($this->tag_pair ? "</{$this->tag}>": '');

		return $res;
	}
}
class IntField extends Field {
	public $validator = '/^[0-9]+$/';
	public $attrs = ['type' => 'text'];
}

class FloatField extends Field {
	public $validator = '/^[0-9]+([.,][0-9]+)?$/';
	public $attrs = ['type' => 'text'];
}

class SelectField extends Field {
	public $tag = 'select';
	public $tag_pair = true;
	public $attrs = [];

	function __construct($options) {
		$this->values = $options['values'];

		parent::__construct($options);
	}
	function __toString() {
		$attrsString = '';
		foreach($this->attrs as $attr => $value) {
			$attrsString .= " $attr='$value' ";
		}

		
		$res = "<{$this->tag} {$attrsString}>";

		foreach($this->values as $key => $value) {
			$res .= "<option value='$key'" . ($this->value == $key ? 'selected' : '') . ">$value</option>";
		}

		$res .= ($this->tag_pair ? "</{$this->tag}>": '');

		return $res;
	}

	function validate() {
		$this->is_valid = array_key_exists($this->value, $this->values);

		if(!$this->is_valid) {
			$this->error = 'Field invalid';
		};

		return $this->is_valid;
	}
}


class MultiselectField extends SelectField {
	public $attrs = ['multiple' => 'multiple'];
	public $values = [];
	public $value = [];

	function __toString() {
		if(substr($this->attrs['name'], -2) != '[]') {
			$this->attrs['name'] = $this->attrs['name'] . '[]';
		}
		

		$attrsString = '';
		foreach($this->attrs as $attr => $value) {
			$attrsString .= " $attr='$value' ";
		}

		
		$res = "<{$this->tag} {$attrsString}>";

		foreach($this->values as $key => $value) {
			$res .= "<option value='$key'" . (in_array($key, $this->value) ? 'selected' : '') . ">$value</option>";
		}

		$res .= ($this->tag_pair ? "</{$this->tag}>": '');

		return $res;



	}

	function validate() {
		for($i = 0; $i < count($this->value); ++$i) {
			$val = $this->value[$i];
			if(!array_key_exists($val, $this->values)) {
				$this->is_valid = false;
				break;
			}
		}

		return $this->is_valid;
	}
}

class CheckboxField extends Field {
	public $attrs = [ 'type' => 'checkbox', 'value' => 'true' ];
	function __toString() {
		if($this->value) {
			$this->attrs['checked'] = 'checked';
		}

		$attrsString = '';
		foreach($this->attrs as $attr => $value) {
			$attrsString .= " $attr='$value' ";
		}
		$res = "<{$this->tag} {$attrsString} " . (isset($this->value) ? " value='{$this->value}' " : '') .  ">";

		$res = "<label> $res {$this->label->text}</label>";

		return $res;
	}

	// function validate() {
	// 	// do something with $required
	// }
}



class BooleanField extends CheckboxField {
	
}

abstract class Form {
	private $fields_arr = [];
	public $values = false;
	public $is_valid = true;
	public $errors = [];
	public $validator = null;

	function __construct() {
		$this->init();

		foreach(get_object_vars($this) as $name => $field) {
			if($field instanceof Field) {
				$this->fields_arr[$name] = $field;
			}
		}

		foreach ($this->fields_arr as $name => $field) {
			$field->attrs['name'] = $name;
			
			$field->attrs['id'] = 'field_' . $name;
			if(!$field->label) {
				$field->label = new Label(str_replace('_', ' ', ucwords($name)));
			}
			$field->label->for = $field->attrs['id'];
		}
	}

	function validate($values) {
		$this->values = $values;
		$this->is_valid = count($this->errors) == 0;

		// set values
		foreach ($this->fields_arr as $name => $field) {
			$value = isset($this->values[$name]) ? $this->values[$name] : '';
			$field->value = $value;
			$filed->attrs['value'] = $value;
		}

		// do validate
		foreach ($this->fields_arr as $name => $field) {
			if(!$field->validate()) {
				$this->is_valid = false;
			}
		}

		if(is_callable($this->validator)) {
			$validator = $this->validator;

			if(!$validator()) {
				$this->is_valid = false;
			}
		}

		return $this->is_valid;
	}

	function addError($message) {
		$this->is_valid = false;
		array_push($this->errors, $message);
	}
}



class BaseEmail {
	public $title_suffix = '';
	public $title = '';
	public $title_prefix = '';
	public $body = '';

	function send($to) {

		$title = $this->title_prefix . $this->title . $this->title_suffix;

		$message = $this->body;
		$message = preg_replace("/(?!\r)\n/", "\r\n", $message);
		$message = wordwrap($message, 70, "\r\n");

		mail($to, $title, $message);
	}

}