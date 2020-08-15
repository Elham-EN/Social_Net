<?php
    include("../../config/config.php");
    include("../classes/User.php");

    //$query contained what user is searching for in input field
    $query = $_POST['query']; //value passed from demo.js
    $userLoggedIn = $_POST['userLoggedIn']; //value passed from demo.js

    //e.g it will split space between reece kenney. where reece will be first element
    //and kenney will be second element. if send one word like reece the first element
    //will remain as reece and there wont be any element after that.
    $name = explode(" ", $query);

    //finds the position of the first occurrence of a string inside another string.
    //strpos(string,find,start). '== : must be same type'. 
    if(strpos($query, "_") !== false) { //check if there is an underscore there, if true ...
        //LLIKE operator is used in a WHERE clause to search for a specified pattern in a column.
        //'$query%' - Finds any values that start with  in "$query"
        $userReturned = mysqli_query($con, "SELECT * FROM users WHERE username LIKE '$query%' AND user_closed='no' LIMIT 8 ");
    } 
    //this counts number of elements in array, if it equal to 2 (if there is two elements in names array assume it will search)
    //for first and last name.
    else if(count($name) == 2) { 
        //Finds any values that have "name value" in any position 0 and 1
        $userReturned = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '%$name[0]%' AND last_name LIKE '%$name[1]%' 
                                     ) AND user_closed='no' LIMIT 8 "); //maximum 8 result
    }
    else { //if they have only one name or more in names array
        $userReturned = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '%$name[0]%' OR last_name LIKE '%$name[0]%' 
                                      ) AND user_closed='no' LIMIT 8 "); //maximum 8 result
    }

    if($query != "") { //if query is not empty 
        while($row = mysqli_fetch_array($userReturned)) {
            $user = new User($con, $userLoggedIn);
            //if they have not found result them self
            if($row['username'] != $userLoggedIn) { //if username is not userLoggedIn then...
                $mutual_friends = $user->getMutualFriends($row['username']) . " friends in common";
            }
            else {
                $mutual_friends = "";
            }

            if($user->isFriend($row['username'])) { //so if they are friends, then...
                echo "<div class='resultDisplay'>
                        <a href='messages.php?u=" . $row['username'] . "' style='color: #000'>
                            <div class='liveSearchProfilePic'>
                                <img src='". $row['profile_pic'] ."'>
                            </div>
                            <div class='liveSearchText'>
                                ".$row['first_name'] . " " . $row['last_name'] . "
                                <p style='margin: 0;'>". $row['username'] ."</p>
                                <p id='grey'>" .$mutual_friends. "</p>
                            </div>
                        </a>
                      </div>";
            }
        } //end of loop
    }
?>