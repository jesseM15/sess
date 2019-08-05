<?php 

// Session hijacking refers to stealing the session cookie.
// 	- Prevented by https, session.cookie_httponly, session.cookie_secure
// Session fixation refers to setting another user's session id through URL or POST data
//  - Prevented by session.use_strict_mode

// TODO:
// Regenerate session id after x amount of time.
// This could be $defaults['regenerateId'] => '15 minutes' for example.

use SessionHandlers\DatabaseSessionHandler;

class Session
{
	protected $settings = [];
	protected $errors = [];

	public function __construct(array $settings = [])
	{
		$defaults = [
			'name' 			=> 'Universal_Session',
			'lifetime' 		=> '20 minutes',
			'path' 			=> '/',
			'domain' 		=> null,
			'https' 		=> isset($_SERVER['HTTPS']),
			'httpOnly' 		=> false,
			'saveHandler' 	=> 'files',
			'iniSettings' 	=> [
				'session.use_strict_mode' 			=> 1,
				'session.use_only_cookies' 			=> 1,
				'session.sid_length' 				=> 250,
				'session.sid_bits_per_character' 	=> 6,
				// 'session.gc_maxlifetime' 		=> 10,
				// 'session.gc_divisor' 			=> 2,
			],
			'database' 		=> [
				'host' 								=> 'localhost',
				'db' 								=> 'test',
				'user' 								=> '',
				'pass' 								=> '',
				'charset' 							=> 'utf8mb4',
			],
		];

		$settings['iniSettings'] = array_merge($defaults['iniSettings'], $settings['iniSettings'] ?? []);
		$settings['database'] = array_merge($defaults['database'], $settings['database'] ?? []);
		$settings = array_merge($defaults, $settings);

		if (is_string($settings['lifetime']))
		{
			$settings['lifetime'] = strtotime($settings['lifetime']) - time();
		}

		$this->settings = $settings;

		session_name($settings['name']);

		if (!$this->iniSet($settings['iniSettings']))
		{
			echo '<pre>';print_r($this->errors);die();
		}

		session_set_cookie_params($settings['lifetime'], $settings['path'], $settings['domain'], $settings['https'], $settings['httpOnly']);

		if ($settings['saveHandler'] === 'database' || $settings['saveHandler'] === 'db')
		{
			if (!empty($settings['database']['user']) && !empty($settings['database']['pass']))
			{
				$save_handler = new DatabaseSessionHandler($settings['database']);
				session_set_save_handler($save_handler, true);
			}
		}

		if (session_status() == PHP_SESSION_NONE)
		{
			session_start();
		}
	}

	protected function iniSet(array $settings) : bool
	{
		$errors = [];

		foreach ($settings as $key => $val)
		{
			if (strpos($key, 'session.') === 0)
			{
				if (ini_set($key, $val) === false)
				{
					$errors[] = 'Failed to set ini setting ' . $key . ' to ' . $val;
				}
			}
			else
			{
				$errors[] = 'Failed to set ini setting, ' . $key . ' must start with "session."';
			}
		}

		$this->errors = array_merge($this->errors, $errors);

		if (empty($errors))
		{
			return true;
		}

		return false; 
	}
}
