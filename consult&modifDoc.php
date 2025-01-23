<?php
	session_start();
	if(!isset($_SESSION['login']))
		header('Location: login.php');
	include 'databaseConnection.php';
	include 'utilities.php';
	$login = $_SESSION['login'];
	$accountId = $_SESSION['accountId'];
	$projectId = $_GET['projectId'];
	$message = "";
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>ScrumManager</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="assets/css/style.css">
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	</head>
	<body>
		<?php 
			include 'navBar.php';

			$result = $db->query("SELECT name FROM project WHERE id = $projectId"); 
			$projectName = $result->fetch()['name'];

			$result = $db->query("SELECT description FROM documentation WHERE projectId = $projectId"); 
			$documentation = $result->fetch()['description'];

			if(isset($_POST['modify'])) {
				$newDoc = $_POST['doc'];
				$sql = "UPDATE documentation SET description = '$newDoc' WHERE projectId = $projectId";
				if($db->query($sql)) {
					$documentation = $newDoc;
					newUpdate($db, $projectId, $accountId, $login, "has modified the project documentation.");
					$message = '<p style="color:green">The documentation has been modified successfully.</p>';
				}
				else
					$message = '<p style="color:red">An error has occured when trying to modify this documentation. .</p>';
			}		
		?>

		<h1> Consult and modify documentation of <?php echo $projectName ?> </h1>
		<form method="post" action="consult&modifDoc.php?<?php echo 'projectId=' . $projectId; ?>">
			<p>
			<label for="doc">Please put your text below :</label>
				<textarea rows="20" cols="80" type="text" id="doc" name="doc" value="doc"><?php echo $documentation; ?></textarea>
				<input type="submit" name="modify" value="Modify">
			</p>
		</form>
		<div id="message"><?php echo $message ?></div>
	</body>
</html>