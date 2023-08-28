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
				<td style="padding: 0"><input type="checkbox" class="modern-checkbox" name="emailCheckbox"></td>
			</tr>

			<?php
				}
			?>
		</table>
		<?php pagination($currentPage, $totalPages, "emailmonitorpage"); ?>
	</body>
</html>