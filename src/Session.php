<?php 

use SessionHandlers\DatabaseSessionHandler;

class Session
{
	protected $settings = [];
	protected $errors = [];

	public function __construct(array $settings = [])
	{
		$defaults = [
			'name' 			=> 'jfry_session',
			'lifetime' 		=> '20 minutes',
			'path' 			=> '/',
			'domain' 		=> null,
			'https' 		=> true,
			'httpOnly' 		=> true,
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
				'db' 								=> '',
				'user' 								=> '',
				'pass' 								=> '',
				'charset' 							=> 'utf8mb4',
				'table' 							=> 'session',
			],
		];

		// Build settings
		$settings['iniSettings'] = array_merge($defaults['iniSettings'], $settings['iniSettings'] ?? []);
		$settings['database'] = array_merge($defaults['database'], $settings['database'] ?? []);
		$settings = array_merge($defaults, $settings);

		$this->settings = $settings;

		// Apply ini settings
		session_name($settings['name']);

		if (!$this->iniSet($settings['iniSettings']))
		{
			echo '<pre>';print_r($this->errors);die();
		}

		// Set save handler
		if ($settings['saveHandler'] === 'database' || $settings['saveHandler'] === 'db')
		{
			if (!empty($settings['database']['user']) && !empty($settings['database']['pass']))
			{
				$save_handler = new DatabaseSessionHandler($settings['database']);
				session_set_save_handler($save_handler, true);
			}
		}

		// Set cookie
		$settings['lifetime'] = strtotime($settings['lifetime']) - time();
		session_set_cookie_params($settings['lifetime'], $settings['path'], $settings['domain'], $settings['https'], $settings['httpOnly']);

		// Start session
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
