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
		<h1>ORDERS</h1>


		<!-- SEARCH TABLE -->
		<table border="1">
			<tr>
				<td style="background-color: #e9ebf4;">
					<form name="inputForm" method="POST">
						<b>Sender:</b> <input name="S_sendername" class="marg" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_sendername']) ? $_POST['S_sendername'] : '' ?>">
						<b>Address:</b> <input name="S_senderaddr" class="marg" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_senderaddr']) ? $_POST['S_senderaddr'] : '' ?>">
						<b> Title: </b> <input name="S_title" class="marg" type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_title']) ? $_POST['S_title'] : '' ?>">
						<b> After:	</b> <input name="S_afterdate" class="datepicker marg" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_afterdate']) ? $_POST['S_afterdate'] : '' ?>">
						<b> Before:	</b> <input name="S_beforedate" class="datepicker marg" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['S_beforedate']) ? $_POST['S_beforedate'] : '' ?>">
						<b>Type:</b> 
						<select name="S_type" class="marg" style="height:25pt;width:100pt;">
							<option value=""></option>
							<option <?php if (isset($_SESSION['S_type']) && $_SESSION['S_type']=="Refund") echo "selected";?> value="Refund">Refund</option>
							<option <?php if (isset($_SESSION['S_type']) && $_SESSION['S_type']=="Cancel") echo "selected";?> value="Cancel">Cancel</option>
						</select> 
						<b>Status:</b> 
						<select name="S_status" class="marg" style="height:25pt;width:100pt;">
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
						<b>Closed By (NAME):</b> <input name="closedby" class="marg"  type="text" style="height:25pt;width:100pt;" value="<?php echo isset($_POST['closedby']) ? $_POST['closedby'] : '' ?>">
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
					$row['senderaddr'] = truncate($row['senderaddr'], 30);
					$row['title'] = truncate($row['title'], 50);
					if ($row['type'] == "Refund") {
						$row['id'] = $row['id'] . "R";
						$colour = "#ccffcc";
					} elseif ($row['type'] == "Cancel") {
						$row['id'] = $row['id'] . "C";
						$colour = "#ff9999";
					} else {
						$colour = "#c3cde6";
					}
					if ($row['status'] == "Pending") {
						$colour = "#ffffcc";
					} elseif ($row['status'] != "Open") {
						$colour = '#c3cde6';
					}
			?>
			<tr bgcolor="<?= $colour // Display each row?>">
				<td class="center"><?= $row['id'] ?></td>
				<td class="center"><?= $row['senderaddr'] ?></td>
				<td class="center"><?= $row['ordernum'] ?></td>
				<?php
					$encoded_uid = base64_encode($row['emailuid']);
				?>
				<td class="center"><a href="view_email.php?id=service<?= $encoded_uid ?>" target="_blank"><?= $row['title'] ?></a></td>
				<td class="center" bgcolor="<?= $colour ?>"><?= $row['status'] ?></td>
				<td class="center" bgcolor="<?= $colour ?>"><?= $row['date'] ?></td>
				<td style="padding: 0"><input type="checkbox" class="modern-checkbox" name="emailCheckbox"></td>
			</tr>

			<?php
			}
			?>
		</table>
		<?php pagination($currentPage, $totalPages, "emailservicepage"); ?>
	</body>
</html>