<?php
	session_start();
	if(isset($_SESSION['login']))
		header('Location: index.php');
	$message = "";

	if($_SERVER["REQUEST_METHOD"] == "POST") {
		include 'databaseConnection.php';
		
		if(empty($_POST['login']) || empty( $_POST['password']) || empty($_POST['name']) || empty($_POST['surname']) || empty($_POST['email'])) {
			$message = '<p style="color: red">Missing fields for creating an account.</p>';
		}
		else {
			$login = $_POST['login'];
			$password = md5($_POST['password']);
			$name = $_POST['name'];
			$surname = $_POST['surname'];
			$email = $_POST['email'];

			$sql = "INSERT INTO user (login, password, name, surname, mail) VALUES ('$login', '$password', '$name', '$surname', '$email')";
			if(!$db->query($sql))
				$message = '<p style="color: red">This login has already been taken.</p>';
			else {
				$_SESSION['accountId'] = $db->lastInsertId();
				$_SESSION['login'] = $login;
				header('Location: projectList.php');
			}
		}
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>ScrumManager</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="assets/css/style.css">
	</head>
	<body>
		<?php include 'navBar.php'; ?>
		<h1 align ="center">Inscription</h1>
		<form method="POST">
			<table border="0" align="center" cellspacing="2" cellpadding="2">
				<tr align="center">
					<td><input type="text" name="login" placeholder="login"></td>
				</tr>
				<tr align="center">
					<td><input type="password" name="password" placeholder="password"></td>
				</tr>
				<tr align="center">
					<td><input type="text" name="name" placeholder="name"></td>
				</tr>
				<tr align="center">
					<td><input type="text" name="surname" placeholder="surname"></td>
				</tr>
				<tr align="center">
					<td><input type="email" name="email" placeholder="email"></td>
				</tr>
				<tr align="center">
					<td colspan="2"><input type="submit" id="submit" value="Inscription" class="myButton"></td> 
				</tr>
			</table>
		</form>
		<br>
		<div id="message"><?php echo $message ?></div>
		<footer align="center">
			<p>
				Manaova Inscription
			</p>
		</footer>
	</body>
</html>