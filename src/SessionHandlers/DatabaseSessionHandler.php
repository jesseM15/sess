<?php 

namespace SessionHandlers;

use PDO;

class DatabaseSessionHandler implements \SessionHandlerInterface
{
	protected $settings;
	protected $pdo;

	public function __construct(array $settings)
	{
		$this->settings = $settings;
		$this->pdo = $this->connect($this->settings);
		$this->migrate();
		$this->preventCookieIdAssignment();
		$this->pdo = null;
	}

	protected function connect($settings)
	{
		$dsn = 'mysql:';
		$dsn .= 'host=' . $settings['host'] . ';';
		$dsn .= 'dbname=' . $settings['db'] . ';';
		$dsn .= 'charset=' . $settings['charset'] . ';';

		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		];

		try {
			return new PDO($dsn, $settings['user'], $settings['pass'], $options);
		} catch (\PDOException $e) {
			throw new \PDOException($e->getMessage(), (int)$e->getCode());
		}

		return false;
	}

	// rename this to query for simplicity
	protected function query($sql, $binds = [])
	{
		try {
			if (!$stmt = $this->pdo->prepare($sql))
			{
				throw new \Exception('DatabaseSessionHandler failed preparing sql');
			}

			if (!$stmt->execute($binds))
			{
				throw new \Exception('DatabaseSessionHandler failed executing sql');
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $stmt;
	}

	protected function migrate()
	{
		$sql = 'CREATE TABLE IF NOT EXISTS ' . $this->settings['table'] . '(id VARCHAR(250) PRIMARY KEY,data BLOB NULL,last_activity INT NULL);';
		$stmt = $this->query($sql);
		$stmt = null;

		return true;
	}

	// Prevents the session id being set by a cookie if it does not exist in the database
	// This is only useful if an attacker can get a cookie on to a client before they have one.
	protected function preventCookieIdAssignment()
	{
		if (!isset($_COOKIE[session_name()]))
		{
			return true;
		}
		$session_id = $_COOKIE[session_name()];

		$sql = 'SELECT id FROM ' . $this->settings['table'] . ' WHERE id = ? LIMIT 1;';
		$stmt = $this->query($sql, [$session_id]);
		if (!$stmt->fetch())
		{
			// Generate new session id if the id provided by cookie is not found in database.
			session_id(session_create_id());
		}
		$stmt = null;

		return true;
	}

	public function close()
	{
		$this->pdo = null;

		return true;
	}

	public function destroy($session_id)
	{
		$sql = 'DELETE FROM ' . $this->settings['table'] . ' WHERE id = ?';
		$stmt = $this->query($sql, [$session_id]);
		$stmt = null;

		return true;
	}

	public function gc($maxlifetime)
	{
		$maxTimeStamp = time() - $maxlifetime;
		$sql = 'DELETE FROM ' . $this->settings['table'] . ' WHERE last_activity < ? || last_activity is NULL;';
		$stmt = $this->query($sql, [$maxTimeStamp]);
		$stmt = null;

		return true;
	}

	public function open($save_path, $session_name)
	{
		$this->pdo = $this->connect($this->settings);

		return true;
	}

	public function read($session_id)
	{
		$sql = 'SELECT data FROM ' . $this->settings['table'] . ' WHERE id = ? LIMIT 1;';
		$stmt = $this->query($sql, [$session_id]);
		if($row = $stmt->fetch())
		{
			$data = $row['data'];
		}
		$stmt = null;

		return $data ?? '';
	}

	public function write($session_id, $session_data)
	{
		$sql = 'REPLACE INTO ' . $this->settings['table'] . ' (id, data, last_activity) VALUES(?, ?, ?);';
		$stmt = $this->query($sql, [$session_id, $session_data, time()]);
		$stmt = null;

		return true;
	}
}
