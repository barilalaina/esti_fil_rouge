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

	if(empty($projectId))
		$message = '<p style="color:red">Missing GET parameter.</p>';
	else if(!belongsToProject($db, $accountId, $projectId)) // petite sécurité d'accès
		die('You are not allowed to access to this backlog project.');
	else if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
		if($_POST['action'] == 'delete') {
			$projectId = $_POST['projectId'];
			$sprint = $_POST['sprint'];
			$specificId = $_POST['specificId'];
			$result = $db->query("DELETE FROM us WHERE projectId = '$projectId' AND sprint = '$sprint' AND specificId = '$specificId'");
			if($result) {
				newUpdate($db, $projectId, $accountId, $login, "has deleted the user story $specificId of the sprint $sprint.");
				$message = '<p style="color:green">The user story has been deleted successfully.</p>';
			}
			else
				$message = '<p style="color:red">An error has occured when trying to delete this user story.</p>';
		}
		else if(empty($_POST['specificId']) || empty($_POST['description']) || empty($_POST['sprint']))
			$message = '<p style="color:red">"Id", "Description" & "Sprint" fields are required.</p>';
		else {
			$specificId = $_POST['specificId'];
			$description = $_POST['description'];
			$sprint = $_POST['sprint'];
			$cost = !empty($_POST['cost']) ? $_POST['cost'] : 0;
			$priority = !empty($_POST['priority']) ? $_POST['priority'] : 0;
			$done = (isset($_POST['done']) && $_POST['done'] == "done") ? 1 : 0;

			if($_POST['action'] == 'create') {
				if($result = $db->query("SELECT specificId FROM us WHERE projectId = $projectId AND specificId = $specificId")->fetch())
					$message = '<p style="color:red">This US id already exists.</p>';
				else {
					$sql = "INSERT INTO us VALUES (NULL, $specificId, $projectId, '$description', $priority, $cost, $sprint, 0)";
					if(!$db->query($sql))
						$message = '<p style="color:red">This user story id has already been taken by another US.</p>';
					else {
						newUpdate($db, $projectId, $accountId, $login, "has created the user story $specificId of the sprint $sprint.");
						$message = '<p style="color:green">The user story has been created successfully.</p>';
					}
				}
			}
			else if($_POST['action'] == 'modify') {
				$oldId = $_POST['oldId'];
				$idAlreadyExists = false;
				if($specificId != $oldId) {
					$idAlreadyExists = $db->query("SELECT specificId FROM us WHERE projectId = $projectId AND specificId = $specificId")->fetch();
					$sql = "UPDATE us SET specificId = $specificId, description = '$description', sprint = $sprint, cost = $cost, 
						priority = $priority, done = $done WHERE specificId = $oldId AND projectId = $projectId";
				}
				else
					$sql = "UPDATE us SET description = '$description', sprint = $sprint, cost = $cost, 
						priority = $priority, done = $done WHERE specificId = $specificId AND projectId = $projectId";
				if($idAlreadyExists)
					$message = '<p style="color:red">This US id already exists.</p>';
				else if(!$db->query($sql))
					$message = '<p style="color:red">This user story id has already been taken by another US.</p>';
				else {
					newUpdate($db, $projectId, $accountId, $login, "has modified the user story $specificId of the sprint $sprint.");
					$message = '<p style="color:green">The user story has been updated successfully.</p>';
				}
			}
			else
				$message = '<p style="color:red">Invalid action into the POST parameter.</p>';
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
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
		<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
		<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
	</head>
	<body>
		<?php 
			include 'navBar.php';
			if(!empty($projectId)) {
				$result = $db->query("SELECT name FROM project WHERE id = $projectId");
				echo '<h2>Backlog du projet : ' . $result->fetch()['name'] . '</h2>';
				echo '
					<table border=1>
					<tr>
						<td><b>Id</b></td><td><b>US</b></td><td><b>Sprint</b></td><td><b>Cost</b></td><td><b>Priority</b></td>
						<td><b>Done</b></td><td><b>Modify</b></td><td><b>Delete</b></td>
					</tr>
				';
				$sql = "SELECT * FROM us WHERE projectId = $projectId ORDER BY specificId";
				$data = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
				foreach($data as $entry) {
					$entry['oldId'] = $entry['specificId'];
					echo'
						<tr>
							<td>' . $entry['specificId'] . '</td>
							<td>' . $entry['description'] . '</td>
							<td><a href="sprintDetails.php?projectId=' . $projectId . '&sprint=' . $entry['sprint'] . '">' . $entry['sprint'] . '</td>
							<td>' . ($entry['cost'] != 0 ? $entry['cost'] : "") . '</td>
							<td>' . ($entry['priority'] != 0 ? $entry['priority'] : "") . '</td>
							<td>' . ($entry['done'] != 0 ? "Yes" : "No") . '</td>
							<td><img onclick="openModifyDialog(' . str_replace("\"", "'", json_encode($entry)) . ')" style="cursor:pointer"
								src="assets/images/update.png" alt="update"/></td>
							<td><img onclick="openDeleteDialog(' . str_replace("\"", "'", json_encode($entry)) . ')" style="cursor:pointer"
								src="assets/images/delete.png" alt="delete"/></td>
						</tr>
					';
				}
				echo '</table>';
			}
		?>
		<br>
		<button id="createUS" onclick="createDialog.dialog('open')">Add new US</button>
		<button type="button" onclick="location.href='consult&modifDoc.php?projectId=<?php echo $projectId; ?>'">Consult documentation</button>

		<br>
		<div id="message"><?php echo $message ?></div>
		<h2>Burn down chart</h2>

		<?php
			if(count($data) != 0) {
				// récupération de tous les US d'un projet
				foreach($data as $us) {
					if(!isset($sprints[$us['sprint']]))
						$sprints[$us['sprint']] = [];
					array_push($sprints[$us['sprint']], $us);
				}

				// récupération des coûts (effectif et attendu) de chaque sprint et calcul du coût total
				$totalCost = 0;
				$sprintCostsArray = [];
				$sprintDoneCostsArray = [];
				$sprintIds = [0];
				foreach($sprints as $key => $sprint) {
					$sprintCost = 0;
					$sprintDoneCost = 0;
					foreach($sprint as $us) {
						$sprintCost += $us['cost'];
						if($us['done'])
							$sprintDoneCost += $us['cost'];
					}
					array_push($sprintIds, $key);
					array_push($sprintCostsArray, $sprintCost);
					array_push($sprintDoneCostsArray, $sprintDoneCost);
					$totalCost += $sprintCost;
				}

				// calcul des valeurs du graphe à partir des coûts de chaque sprint et du coût total
				$progressiveCost = 0;
				$progressiveDoneCost = 0;
				$nbSprints = count($sprintCostsArray);
				for($i = 0; $i < $nbSprints; ++$i) {
					$progressiveCost += $sprintCostsArray[$i];
					$progressiveDoneCost += $sprintDoneCostsArray[$i];
					$sprintCostsArray[$i] = $totalCost - $progressiveCost;
					$sprintDoneCostsArray[$i] = $totalCost - $progressiveDoneCost;
					
				}

				sort($sprintIds);
				array_unshift($sprintCostsArray, $totalCost);
				array_unshift($sprintDoneCostsArray, $totalCost);
				$chartData['sprintLabels'] = $sprintIds;
				$chartData['expected'] = $sprintCostsArray;
				$chartData['done'] = $sprintDoneCostsArray;
			}
		?>

		<canvas id="chart" width="800" height="400"></canvas>
		<script>
			<?php echo "var chartData = " . (isset($chartData) ? json_encode($chartData) : "null") . ";" ?>
			if(chartData) {
				var chart = new Chart(document.getElementById("chart"), {
					type: 'line',
					data: {
						labels: chartData.sprintLabels,
						datasets: [
							{
								label: "done",
								tension: 0,
								fill: false,
								borderColor: "green",
								data: chartData.done
							},
							{
								label: "expected",
								tension: 0,
								fill: false,
								borderColor: "red",
								data: chartData.expected
							}
						]
					},
					options: {
						responsive: false
					}
				});
			}

			$(function() {
				createDialog = $("#createDialog").dialog({
					autoOpen: false,
					height: 570,
					width: 700,
					modal: true,
					buttons: {
						"Create a new US": function() {
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
						"Modify US": function() {
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

				openModifyDialog = function(usObj) {
					$("#modifyDialog > form > fieldset > input").each(function(index, elt) {
						if(elt.name == 'done')
							elt.checked = usObj['done'] == "1";
						else if(elt.name != 'action')
							elt.value = usObj[elt.name];
					});
					modifyDialog.dialog('open');
				};

				openDeleteDialog = function(params) {
					$("#deleteDialog > form > fieldset > input").each(function(index, elt) {
						if(elt.name != 'action')
							elt.value = params[elt.name];
					});
					deleteDialog.dialog('open');
				};
			});
		</script>
	</body>	
	<div id="createDialog" title="Create new US">
		<p class="validateTips">"Id", "Description" & "Sprint" fields are required.</p>
		<form method="POST">
			<fieldset>
				<input type="hidden" type="text" name="action" value="create">

				<label for="id">Id</label>
				<input type="number" name="specificId" id="specificId" class="text ui-widget-content ui-corner-all" required>

				<label for="description">Description</label>
				<input type="text" name="description" id="description" class="text ui-widget-content ui-corner-all" required>

				<label for="sprint">Sprint</label>
				<input type="number" name="sprint" id="sprint" class="text ui-widget-content ui-corner-all" required>				

				<label for="cost">Cost</label>
				<input type="number" name="cost" id="cost" class="text ui-widget-content ui-corner-all">
				
				<label for="priority">Priority</label>
				<input type="number" name="priority" id="priority" class="text ui-widget-content ui-corner-all">

				<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
			</fieldset>
		</form>
	</div>

	<div id="modifyDialog" title="Modify US">
		<p class="validateTips">"Id", "Description" & "Sprint" fields are required.</p>
		<form method="POST">
			<fieldset>
				<input type="hidden" name="oldId">
				<input type="hidden" type="text" name="action" value="modify">

				<label for="specificId">Id</label>
				<input type="number" name="specificId" id="specificId" class="text ui-widget-content ui-corner-all" required>

				<label for="description">Description</label>
				<input type="text" name="description" id="description" class="text ui-widget-content ui-corner-all" required>

				<label for="Sprint">Sprint</label>
				<input type="number" name="sprint" id="sprint" class="text ui-widget-content ui-corner-all" required>				

				<label for="Cost">Cost</label>
				<input type="number" name="cost" id="cost" class="text ui-widget-content ui-corner-all">
				
				<label for="Priority">Priority</label>
				<input type="number" name="priority" id="priority" class="text ui-widget-content ui-corner-all">

				<label for="done">Done ?</label><br>
				<input type="checkbox" name="done" id="done" value="done">

				<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
			</fieldset>
		</form>
	</div>

	<div id="deleteDialog" title="User story deletion">
		<p class="validateTips">Delete this user story ?</p>
		<form method="POST">
			<fieldset>
				<input type="hidden" type="text" name="action" value="delete">
				<input type="hidden" type="text" name="projectId">
				<input type="hidden" type="text" name="specificId">
				<input type="hidden" type="text" name="sprint">

				<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
			</fieldset>
		</form>
	</div>
</html>
