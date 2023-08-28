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
	render('email-list.php', [ 'info' => $info, 'currentPage' => $currentPage, 'totalPages' => $totalPages]);
?>


