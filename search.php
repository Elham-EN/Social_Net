<?php
    include("includes/header.php");

    //get the q parameter from URL
    if(isset($_GET['q'])) { //if value is declared and not null
        $query = $_GET['q']; 
    }
    else {
        $query = ""; //empty
    }

    if(isset($_GET['type'])) {
        $type = $_GET['type'];
    }
    else {
        $type = "name"; //default to name
    }
?>

<div class="main_column column" id="main_column">
    <?php
        if($query == "")
            echo "You must enter something in the search box.";
        else {
              
            //If query contains an underscore, assume user is searching for usernames
            //strpos searches for underscore in query, if found then...
            if ($type == "username") 
                $usersReturnedQuery = mysqli_query($con, "SELECT * FROM users WHERE username LIKE '$query%' AND user_closed='no' LIMIT 8");
            else {
                $names = explode(" ", $query);
                 //If there are three words, assume there is middle name 
                 if (count($names) == 3)
                    $usersReturnedQuery = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '$names[0]%' AND last_name 
                                                LIKE '$names[2]%') AND user_closed='no' ");
                 //If query has two word only, search first names and last names
                else if (count($names) == 2)
                    $usersReturnedQuery = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '$names[0]%' AND last_name 
                                                        LIKE '$names[1]%') AND user_closed='no' ");
                //else if there is only one word, search for firstname or last name
                else
                    $usersReturnedQuery = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '$names[0]%' OR last_name LIKE 
                                                        '$names[0]%') AND user_closed='no' ");
            }//end of else statement 
            //check if results were found
            if(mysqli_num_rows($usersReturnedQuery) == 0)
                echo "We can't find anyone with a " . $type . " Like: " . $query;
            else
                echo mysqli_num_rows($usersReturnedQuery) . " results found: <br><br>";

            echo "<br><br><p id='grey'>Trying searching</p>";
            echo "<a href='search.php?q=" . $query . "&type=name'>Name</a>, 
                  <a href='search.php?q=" . $query . "&type=username'>Usernames</a><br><br><hr id='search_hr'>";

            while($row = mysqli_fetch_array($usersReturnedQuery)) { //getting information about each user found
                $user_obj = new User($con, $user['username']);
                $button = "";
                $mutual_friend = "";
                if ($user['username'] != $row['username']) { //userloggedIn not equal the user we searched for
                    //Generate button depending on friendship status
                    if ( $user_obj->isFriend($row['username']) )
                        $button = "<input type='submit' name='" . $row['username'] . "' class='danger' value='Remove Friend'>";
                    else if ( $user_obj->didReceiveRequest($row['username']) )
                        $button = "<input type='submit' name='" . $row['username'] . "' class='warning' value='Respond To Request'>";
                    else if ( $user_obj->didSendRequest($row['username']) )
                        $button = "<input class='default' value='Request Sent' style='background-color: silver; border: none; padding: 7px 1px; border-radius: 5px; color: #fff; text-align:center;'>";
                    else 
                        $button = "<input type='submit' name='" . $row['username'] . "' class='success' value='Add Friend'>";
                    
                    $mutual_friend = $user_obj->getMutualFriends($row['username']) . " friends in common";

                   //Button forms - adding functionailty to friend buttons
				   if(isset($_POST[$row['username']])) {
                        if($user_obj->isFriend($row['username'])) {
                            $user_obj->removeFriend($row['username']);
                            header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); //refresh page
                        }
                        else if($user_obj->didReceiveRequest($row['username'])) {
                            header("Location: requests.php");
                        }
                        else if($user_obj->didSendRequest($row['username'])) {

                        }
                        else {
                            $user_obj->sendRequest($row['username']);
                            header("Location: http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
                        }
				  }

                }//end of if statement

                echo "<div class='search_result'>
                            <div class='searchPageFriendButtons'>
                                <form action='' method='POST'>
                                    " . $button . "
                                    <br>
                                </form>
                            </div>
                            <div class='result_profile_pic'>
                                <a href='" . $row['username'] . "'><img src='" . $row['profile_pic'] . "' style='height: 100px;'></a>
                            </div>
                            <a href='" . $row['username'] . "'> " . $row['first_name'] . " " . $row['last_name'] . "
                            <p id='grey'>" . $row['username'] . "</p>
                            </a>
                            <br>
                            " . $mutual_friend . "<br>
                      </div>
                      <br>
                      <hr id='search_hr'>";

            }//end of while loop
        }//end of else statement 
    ?>
</div>