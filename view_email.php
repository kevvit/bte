<?php


    if (isset($_GET['id'])) {
        $config = parse_ini_file('config.ini');
        $servername = $config['db_host'];
        $username = $config['db_user'];
        $password = $config['db_password'];
        $database = $config['db_name'];

        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 
        
        $emailuid = base64_decode($_GET['id']);

        # Find the email with the uid as decoded from the url
        $sql = "SELECT * FROM emails WHERE emailuid LIKE \"" . $emailuid . "%\"";
        $info = array();
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
        } elseif ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                array_push($info, array($row["id"], $row["emailuid"], $row["sendername"], $row["senderaddr"], $row["title"], $row["body"], $row["date"], $row["type"], $row["note"]));
            }
        } else {
            echo "<h1> EMAIL NOT FOUND: </h1>" . $sql;
        }
    } else {
        echo "<h1> ERROR </h1>";
        echo "<p>No email ID specified. Use emailmonitorpage.php</p>";
    }
    
    function updateEmailType($type, $conn, $emailuid, $row) {
        $sql = "UPDATE emails SET type = '$type' WHERE emailuid LIKE '$emailuid%'";
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
        }

        $emailuid = $row[1];
        $sender = $row[2];
        $senderaddr = $row[3];
        $title = $row[4];
        $body = $row[5];
        $date = $row[6];
        $status = "Open";
        $closedby = '';

        $ordernum = '####';
        $pattern = '/\d{3}-\d{7}-\d{7}/';
        $cleanedHtmlContent = str_replace(['<o:p>', '</o:p>'], '', $body);
        $dom = new DOMDocument();
        $dom->loadHTML(htmlspecialchars($cleanedHtmlContent));
        foreach ($dom->getElementsByTagName('*') as $element) {
            $nodeValue = $element->nodeValue;
            if (preg_match_all($pattern, $nodeValue, $matches)) {
                $ordernum = $matches[0][0];
                break;
            }
        }
        
        $sql = "INSERT INTO emailsort (emailuid, sendername, senderaddr, title, body, date, type, status, closedby, ordernum) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON 
        DUPLICATE KEY UPDATE type = ?, ordernum = ?";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("ssssssssssss", $emailuid, $sender, $senderaddr, $title, $body, $date, $type, $status, $closedby, $ordernum, $type, $ordernum);
        
        $stmt->execute();
    }

    if ($_POST) {
        if (isset($_POST['markrefund'])) {
            updateEmailType("Refund", $conn, $emailuid, $info[0]);
        } elseif (isset($_POST['markcancel'])) {
            updateEmailType("Cancel", $conn, $emailuid, $info[0]);
        } elseif (isset($_POST['saveNote'])) {
            $note_content = $_POST['note_content'];
            $sql = "UPDATE emails SET note = '$note_content' WHERE emailuid LIKE '$emailuid%'";
            $result = $conn->query($sql);
            if ($result === false) {
                echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
            }
        }
        $currentUrl = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        header("Location: ". $currentUrl);
        exit();
    } 
    
    

?>

<html>
<head>
<style>
  body { width: 1560px; margin: 0 auto; }
  table { border-collapse: collapse; width: 100%; }
  table, td, th { border: 1px solid gray; padding: 5px 10px; }
  input[type=submit] { padding: 4px 10px; }
  input[type=text] { font-size:15px; padding: 5px 10px; }
  .center { text-align: center; }
  #newlist li
  {
	text-decoration: none;
	list-style: none;
	display:block;	
	margin:1px;
	text-align:left;
  }
</style>

</head>
<body>

<br>

		<table border="1">
    <tr style="background-color: #eee;">
      
      <th>Details</th>
    </tr>
	
	<?php
	// Set the background color of the table
	
	$colour = "#c3cde6";
	foreach ($info as $row){
        if ($row[7] == "Refund") {
            $row[4] = $row[4] . " (REFUND)";
        } elseif ($row[7] == "Cancel") {
            $row[4] = $row[4] . " (CANCEL)";
        }
		echo "<h1> " . $row[4] . "</h1>";
		echo "<h3> " . $row[2] . "</h3>";
		echo "<h3> " . $row[3] . "</h3>";
		echo "<h3> " . $row[6] . "</h3>";
        $existing_note_content = $row[8];
        ?> 
        <form method="post">
            <textarea name="note_content" rows="5" cols="100"><?php echo $existing_note_content; ?></textarea>
            <br>
            <input type="submit" name="saveNote" value="Save Note">
        </form>
        <form name="buttonForm" method="POST">
            <input style = "background-color: #ccffcc" type="submit" name="markrefund" value="Mark as Refund" id="markrefund" />
            <input style = "background-color: #ff9999" type="submit" name="markcancel" value="Mark as Cancel" id="markcancel" />
        </form>
        <?php
        echo "<title>" . $row[4] . "</title>";
	?>
	<tr>
        <td class="center" bgcolor="<?= $colour ?>"><?= nl2br($row[5]) ?></td>
	</tr>
	<?php
	}
	
	
	?>
</table>