<?php
	require_once 'helper.php';
	session_start();
	
	$conn = connSetup();
	$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
	$startIndex = ($currentPage - 1) * $emailsPerPage;

	$sql = "SELECT * FROM emails WHERE NOT id = 0";
	# Prefix fields with M_<field> to indicate fields from the monitor page. The <field> must match the field name in the database (except before/afterdate)
	$searchFields = ['M_sendername', 'M_senderaddr', 'M_title', 'M_beforedate', 'M_afterdate'];

	if ($_POST) {
		if(isset($_POST['clearbtn'])) {
			clearFields($searchFields, "emailmonitorpage");
		} elseif (isset($_POST['searchbtn'])) { # Clicked Search button
			# Add queries to filter and retrieve select emails
			$sql = getValues($searchFields, $sql, "post");
			header("Location: emailmonitorpage.php?page=1");
		} elseif (isset($_POST['emailIds'])) { # Clicked mark selected as refunds/cancels
			# Updates the table based on the selected emails
			$emailIds = json_decode($_POST['emailIds'], true);
			$type = array_shift($emailIds);
			foreach ($emailIds as $emailId) {
				$sql = "SELECT * FROM emails WHERE id LIKE '$emailId%'";
				$result = $conn->query($sql);
				if (!$result) {
					echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
					exit(1);
				} elseif ($result->num_rows > 0) {
					$info = $result->fetch_assoc();
					updateEmailType($type, $conn, $info['emailuid'], $info);
				}
			}
			$sql = "SELECT * FROM emails WHERE NOT id = 0";
			header("Location: emailmonitorpage.php?page=$currentPage");
		}
	} else {
		# Maintain the search field inputs after form submission
		foreach ($searchFields as $fields) {
			$_POST[$fields] = getSessionValue($fields);
		}
		$sql = getValues($searchFields, $sql, "session");
	}

	# Retrieve all table data
	$sql = $sql . " ORDER BY date DESC LIMIT " . $emailsPerPage . " OFFSET " . $startIndex;
	$info = retrieveAllEmails($conn, $sql);
	
	$totalPages = calculatePages($sql, $conn);
?>

<html>
	<head>
		<title>Emails</title>
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
		<h1>INBOX</h1>

		<!-- SEARCH TABLE -->
		<table border="1">
			<tr>
				<td style="background-color: #e9ebf4;">
					<form name="inputForm" method="POST">
						<b>Sender:</b> <input name="M_sendername" class="marg" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['M_sendername']) ? $_POST['M_sendername'] : '' ?>">
						<b>Address:</b> <input name="M_senderaddr" class="marg" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['M_senderaddr']) ? $_POST['M_senderaddr'] : '' ?>">
						<b> Title: </b> <input name="M_title" class="marg" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['M_title']) ? $_POST['M_title'] : '' ?>">
						<b> After (MM/DD/YYYY):	</b> <input name="M_afterdate" class="datepicker marg" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['M_afterdate']) ? $_POST['M_afterdate'] : '' ?>">
						<b> Before (MM/DD/YYYY):	</b> <input name="M_beforedate" class="datepicker marg" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['M_beforedate']) ? $_POST['M_beforedate'] : '' ?>">
						
						<input type="submit" name="searchbtn" value="Search" id="searchbtn" />
						<input type="submit" name="clearbtn" value="Clear Filters" id="clearbtn" />
						<br><br>
						<input type="hidden" id="emailIdsInput" name="emailIds" value="">
						<button style = "background-color: #ccffcc" type="submit" onclick="markRefunds()">Mark selected as Refunds</button>
						<button style = "background-color: #ff9999" onclick="markCancels()">Mark selected as Cancels</button>
					</form>
				</td>
			</tr>
		</table>

		<!-- DISPLAY TABLE -->
		<table border="1">
			<tr style="background-color: #eee;">
				<th> ID </th>
				<th>Sender</th>
				<th>Address</th>
				<th>Title</th>
				<th>Date</th>
				<th></th>
			</tr>
			<?php
				// Set the background color of the table
				foreach ($info as $row){
					$row['sendername'] = truncate($row['sendername'], 30);
					$row['senderaddr'] = truncate($row['senderaddr'], 30);
					$row['title'] = truncate($row['title'], 80);
					if ($row['type'] == "Refund") {
						$colour = "#ccffcc";
					} elseif ($row['type'] == "Cancel") {
						$colour = "#ff9999";
					} else {
						$colour = "#c3cde6";
					}
			?>
			<tr bgcolor="<?= $colour ?>">
				<td class="center"><?= $row['id'] ?></td>
				<td class="center"><?= $row['sendername'] ?></td>
				<td class="center"><?= $row['senderaddr'] ?></td>
				<?php
					$encoded_uid = base64_encode($row['emailuid']);
				?>
				<td class="center"><a href="view_email.php?id=<?= $encoded_uid ?>" target="_blank"><?= $row['title'] ?></a></td>
				<td class="center" bgcolor="<?= $colour ?>"><?= $row['date'] ?></td>
				<td><input type="checkbox" name="emailCheckbox"></td>
			</tr>

			<?php
				}
			?>
		</table>
		<?php pagination($currentPage, $totalPages, "emailmonitorpage"); ?>
	</body>
</html>
