<?php
class User {
	private $user;
	private $con;

	public function __construct($con, $user){
		$this->con = $con;
		$user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$user'");
		$this->user = mysqli_fetch_array($user_details_query);
	}

	public function getUsername() {
		return $this->user['username']; //access specific username value from database
	}

	public function getNumberOfFriendRequest() {
		$username = $this->user['username']; //contain specified username from the logged in user
		$query = mysqli_query($this->con, "SELECT * FROM friend_requests WHERE user_to='$username'");
		return mysqli_num_rows($query);
	}

	public function getNumPosts() {
		$username = $this->user['username']; //contain specified username from the logged in user
		$query = mysqli_query($this->con, "SELECT num_posts FROM users WHERE username='$username'");
		$row = mysqli_fetch_array($query);//fetches a result row as an associative array, a numeric array, or both
		return $row['num_posts']; //Return number of Posts
	}

	public function getFirstAndLastName() {
		$username = $this->user['username'];
		$query = mysqli_query($this->con, "SELECT first_name, last_name FROM users WHERE username='$username'");
		$row = mysqli_fetch_array($query);
		return $row['first_name'] . " " . $row['last_name'];
	}

	public function isClosed() {
		$username = $this->user['username'];
		$query = mysqli_query($this->con, "SELECT user_closed FROM users WHERE username='$username'");
		$row = mysqli_fetch_array($query);

		if($row['user_closed'] == 'yes')
			return true; //if closed return true
		else 
			return false; //else return false
	}

	//check if they are friends 
	public function isFriend($username_to_check) {
		$usernameComma = "," . $username_to_check . ",";
		//check if friends are in friend_array or if username you are checking is the same as user logged in 
		if( ( strstr($this->user['friend_array'], $usernameComma) || $username_to_check == $this->user['username'] ) ) {
			return true;
		} else { //if it wasn't in the friend_array or not you logged in 
			return false; 
		}
	}

	//get profile image 
	public function getProfilePic() {
		$username = $this->user['username'];
		$query = mysqli_query($this->con, "SELECT profile_pic FROM users WHERE username='$username'");
		$row = mysqli_fetch_array($query);
		return $row['profile_pic'];
	}

	public function getFriendArray() {
		$username = $this->user['username'];
		$query = mysqli_query($this->con, "SELECT friend_array FROM users WHERE username='$username'");
		$row = mysqli_fetch_array($query);
		return $row['friend_array'];
	}

	//Check if user received friend request from friend 
	public function didReceiveRequest($user_from) { 
		$user_to = $this->user['username']; //from loggedIn user
		$check_request_query = mysqli_query($this->con, "SELECT * FROM friend_requests WHERE user_to='$user_to' AND user_from='$user_from'");
		if (mysqli_num_rows($check_request_query) > 0) { //check if there's request sent in the table and return true
			return true;
		} else {
			return false;
		}
	}

	//Check if user sent friend request to friend 
	public function didSendRequest($user_to) {
		$user_from = $this->user['username']; //from loggedIn user
		$check_request_query = mysqli_query($this->con, "SELECT * FROM friend_requests WHERE user_to='$user_to' AND user_from='$user_from'");
		if (mysqli_num_rows($check_request_query) > 0) { //check if there's request sent in the table and return true
			return true;
		} else {
			return false;
		}
	}

	//Remove friend 
	public function removeFriend($user_to_remove) {
		$logged_in_user = $this->user['username'];
		$query = mysqli_query($this->con, "SELECT friend_array FROM users WHERE username='$user_to_remove'");
		$row = mysqli_fetch_array($query);
		$friend_array_username = $row['friend_array'];
		/*replaces some characters with some other characters in a string. str_replace(find,replace,string,count)
		Going to look for $user_to_remove follow by comma. Returns a string or an array with the replaced values*/ 
		$new_friend_array = str_replace($user_to_remove . ",", "", $this->user['friend_array']);
		//Remove friend from friend_array of user LoggedIn
		$remove_friend = mysqli_query($this->con, "UPDATE users SET friend_array='$new_friend_array' WHERE username='$logged_in_user'");

		$new_friend_array = str_replace($this->user['username'] . ",", "", $friend_array_username);
		//Remove friend from friend_array of user you trying to remove
		$remove_friend = mysqli_query($this->con, "UPDATE users SET friend_array='$new_friend_array' WHERE username='$user_to_remove'");
	}

	//Add Friend
	public function sendRequest($user_to) {
		$user_from = $this->user['username'];
		$query = mysqli_query($this->con, "INSERT INTO friend_requests VALUES(NULL, '$user_to', '$user_from')");
	}

	public function getMutualFriends($user_to_check) {
		$mutualFriends = 0;
		$user_array = $this->user['friend_array']; //we have friend_array of user LoggedIn
		//breaks a string into an array explode(separator - Specifies where to break the string,
		//string - The string to split)
		$user_array_explode = explode(",", $user_array);

		//friend_array of user we passed
		$query = mysqli_query($this->con, "SELECT friend_array FROM users WHERE username='$user_to_check'");
		$row = mysqli_fetch_array($query);
		$user_to_check_array = $row['friend_array'];
		$user_to_check_array_explode = explode(",", $user_to_check_array);

		//Looping into two friend arrays
		foreach ($user_array_explode as $i) {
			foreach ($user_to_check_array_explode as $j) {
				if ($i == $j && $i != "") { //if i equal to j and i not empty then...
					$mutualFriends++;
				}
			} //end foreach loo[]
		} //end foreach loop
		return $mutualFriends; //total friends we have in common
	} //end getMutualFriends()
}

?>