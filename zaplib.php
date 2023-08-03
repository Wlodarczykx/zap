<?php

/**
 * Singleton for loading and storing zaps
 */
class Zap 
{
	private static Zap $instance;
	
	private mysqli $db;
	
	public function setDb(mysqli $db) 
	{
		$this->db = $db;
	}
	
	protected function __construct() { }
	
	public static function instance(): Zap
	{
		if (!isset(self::$instance)) {
			self::$instance = new Zap();
		}
		
		return self::$instance;
	}
		
	public function storeZapperInCookie(int $zapperId): int
	{
		$retval = null;

		if (null !== $zapperId) {
			$result = $this->db->query(sprintf("SELECT * FROM zappers WHERE id = %d", intval($zapperId)));

			if ($row = mysqli_fetch_assoc($result)) {
				setcookie('zapper', $zapperId, time() + 60 * 60 * 24 * 365, '/');
				$retval = $zapperId;
			}
		}

		return $retval;
	}
		
	public function decodePeriods(string $where): string
	{    
		return str_replace('%PX', '.', $where);
	}
		
	public function store(string $url, int $zapperId, mysqli $db): void
	{
		$this->db->query(sprintf("INSERT INTO zaps (id, url, userid) VALUES(NULL, '%s', %d)", mysqli_real_escape_string($this->db, $url), intval($zapperId)));
	}
		
	public function getUserFormHtml(): string
	{
		$zappers = array();

		$result = $this->db->query("SELECT id, name FROM zappers ORDER BY name ASC");

		while($row = mysqli_fetch_assoc($result))
		{
			$zappers[] = array('id' => $row['id'], 'name' => $row['name']);
		}

		ob_start();
		?>
		<form action="zap.php" method="post">
			<select name="zapper">
				<?php echo array_walk($zappers, function($value, $index) {
					echo sprintf("<option value=\"%s\">%s</option>", $value['id'], $value['name']);
				})?>
			</select>
			<button name="submit" type="submit">Opslaan</button>
		</form>
		<?php

		return ob_get_clean();
	}
		
	public function getListingHtml(): string
	{
		$result = $this->db->query("SELECT z.id, z.url, zn.name, z.timestamp FROM zaps z, zappers zn WHERE z.userid = zn.id ORDER BY timestamp DESC");
		$lis = array();

		while ($row = mysqli_fetch_assoc($result)) {
			$lis[] = sprintf("<li><a href=\"%s\">%s</a><br><small>%s</small> by %s</li>", $row['url'], $row['url'], date('Y-m-d @ h:i', strtotime($row['timestamp'])), $row['name']);
		}

		return sprintf("<ul>%s</ul>", implode('', $lis));
	}

	/**
	 * Issues a header redirect.
	 */
	public function load(): void
	{	
		$result = $this->db->query("SELECT url FROM zaps ORDER BY timestamp DESC LIMIT 1");
		
		if ($row = mysqli_fetch_assoc($result)) { 
			// close the database connection; we are leaving the page
			$this->db->close();
			header(sprintf("Location: %s", $row['url']));
			exit;
		}
	}
}

