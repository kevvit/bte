<html>
    <head>
        <link rel="stylesheet" type="text/css" href="styles.css">
    </head>
    <body>
        <br>
        <table style="border: 1px solid #333;" >
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
                $title = $info['title'];
                $body = $info['body'];
                if ($source == "service") {
                    $emailuid = $info['emailuid'];
                    $sql = "SELECT note FROM emailsort WHERE emailuid = '$emailuid'";
                    $info = array();
                    $result = $conn->query($sql);
                    if ($result === false) {
                        echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
                    } elseif ($result->num_rows > 0) {
                        $info = $result->fetch_assoc();
                    }
                    $existing_note_content = $info['note'];
                    
            ?> 
                    <form method="post">
                        <textarea name="note_content" rows="5" cols="100"><?php echo $existing_note_content; ?></textarea>
                        <br>
                        <br>
                        <input type="submit" name="saveNote" value="Save Note">
                    </form>
            <?php
                }
            ?>
                    <form name="buttonForm" method="POST">
                        <input style = "background-color: #ccffcc; color: #333" type="submit" name="markrefund" value="Mark as Refund" id="markrefund" />
                        <input style = "background-color: #ff9999; color: #333" type="submit" name="markcancel" value="Mark as Cancel" id="markcancel" />
                    </form>
                    <?php
                    echo "<title>" . $title . "</title>";
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

                p, b {
                    text-align: left;
                }

                body {
                    background-color: white !important;
                }
            </style>
            <tr>
                <td class="center"><?= "<br><br><br>" . str_replace("<br />", "", nl2br($body)) . "<br><br><br>" ?></td>
            </tr>
        </table>
    </body>
</html>