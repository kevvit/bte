<?php
	require_once 'helper.php';
	session_start();

	# Config for db 
	$config = parse_ini_file('config.ini');
	$servername = $config['db_host'];
	$username = $config['db_user'];
	$password = $config['db_password'];
	$database = $config['db_name'];

	$conn = new mysqli($servername, $username, $password, $database);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	} 

	$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
	$emailsPerPage = 20;
	$startIndex = ($currentPage - 1) * $emailsPerPage;

	$sql = "SELECT * FROM emailsort WHERE NOT id = 0";
	# Prefix fields with S_<field> to indicate fields from the service page. The <field> must match the field name in the database (except before/afterdate)
	$searchFields = ['S_sendername', 'S_senderaddr', 'S_title', 'S_beforedate', 'S_afterdate', 'S_type', 'S_status'];

	if ($_POST) {
		if(isset($_POST['clearbtn'])) {
			clearFields($searchFields, "emailservicepage");
		} elseif (isset($_POST['searchbtn'])) {
			# Add queries to filter and retrieve select emails
			$sql = getValues($searchFields, $sql, "post");
			header("Location: emailservicepage.php?page=1");
			
		} elseif (isset($_POST['emailIds'])) {
			# Updates the table based on the selected emails
			$emailIds = json_decode($_POST['emailIds'], true);
			$status = array_shift($emailIds);
			if ($status == "Closed") {
				if (isset($_POST['closedby'])) {
					$closedby = $_POST['closedby'] ?? '';
					if ($closedby == '') {
						echo "<script>alert('Name required');</script>";
						echo '<script>window.location.href = "emailservicepage.php?page=' . $currentPage . '";</script>';
						exit(1);
					}
					$status = $status . " by $closedby";
				} else {
					echo "<script>alert('Name required');</script>";
					echo '<script>window.location.href = "emailservicepage.php?page=' . $currentPage . '";</script>';
					exit(1);
				}
			}
			
			foreach ($emailIds as $emailId) {
				$emailId = substr($emailId, 0, -1);
				$sql = "SELECT * FROM emailsort WHERE id LIKE '$emailId%'";
				$info = array();
				$result = $conn->query($sql);
				if ($result === false) {
					echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
				} elseif ($result->num_rows > 0) {
					while ($row = $result->fetch_assoc()) {
						array_push($info, array($row["emailuid"]));
					}
				} else {
					echo "Email not found in emails database.";
					exit(1);
				}
				$emailuid = $info[0][0];
				if ($status == "Refund" || $status == "Cancel") {
					# Update type (Refund/Cancel)
					$sql = "UPDATE emailsort SET type = '$status' WHERE emailuid LIKE '$emailuid%'";
					$result = $conn->query($sql);
					if (!$result) {
						echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
					} 
					$sql = "UPDATE emails SET type = '$status' WHERE emailuid LIKE '$emailuid%'";
				} else {
					# Update status (Open/Pending/Closed)
					$sql = "UPDATE emailsort SET status = '$status' WHERE emailuid LIKE '$emailuid%'";
				}
				$result = $conn->query($sql);
				if (!$result) {
					echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
				} 
				$sql = "SELECT * FROM emailsort WHERE NOT id = 0";
			}

			header("Location: emailservicepage.php?page=$currentPage");
		}
	} else {
		# Maintain the search field inputs after form submission
		foreach ($searchFields as $fields) {
			$_POST[$fields] = getSessionValue($fields);
		}
		$sql = getValues($searchFields, $sql, "session");
	}

	# Retrieve all table data
	$sql = $sql . " ORDER BY status DESC, date ASC LIMIT " . $emailsPerPage . " OFFSET " . $startIndex;
	$info = array();
	$result = $conn->query($sql);
	if ($result === false) {
		echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
	} elseif ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			array_push($info, array($row["id"], $row["emailuid"], $row["sendername"], $row["senderaddr"], $row["title"], $row["body"], $row["type"], $row['status'], $row["date"], $row['ordernum']));
		}
	}
	
	$totalPages = calculatePages($emailsPerPage, $sql, $conn);
?>

<html>
	<head>
		<title>Emails SERVICE</title>
		<link rel="stylesheet" type="text/css" href="styles.css">
		<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  		<link rel="stylesheet" href="/resources/demos/style.css">
		<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
  		<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
		<script>
			$( function() {
			$( ".datepicker" ).datepicker();
			} );
		</script>
		<script src="helper.js"></script>
	</head>
	<body>
		<br>
		<h1>Orders</h1>


		<!-- SEARCH TABLE -->
		<table border="1">
			<tr>
				<td style="background-color: #e9ebf4;">
					<form name="inputForm" method="POST">
						<b>Sender:</b> <input name="S_sendername" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_sendername']) ? $_POST['S_sendername'] : '' ?>">
						<b>Address:</b> <input name="S_senderaddr" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_senderaddr']) ? $_POST['S_senderaddr'] : '' ?>">
						<b> Title: </b> <input name="S_title" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_title']) ? $_POST['S_title'] : '' ?>">
						<b> After:	</b> <input name="S_afterdate" class="datepicker" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_afterdate']) ? $_POST['S_afterdate'] : '' ?>">
						<b> Before:	</b> <input name="S_beforedate" class="datepicker" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_beforedate']) ? $_POST['S_beforedate'] : '' ?>">
						<b>Type:</b> 
						<select name="S_type" style="height:25pt;width:100pt;">
							<option value=""></option>
							<option <?php if (isset($_SESSION['S_type']) && $_SESSION['S_type']=="Refund") echo "selected";?> value="Refund">Refund</option>
							<option <?php if (isset($_SESSION['S_type']) && $_SESSION['S_type']=="Cancel") echo "selected";?> value="Cancel">Cancel</option>
						</select> 
						<b>Status:</b> 
						<select name="S_status" style="height:25pt;width:100pt;">
							<option value=""></option>
							<option <?php if (isset($_SESSION['S_status']) && $_SESSION['S_status']=="Open") echo "selected";?> emailstatus="Open">Open</option>
							<option <?php if (isset($_SESSION['S_status']) && $_SESSION['S_status']=="Pending") echo "selected";?> emailstatus="Pending">Pending</option>
							<option <?php if (isset($_SESSION['S_status']) && $_SESSION['S_status']=="Closed") echo "selected";?> emailstatus="Closed">Closed</option>
						</select> 
						<input type="submit" name="searchbtn" value="Search" id="searchbtn" />
						<input type="submit" name="clearbtn" value="Clear Filters" id="clearbtn" />
						<br><br>
						<input type="hidden" id="emailIdsInput" name="emailIds" value="">
						<button style = "background-color: #ccffcc" type="submit" onclick="markRefunds()">Mark selected as Refunds</button>
						<button style = "background-color: #ff9999" onclick="markCancels()">Mark selected as Cancels</button>
						<button style = "background-color: #ffffcc" onclick="markPending()">Mark selected as Pending</button><br><br>
						<b>Closed By (NAME):</b> <input name="closedby" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['closedby']) ? $_POST['closedby'] : '' ?>">
						<button style = "background-color: #c3cde6" onclick="markClosed()">Mark selected as Closed</button>
					</form>
				</td>
			</tr>
		</table>

		<!-- DISPLAY TABLE -->
		<table border="1">
			<tr style="background-color: #eee;">
				<th> ID </th>
				<th>Address</th>
				<th>Order #</th>
				<th>Title</th>
				<th>Status</th>
				<th>Date</th>
				<th></th>
			</tr>
	
			<?php
				// Set the background color of the table
				foreach ($info as $row){
					$row[3] = truncate($row[3], 30);
					$row[4] = truncate($row[4], 50);
					if ($row[6] == "Refund") {
						$row[0] = $row[0] . "R";
						$colour = "#ccffcc";
					} elseif ($row[6] == "Cancel") {
						$row[0] = $row[0] . "C";
						$colour = "#ff9999";
					} else {
						$colour = "#c3cde6";
					}
					if ($row[7] == "Pending") {
						$colour = "#ffffcc";
					} elseif ($row[7] != "Open") {
						$colour = '#c3cde6';
					}
			?>
			<tr bgcolor="<?= $colour // Display each row?>">
				<td class="center"><?= $row[0] ?></td>
				<td class="center"><?= $row[3] ?></td>
				<td class="center"><?= $row[9] ?></td>
				<?php
				$encoded_uid = base64_encode($row[1]);
				?>
				<td class="center"><a href="view_email.php?id=<?= $encoded_uid ?>" target="_blank"><?= $row[4] ?></a></td>
				<td class="center" bgcolor="<?= $colour ?>"><?= $row[7] ?></td>
				<td class="center" bgcolor="<?= $colour ?>"><?= $row[8] ?></td>
				<td><input type="checkbox" name="emailCheckbox"></td>
			</tr>

			<?php
			}
			?>
		</table>
		<?php pagination($currentPage, $totalPages, "emailservicepage"); ?>
	</body>
</html>