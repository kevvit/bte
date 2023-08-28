<?php
	require_once 'helper.php';
	session_start();

	$conn = connSetup();
	$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
	$startIndex = ($currentPage - 1) * $emailsPerPage;

	$sql = "SELECT * FROM emailsort WHERE NOT id = 0";
	# Prefix fields with S_<field> to indicate fields from the service page. The <field> must match the field name in the database (except before/afterdate)
	$searchFields = ['S_sendername', 'S_senderaddr', 'S_title', 'S_beforedate', 'S_afterdate', 'S_type', 'S_status'];

	if ($_POST) {
		if(isset($_POST['clearbtn'])) {
			clearFields($searchFields, "emailservicepage");
		} elseif (isset($_POST['searchbtn'])) { # Clicked Search button
			# Add queries to filter and retrieve select emails
			$sql = getValues($searchFields, $sql, "post");
			header("Location: emailservicepage.php?page=1");
		} elseif (isset($_POST['emailIds'])) { # Clicked mark selected as refunds/cancels/pending/closed
			# Updates the table based on the selected emails
			$emailIds = json_decode($_POST['emailIds'], true);
			$status = array_shift($emailIds);
			if ($status == "Closed") {
				if (isset($_POST['closedby'])) {
					$closedby = $_POST['closedby'] ?? '';
					$closedby = str_replace("'", "", $closedby); // Do not allow single quotes
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
					$info = $result->fetch_assoc();
				} else {
					echo "Email not found in emails database.";
					exit(1);
				}
				$emailuid = $info['emailuid'];
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
			}
			$sql = "SELECT * FROM emailsort WHERE NOT id = 0";
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
	$info = retrieveAllEmails($conn, $sql);
	
	$totalPages = calculatePages($sql, $conn);
	render('email-service-list.php', ['info' => $info, 'currentPage' => $currentPage, 'totalPages' => $totalPages]);
?>

