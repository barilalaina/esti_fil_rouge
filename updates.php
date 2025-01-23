<?php
	session_start();
	if(!isset($_SESSION['login']))
		header('Location: login.php');
	include 'databaseConnection.php';

	$result = $db->query("SELECT date_update, description FROM updates WHERE projectId = " . $_GET['projectId'] . " ORDER BY id DESC");
	if(!$result)
		echo '<p style="color: red">An error has occurred when loading the project updates list.</p>';
	else {
		echo '<ul>';
		while($data = $result->fetch())
			echo '<li>[' . $data['date_update'] . '] ' . $data['description'];
		echo '</ul><a href="projectList.php">Go back to the list here.</a>';
	}
?>