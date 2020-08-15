<?php  
if(isset($_POST['login_button'])) { //determine if it is other than NULL

	$email = filter_var($_POST['log_email'], FILTER_SANITIZE_EMAIL); //sanitize email/correct email format

	$_SESSION['log_email'] = $email; //Store email into session variable 
	$password = md5($_POST['log_password']); //Get password & enrypt password

	$check_database_query = mysqli_query($con, "SELECT * FROM users WHERE email='$email' AND password='$password'");
	$check_login_query = mysqli_num_rows($check_database_query); //get the num of rows in a rsult

	if($check_login_query == 1) { //if return 1, this mean user login successfully
		//Able to access the resource returned from $check_db_query and store in an array
		$row = mysqli_fetch_array($check_database_query); // fetches a result row as an associative array
		$username = $row['username']; //And to access database 'username' 

		//if account, need to reopen it 
        //find the result where email is equal to email that entered and the result equal to 'yes'
		$user_closed_query = mysqli_query($con, "SELECT * FROM users WHERE email='$email' AND user_closed='yes'");

		 //if it finds one, then update the user_closed back to 'no'. This is when login which open the account.
		if(mysqli_num_rows($user_closed_query) == 1) {
			$reopen_account = mysqli_query($con, "UPDATE users SET user_closed='no' WHERE email='$email'");
		}

		//Now saving value of username as long as session var contains a username and it's not nil when it contains a value
		//This mean user is login. everytime we reload the page inside the website we going to check to see if this is nil 
		//and if it is nill and doesn't contain a value it would mean that the user not loggin. either user been logout or 
		//trying to access a page without loggin in and will just redirect them back to login page
		$_SESSION['username'] = $username;
		//this line only execute if user is login
		header("Location: index.php");  //this will redirect the page back to index.php
		exit();
	}
	else {
		array_push($error_array, "Email or password was incorrect<br>");
	}
}
?>