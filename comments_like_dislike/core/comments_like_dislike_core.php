<?php
define("DEBUGING", true);

function debugEcho($text)
{
	if(DEBUGING===true) echo "$text<br />";
}
function debugDie($text)
{
	if(DEBUGING===true) die($text);
}

class comments_like_dislike {
	
	private static $mysqli;
	private static $connected = false;
	
	const LIKEDISLIKE_DB_HOST = '127.0.0.1';
	const LIKEDISLIKE_DB_USERNAME = 'root';
	const LIKEDISLIKE_DB_PASSWORD = '';
	const LIKEDISLIKE_DB = 'like_dislike_db_news';
	
	private static function connectToDB() {
		if(!self::$connected) {
			self::$mysqli = new mysqli(comments_like_dislike::LIKEDISLIKE_DB_HOST, comments_like_dislike::LIKEDISLIKE_DB_USERNAME, comments_like_dislike::LIKEDISLIKE_DB_PASSWORD, comments_like_dislike::LIKEDISLIKE_DB);
			if(self::$mysqli->connect_errno)
				debugDie('Failed to connect to MySQL: (' . self::$mysqli->connect_errno . ') ' . self::$mysqli->connect_error);
			
			self::$mysqli->set_charset('utf8');
			self::$connected = true;
		}
	}
	public static function closeConnection() {
		self::$mysqli->close();
		self::$connected = false;
	}
	
	private static function query($SQL, &$inserted_id = null) {
		if(!self::$connected)
			self::connectToDB();
		
		$out = null;
		if($result = self::$mysqli->query($SQL)) {
			if(gettype($result)!='boolean') { // then, the $result is absolutely of type 'mysqli_result'
				$out = array();
				while ($row = $result->fetch_assoc())
					$out[] = $row;
				$result->close();
			}
			$inserted_id = self::$mysqli->insert_id;
		}
		else
			DebugEcho('*FAILED* (' . self::$mysqli->errno . ') ' . self::$mysqli->error . '<br />');
		
		return $out;
	}
	
	
	const OPINION_LIKE = 0;
	const OPINION_DISLIKE = 1;
	
	const TABLE_NAME = 'comments_like_dislike';
	
	// This method is totally independent of all other methods in the class
	public static function install() {
		
		$mysqli = new mysqli(comments_like_dislike::LIKEDISLIKE_DB_HOST, comments_like_dislike::LIKEDISLIKE_DB_USERNAME, comments_like_dislike::LIKEDISLIKE_DB_PASSWORD);
		if($mysqli->connect_errno)
			debugDie('Failed to connect to MySQL: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
		
		
		if(!$mysqli->query(
			'CREATE DATABASE IF NOT EXISTS '.comments_like_dislike::LIKEDISLIKE_DB.'
			DEFAULT CHARACTER SET = utf8
			DEFAULT COLLATE = utf8_general_ci;'
		))
			DebugEcho('*FAILED CREATING DATABASE* (' . $mysqli->errno . ') ' . $mysqli->error . '<br />');;

		if($mysqli->select_db(comments_like_dislike::LIKEDISLIKE_DB)) {
			if(!$mysqli->query(
				'CREATE TABLE IF NOT EXISTS '.comments_like_dislike::TABLE_NAME.'(
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					comment_id INT NOT NULL,
					ip_v4 INT UNSIGNED,
					ip_v6 BINARY(16),
					opinion TINYINT(1) NOT NULL,
					insert_date DATETIME NOT NULL
				);'
			))
				DebugEcho('*FAILED CREATING TABLE* (' . $mysqli->errno . ') ' . $mysqli->error . '<br />');
		} else DebugEcho('*SELECTING DB FAILED* (' . $mysqli->errno . ') ' . $mysqli->error . '<br />');
		
		$mysqli->close();
	}
	
	
	public static function insert_opinion($comment_id, $ip_address, $opinion) {
		
		self::get_ip_related_vars($ip_address, $ip_field_name, $ip_value);
		self::query(
			'INSERT INTO '.comments_like_dislike::TABLE_NAME." (comment_id, $ip_field_name, opinion, insert_date) VALUES
			($comment_id, $ip_value, $opinion, '".date('Y-m-d H:i:s')."');", $insert_id);
		return $insert_id;
	}
	
	public static function insert_like_opinion($comment_id, $ip_address) {
		return self::insert_opinion($comment_id, $ip_address, comments_like_dislike::OPINION_LIKE);
	}
	public static function insert_dislike_opinion($comment_id, $ip_address) {
		return self::insert_opinion($comment_id, $ip_address, comments_like_dislike::OPINION_DISLIKE);
	}
	
	public static function get_opinion_row($comment_id, $ip_address) {
		self::get_ip_related_vars($ip_address, $ip_field_name, $ip_value);
		$out = self::query('SELECT * FROM '.comments_like_dislike::TABLE_NAME." WHERE comment_id = $comment_id AND $ip_field_name = $ip_value LIMIT 1;");
		if(!empty($out))
			$out = $out[0];
		return $out;
	}
	
	public static function count_opinions($comment_id, $opinion) {
		$out = self::query('SELECT COUNT(*) AS _count FROM '.comments_like_dislike::TABLE_NAME. " WHERE comment_id = $comment_id AND opinion = $opinion;");
		return $out[0]['_count'];
	}
	
	public static function count_like_opinions($comment_id) {
		return self::count_opinions($comment_id, comments_like_dislike::OPINION_LIKE);
	}
	public static function count_dislike_opinions($comment_id) {
		return self::count_opinions($comment_id, comments_like_dislike::OPINION_DISLIKE);
	}
	
	
	private static function ipVersion($ip_address) {
		return strpos($ip_address, ":") === false ? 4 : 6;
	}
	
	private static function get_ip_related_vars($ip_address, &$ip_field_name, &$ip_value) {
		if(self::ipVersion($ip_address) == 4) {
			$ip_field_name = 'ip_v4';
			$ip_value = 'INET_ATON(\''.$ip_address.'\')';
		} else {
			$ip_field_name = 'ip_v6';
			$ip_value = inet_pton($ip_address);
		}
	}
}

comments_like_dislike::install();