<?php 
	session_start();
	if(!isset($_SESSION['login']))
		header('Location: login.php');
	include 'databaseConnection.php';
	include 'utilities.php';
	$login = $_SESSION['login'];
	$accountId = $_SESSION['accountId'];
	$projectId = $_GET['projectId'];
	$sprint = $_GET['sprint'];
	$message = "";

	if(empty($projectId) || empty($sprint))
		$message = '<p style="color:red">Missing GET parameter(s).</p>';
	else if(!belongsToProject($db, $_SESSION['accountId'], $projectId)) // petite sécurité d'accès
		die('You are not allowed to access to this backlog project.');
	else if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
		if($_POST['action'] == "create") {
			if(empty($_POST['id']) || empty($_POST['description']))
				$message = '<p style="color: red">"Id" & "Description" fields are required.</p>';
			else {
				if(!empty($_POST['developerUsername']) && !$developerId = getIdByUsername($db, $_POST['developerUsername']))
					$message = '<p style="color: red">Unknown developer username.</p>';
				else {
					$id = $_POST['id'];
					$description = $_POST['description'];
					$developerId = isset($developerId) ? $developerId : 'NULL';
					$status = !empty($_POST['status']) ? $_POST['status'] : 'NULL';
					$duration = !empty($_POST['duration']) ? $_POST['duration'] : 'NULL';

					$sql = "INSERT INTO task (id, projectId, description, developerId, sprint, status, duration) 
					VALUES ($id, $projectId, '$description', $developerId, $sprint, $status, $duration)";
					if(!$db->query($sql))
						$message = '<p style="color:red">Task id already used.</p>';
					else {
						newUpdate($db, $projectId, $accountId, $login, "has created the task $id of the sprint $sprint.");
						$message = '<p style="color:green">The task has been created successfully.</p>';
					}
				}
			}
		}
		else if($_POST['action'] == "modify") {
			if(empty($_POST['id']) || empty($_POST['description']))
				$message = '<p style="color: red">"Id" & "Description" fields are required.</p>';
			else {
				if(!empty($_POST['developerUsername']) && !$developerId = getIdByUsername($db, $_POST['developerUsername']))
					$message = '<p style="color: red">Unknown developer username.</p>';
				else {
					$id = $_POST['id'];
					$oldId = $_POST['oldId'];
					$description = $_POST['description'];
					$developerId = isset($developerId) ? $developerId : 'NULL';
					$duration = !empty($_POST['duration']) ? $_POST['duration'] : 'NULL';
					$status = !empty($_POST['status']) ? $_POST['status'] : 'NULL';

					if($id != $oldId)
						$sql = "UPDATE task SET id = $id, description = '$description', 
							developerId = $developerId, duration = $duration, status = $status
							WHERE id = $oldId AND sprint = $sprint AND projectId = $projectId";
					else
						$sql = "UPDATE task SET description = '$description', developerId = $developerId, duration = $duration, status = $status
							WHERE id = $id AND sprint = $sprint AND projectId = $projectId";
					if(!$db->query($sql))
						$message = '<p style="color:red">Task id already used.</p>';
					else {
						newUpdate($db, $projectId, $accountId, $login, "has modified the task $id of the sprint $sprint.");
						$message = '<p style="color:green">The task has been updated successfully.</p>';
					}
				}
			}
		}
		else if($_POST['action'] == "delete") {
			if(empty($_POST['id']))
				$message = '<p style="color: red">Missing id for deletion.</p>';
			else {
				$id = $_POST['id'];
				$sql = "DELETE FROM task WHERE id = $id AND sprint = $sprint AND projectId = $projectId";
 				if(!$db->query($sql))
 					$message = '<p style="color:red">An error has occurred when deleting the task.</p>';
 				else {
 					newUpdate($db, $projectId, $accountId, $login, "has deleted the task $id of the sprint $sprint.");
 					$message = '<p style="color:green">The task has been deleted successfully.</p>';
 				}
			}
		}
		else
			$message = '<p style="color: red">Invalid action.</p>';
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>ScrumManager</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="assets/css/style.css">
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
		<script src="https://code.jquery.com/jquery.js"></script>
		<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	</head>
	<body>
		<?php include 'navBar.php'; ?>
		<h1>Sprint <?php echo $sprint; ?> </h1>
		<h2>Kanban</h2>
		<table class="tableau">
			<thead class="header">
				<th class="cell">Task id</th>
				<th class="cell">Description</th>
				<th class="cell">Developer</th>
				<th class="cell">Duration</th>
				<th class="cell">To do</th>
				<th class="cell">On going</th>
				<th class="cell">On testing</th>
				<th class="cell">Done</th>
				<th class="cell">Modify</th>
				<th class="cell">Delete</th>
			</thead>
			<tbody>
				<?php
					$result = $db->query("SELECT id, description, developerId, duration, status FROM task 
						WHERE projectId = $projectId AND sprint = $sprint");
					while($data = $result->fetch()) {
						$data['oldId'] = $data['id'];
						$data['developerUsername'] = !empty($data['developerId']) ? getUsernameById($db, $data['developerId']) : '';
						echo '
							<tr class="row">
								<td class="cell">' . $data['id'] . '</td>
								<td class="cell">' . $data['description'] . '</td>
								<td class="cell">' . $data['developerUsername'] . '</td>
								<td class="cell">' . $data['duration'] . '</td>
								<td class="cell">' . ($data['status'] == 0 ? 'X' : '') . '</td>
								<td class="cell">' . ($data['status'] == 1 ? 'X' : '') . '</td>
								<td class="cell">' . ($data['status'] == 2 ? 'X' : '') . '</td>
								<td class="cell">' . ($data['status'] == 3 ? 'X' : '') . '</td>
								<td><img onclick="openModifyDialog(' . str_replace("\"", "'", json_encode($data)) . ')" style="cursor:pointer"
									src="assets/images/update.png" alt="update"/></td>
								<td><img onclick="openDeleteDialog(' . $data['id'] . ')" style="cursor:pointer"
									src="assets/images/delete.png" alt="delete"/></td>
							</tr>
						';
					}
				?>
			</tbody>
		</table>
		<h2>User stories</h2>
		<ul>
			<?php
				$sql = "SELECT specificId, description FROM us WHERE sprint = $sprint AND projectId = $projectId"; 
				$result = $db->query($sql);
				while($data = $result->fetch())
					echo '<li><b>US#' . $data['specificId'] . '</b> : '. $data['description'] .' </li>';
			?>
		</ul>
		<button onclick="createDialog.dialog('open')">Create new task</button>
		<?php echo '<div id="message">' . $message . '</div>'; ?>
		<script>
			$(function() {
				createDialog = $("#createDialog").dialog({
					autoOpen: false,
					height: 600,
					width: 700,
					modal: true,
					buttons: {
						"Create": function() {
							createDialog.find("form").submit();
							createDialog.dialog("close");
						},
						Cancel: function() {
							createDialog.dialog("close");
						}
					},
					close: function() {

					}
				});

				modifyDialog = $("#modifyDialog").dialog({
					autoOpen: false,
					height: 600,
					width: 700,
					modal: true,
					buttons: {
						"Modify": function() {
							modifyDialog.find("form").submit();
							modifyDialog.dialog("close");
						},
						Cancel: function() {
							modifyDialog.dialog("close");
						}
					},
					close: function() {

					}
				});

				deleteDialog = $("#deleteDialog").dialog({
					autoOpen: false,
					height: 300,
					width: 300,
					modal: true,
					buttons: {
						"Confirm": function() {
							deleteDialog.find("form").submit();
							deleteDialog.dialog("close");
						},
						Cancel: function() {
							deleteDialog.dialog("close");
						}
					},
					close: function() {

					}
				});

				openModifyDialog = function(taskObj) {
					$("#modifyDialog > form > fieldset > input").each(function(index, elt) {
						if(elt.name != 'action')
							elt.value = taskObj[elt.name];
					});
					modifyDialog.dialog('open');
				};

				openDeleteDialog = function(taskId) {
					$('#deleteDialog > form > fieldset > input[name="id"]').val(taskId);
					deleteDialog.dialog('open');
				}
			});
		</script>
	</body>

	<div id="createDialog" title="Create new task">
		<p class="validateTips">"Id" & "Description" fields are required.</p>
		<form method="POST">
			<fieldset>
				<input type="hidden" name="action" value="create">

				<label for="id">Id</label>
				<input type="number" name="id" id="id" class="text ui-widget-content ui-corner-all" required>

				<label for="description">Description</label>
				<input type="text" name="description" id="description" class="text ui-widget-content ui-corner-all" required>

				<label for="developerUsername">Developer username</label>
				<input type="text" name="developerUsername" id="developerUsername" class="text ui-widget-content ui-corner-all">

				<label for="status">Status</label>
				<input type="number" name="status" id="status" class="text ui-widget-content ui-corner-all">

				<label for="duration">Duration</label>
				<input type="number" name="duration" id="duration" class="text ui-widget-content ui-corner-all">

				<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
			</fieldset>
		</form>
	</div>

	<div id="modifyDialog" title="Modify task">
		<p class="validateTips">"Id" & "Description" fields are required.</p>
		<form method="POST">
			<fieldset>
				<input type="hidden" name="oldId">
				<input type="hidden" name="action" value="modify">

				<label for="id">Id</label>
				<input type="number" name="id" id="id" class="text ui-widget-content ui-corner-all" required>

				<label for="description">Description</label>
				<input type="text" name="description" id="description" class="text ui-widget-content ui-corner-all" required>

				<label for="developerUsername">Developer username</label>
				<input type="text" name="developerUsername" id="developerUsername" class="text ui-widget-content ui-corner-all">

				<label for="status">Status</label>
				<input type="number" name="status" id="status" class="text ui-widget-content ui-corner-all">

				<label for="duration">Duration</label>
				<input type="number" name="duration" id="duration" class="text ui-widget-content ui-corner-all">

				<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
			</fieldset>
		</form>
	</div>

	<div id="deleteDialog" title="Task deletion">
  		<p class="validateTips">Delete this task ?</p>
		<form method="POST">
			<fieldset>
				<input type="hidden" name="action" value="delete">
				<input type="hidden" name="id">
				<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
			</fieldset>
		</form>
	</div>
</html>