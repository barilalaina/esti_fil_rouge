<?php
	function getIdByUsername($db, $user) {
		$sql = "SELECT id FROM user WHERE login = '$user'";
		try {
			$result = $db->query($sql);
		}
		catch(PDOException $e) {
			echo $sql . "<br>" . $e->getMessage();
			return false;	
		}
		return $result->fetch()['id'];
	}

	function getUsernameById($db, $id) {
		$sql = "SELECT login FROM user WHERE id = $id";
		try {
			$result = $db->query($sql);
		}
		catch(PDOException $e) {
			echo $sql . "<br>" . $e->getMessage();
			return false;	
		}
		return $result->fetch()['login'];
	}

	function belongsToProject($db, $userId, $projectId) {
		$sql = "SELECT owner, master FROM project WHERE id = $projectId UNION SELECT userId, projectId FROM contributor WHERE projectId = $projectId";
		$data = $db->query($sql)->fetch();
		return
			($data['owner'] ? $data['owner'] == $userId : false) || 
			($data['master'] ? $data['master'] == $userId : false) || 
			($data['userId'] ? $data['userId'] == $userId : false);
	}

	function isProjectMaster($db, $userId, $projectId) { 
		$sql = "SELECT master FROM project WHERE id = $projectId";
		$data = $db->query($sql)->fetch();
		return $data['master'] == $userId;
	}

	function newUpdate($db, $projectId, $accountId, $login, $description) {
		$description = "<b>$login</b> " . $description;
		$db->query("INSERT INTO updates VALUES (NULL, $projectId, '$description', $accountId, now())");
		$db->query("UPDATE project SET last_update = now() WHERE id = $projectId");
	}
?>