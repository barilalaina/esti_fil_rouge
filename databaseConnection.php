<?php
	try {
		$db = new PDO('mysql:host=localhost;dbname=scrummanager;charset=utf8', 'root', '');
	}
	catch(PDOException $e) {
	   	die('Erreur de connexion  : ' . $e->getMessage());
	}
?>