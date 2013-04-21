<?php
#M9 Classes
class M9
{
    public static function data($name) {
        echo data::getData($name);
    }
}


#Basic database interaction class
class database
{
	#initialize variables. These are later changed in settings.php
    static protected $dbuser = 'scoutinguser';
    static protected $dbpassword = 'hunter3';
    #Do Not Set to localhost, does not work in all environments
    static protected $dbhost = '127.0.0.1';
    static protected $database = 'scouting2013';
    static protected $prefix = '';

	public static function setcredentials($user, $password, $host = '127.0.0.1', $db = 'scouting2013', $pre = '') {
    	// property declaration
    	$result = 'Credential(s) not set: ';
    	$error = 0;
    	if (is_string($user)) {
	    	database::$dbuser = $user;
	    } else {
    		$error = 1;
    		$result .= '$dbuser ';
    	}
    	
    	if (is_string($password)) {
	    	database::$dbpassword = $password;
	    } else {
    		$error = 1;
    		$result .= '$dbpassword ';
    	}
    	
    	if (!$error) {
    		database::$dbhost = $host;
    		database::$database = $db;
    		database::$prefix = $pre;
	    	$result = 'Success!';
	    } else {
	    	$result = 'Error!';
	    }

    	return $result;
    }
    
    public static function sqlconn() {	
		try {
    		$dbh = new PDO('mysql:host='.database::$dbhost.';dbname='.database::$database, database::$dbuser, database::$dbpassword);
    	}
    	catch (PDOException $e) {
			print ("Could not connect to server.n");
			die ("getMessage(): " . $e->getMessage () . "n");
		}
		return $dbh;
	}

    // method declaration
    public static function info() {
    	echo "Database: ".database::$database."<br />";
        echo "Database User: ".database::$dbuser."<br />";
        echo "Database Host: ".database::$dbhost."<br />";
        echo "Database Prefix: ".database::$prefix."<br />";
    }

    public static function test() {
    	$dbh = database::sqlconn();
    	if ($dbh) {
            echo "Connection Successful!";
    		return true;
    		$dbh = NULL;
    	}
    	else {
    		return false;
    	}
    }

    public static function select($query) {
    	$dbh = database::sqlconn();

		$result = $dbh->query ($query);
        
        if ($result) {
            return $result->fetchAll();
        }

		$dbh = NULL;
    }

    public static function insert($query) {
    	$dbh = database::sqlconn();

		// Perform Query
		try {
			$result = $dbh->exec ($query);
		}

		// Check result
		// This shows the actual query sent to MySQL, and the error. Useful for debugging.
  		catch (PDOException $e) {
			print ("Could not connect to server.n");
			die ("getMessage(): " . $e->getMessage () . "n");
		}

		return("Query Successful");

		$dbh = NULL;
    }
}

class user
{
    public static function logout() {
        $user = $_COOKIE['username'];
        database::insert("UPDATE  `m9`.`users` SET  `clientid` =  NULL WHERE  `users`.`username` = '".$user."'");
        setcookie('username', '');
        setcookie('clientid', '');
        header('Location: /M9/');
    }
    
    public static function logoutSpecific($user) {
        database::insert("UPDATE  `m9`.`users` SET  `clientid` =  NULL WHERE  `users`.`id` = '".$user."'");
        setcookie('username', '');
        setcookie('clientid', '');
    }
    
    public static function delete($user) {
        database::insert("DELETE FROM `m9`.`users` WHERE `users`.`id` = ".$user);
    }
    
    public static function create($username, $password, $type, $groups, $gravatar, $googleplus) {
        $sql = "INSERT INTO `m9`.`users` (`username`, `password`, `clientid`, `type`, `groups`, `gravatar`, `googleplus`, `id`) VALUES ('".$username."', '".hash('sha256', $password)."', NULL, '".$type."', NULL, '".md5(strtolower( trim($username)))."', NULL, NULL);";
        if (database::select("SELECT * FROM `m9`.`users` WHERE `users`.`username` = '".$username."'")) {
            #echo "User exists";
        } else {
            #echo "Inserted";
            database::insert($sql);
        }
    }
    
    public static function changeUsername($user, $username) {
        if (database::select("SELECT * FROM `m9`.`users` WHERE `users`.`username` = '".$username."'")) {
            #echo "User exists";
        } else {
            #echo "Inserted";
            database::insert("UPDATE  `m9`.`users` SET  `username` =  '".$username."' WHERE  `users`.`id` = '".$user."'");
        }
    }
    
    public static function changePassword($user, $old, $new, $repeat) {
        $userdata = database::select("SELECT * FROM  `users` WHERE  `id` =  '".$user."'");
        $userdata = $userdata[0];
        if (hash('sha256', $old) == $userdata['password'] && $new == $repeat) {
            database::insert("UPDATE  `m9`.`users` SET  `password` =  '".hash('sha256', $new)."' WHERE  `users`.`id` = '".$user."'");
            database::insert("UPDATE  `m9`.`users` SET  `clientid` =  NULL WHERE  `users`.`id` = '".$user."'");
        }
    }
    
    public static function changeType($user, $type) {
        database::insert("UPDATE  `m9`.`users` SET  `type` =  '".$type."' WHERE  `users`.`id` = '".$user."'");
    }
    
    public static function getUserType() {
        $user = $_COOKIE['username'];
        $userdata = database::select("SELECT * FROM  `users` WHERE  `username` =  '".$user."'");
        $userdata = $userdata[0];
        return $userdata['type'];
    }
    
    public static function userList() {
        $userdata = database::select("SELECT * FROM  `users` WHERE 1");
        echo '<input type="hidden" name="clientid" value="'.$_COOKIE['clientid'].'" />';
        echo '<table border="1">';
        echo '<th>Username</th><th>Password</th><th>User Type</th><th>Groups</th><th>Gravatar</th><th>Google Plus</th><th>Logout User</th>';
        foreach ($userdata as $data) {
            echo '<tr>';
            echo '<td><input type="button" value="'.$data['username'].'" onClick="User.username('.$data['id'].')" /></td>';
            echo '<td><input type="button" value="Change Password" onClick="User.password('.$data['id'].')" /></td>';
            echo '<td><input type="button" value="'.$data['type'].'" onClick="User.type('.$data['id'].')" /></td>';
            echo '<td>Coming Soon</td>';
            echo '<td><img alt="Gravatar" src="http://www.gravatar.com/avatar/'.$data['gravatar'].'" /></td>';
            echo '<td>Coming Soon</td>';
            echo '<td><input type="button" value="Logout User" onClick="User.logout('.$data['id'].')" /></td>';
            echo '<td><input type="button" value="X" onClick="User.delete('.$data['id'].')" /></td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

class data
{
    public static function getData($tag) {
        $data = database::select("SELECT * FROM  `data` WHERE  `tag` =  '".$tag."'");
        $data = $data[0];
        return $data['data'];
    }
    
    public static function dataList() {
        $listdata = database::select("SELECT * FROM  `data` WHERE 1");
        echo '<input type="hidden" name="clientid" value="'.$_COOKIE['clientid'].'" />';
        echo '<table border="1">';
        echo '<th>Tag</th><th>Data</th><th>Data Modified</th>';
        foreach ($listdata as $data) {
            echo '<tr>';
            echo '<td><input type="button" value="'.$data['tag'].'" onClick="Data.tag('.$data['id'].')" /></td>';
            echo '<td id="'.$data['id'].'">'.$data['data'].'</td>';
            echo '<td>'.$data['timestamp'].'</td>';
            echo '<td><input type="button" value="Edit" onClick="Data.edit('.$data['id'].')" /></td>';
            echo '<td><input type="button" value="X" onClick="Data.delete('.$data['id'].')" /></td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    public static function createData($tag, $data) {
        $sql = "INSERT INTO `m9`.`data` (`tag`, `data`, `timestamp`, `id`) VALUES ('".$tag."', '".$data."', CURRENT_TIMESTAMP, NULL);";
        if (database::select("SELECT * FROM `m9`.`data` WHERE `users`.`tag` = '".$tag."'")) {
            #echo "Tag exists";
        } else {
            #echo "Inserted";
            database::insert($sql);
        }
    }
    
    public static function changeTag($id, $new) {
        if (database::select("SELECT * FROM `m9`.`data` WHERE `data`.`tag` = '".$new."'")) {
            #echo "Tag exists";
        } else {
            #echo "Inserted";
            database::insert("UPDATE  `m9`.`data` SET  `tag` =  '".$new."' WHERE  `data`.`id` = '".$id."'");
        }
    }
    
    public static function changeData($id, $new) {
        database::insert("UPDATE  `m9`.`data` SET  `data` =  '".$new."' WHERE  `data`.`id` = '".$id."'");
    }
}

class cards 
{
    public static function loadCards($cardsToLoad) {
        foreach($cardsToLoad as $currentCard) {
            $filename = "Cards/".$currentCard.".php";
            echo '<div class="card" id="'.$currentCard.'">';
            #echo $filename;
            if (file_exists($filename)) {
                include($filename);
    	    }
            echo '</div>';
        }
    }
    
    public static function adminPanel() {
        $type = user::getUserType();
        if ($type == "admin") {
            $cards = Array('Data', 'Users');
        } elseif ($type == "standard") {
            $cards = Array('Data');
        }
        cards::loadCards($cards);
    }
}


class devmode
{
    public static function enable($newPage) {
        if (count($_COOKIE) > 0) {
            $username = filter::username($_COOKIE['username']);
            $userdata = database::select("SELECT * FROM  `users` WHERE  `username` =  '".$username."'");
            $userdata = $userdata[0];
            #If the user has cookies, this is very likely
            if ($username == $userdata['username'] && filter::password($_COOKIE['clientid']) == $userdata['clientid'] && $userdata != '') {
                header('Location: '.$newPage);
            }
        }
    }
}


class filter
{
    public static function username($input) {
        mysql_real_escape_string($input);
        $output = filter_var($input, FILTER_VALIDATE_EMAIL);
        return $output;
    }
    
    public static function password($input) {
        mysql_real_escape_string($input);
        return $input;
    }
}

?>