<?php
    require_once 'helper.php';
    if (isset($_GET['id'])) {
        $conn = connSetup();
        $emailuid = base64_decode($_GET['id']);

        # Find the email with the uid as decoded from the url
        $sql = "SELECT * FROM emails WHERE emailuid LIKE \"" . $emailuid . "%\"";
        $info = array();
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
        } elseif ($result->num_rows > 0) {
            $info = $result->fetch_assoc();
        } else {
            echo "<h1> EMAIL NOT FOUND. <br>EMAIL UID: </h1>" . $emailuid;
            exit();
        }
    } else {
        echo "<h1> ERROR </h1>";
        echo "<p>No email ID specified. Use emailmonitorpage.php</p>";
    }

    if ($_POST) {
        if (isset($_POST['markrefund'])) {
            updateEmailType("Refund", $conn, $emailuid, $info);
        } elseif (isset($_POST['markcancel'])) {
            updateEmailType("Cancel", $conn, $emailuid, $info);
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
        <link rel="stylesheet" type="text/css" href="styles.css">
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
                if ($info['type'] == "Refund") {
                    $info['title'] = $info['title'] . " (REFUND)";
                } elseif ($info['type'] == "Cancel") {
                    $info['title'] = $info['title'] . " (CANCEL)";
                }
                echo "<div class=\"center\">";
                echo "<h1> " . $info['title'] . "</h1>";
                echo "<h3> " . $info['sendername'] . "</h3>";
                echo "<h3> " . $info['senderaddr'] . "</h3>";
                echo "<h3> " . $info['date'] . "</h3>";
                $existing_note_content = $info['note'];
                    ?> 
                    <form method="post">
                        <textarea name="note_content" rows="5" cols="100"><?php echo $existing_note_content; ?></textarea>
                        <br>
                        <input type="submit" name="saveNote" value="Save Note">
                    </form>
                    <form name="buttonForm" method="POST">
                        <input style = "background-color: #ccffcc; color: #333" type="submit" name="markrefund" value="Mark as Refund" id="markrefund" />
                        <input style = "background-color: #ff9999; color: #333" type="submit" name="markcancel" value="Mark as Cancel" id="markcancel" />
                    </form>
                    <?php
                    echo "<title>" . $info['title'] . "</title>";
                    echo "</div>";
            ?>
            <style>
                table {
                    border: none;
                    border-spacing: 0;
                    width: 100%;
                }

                th, td {
                    padding: 0;
                    text-align: left;
                    vertical-align: top;
                    border: none;
                }
            </style>
            <tr>
                <td class="center"  style="background-color: <?= $colour ?> !important;"><?= "<br><br><br>" . str_replace("<br />", "", nl2br($info['body'])) . "<br><br><br>" ?></td>
            </tr>
        </table>
    </body>
</html>