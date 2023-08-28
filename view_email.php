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
            $sql = "UPDATE emails SET note = ? WHERE emailuid LIKE '$emailuid%'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $note_content);
            $stmt->execute();
        }
        $currentUrl = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        header("Location: ". $currentUrl);
        exit();
    } 
    render('email-detail.php', ['info' => $info]);
?>