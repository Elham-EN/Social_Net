<?php
ob_start(); //Turns on output buffering 
session_start();

$timezone = date_default_timezone_set("Australia/Melbourne");

$con = mysqli_connect("localhost", "root", "12345", "social", "3308");

//$con = mysqli_connect("localhost", "id13291066_root", "eN/*zm_gmK}~*mY5", "id13291066_social", "3306");

//$con = mysqli_connect("localhost", "root", "", "social"); //Connection variable


if(mysqli_connect_errno()) 
{
	echo "Failed to connect: " . mysqli_connect_errno();
}

?>