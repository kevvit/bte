<?php
    $emailsPerPage = 20; # Number of emails to display per page

    /**
     * Set up connection to the database using the credentials from config.ini
     * 
     * @return object $conn The connection to the database via mysqli
     * 
     */
    function connSetup() {
        $config = parse_ini_file('config.ini');
        $servername = $config['db_host'];
        $username = $config['db_user'];
        $password = $config['db_password'];
        $database = $config['db_name'];

        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 
        return $conn;
    }

    /**
     * Retrieve saved information from the session to maintain search queries from previous searches
     * 
     * @param string $field The search field name
     * @return string $input The saved field from the session, or empty string if no saved info
     * 
     */
    function getSessionValue($field) {
        if (isset($_SESSION[$field])) {
            $input = $_SESSION[$field];
        } else {
            $input = '';
        }
        return $input;
    }

    /**
     * Get the user input from search fields and save to the session variables
     * Reformats the date input for sql
     * 
     * @param string $field The search field name
     * @return string $input The saved field from the session, or empty string if no saved info
     * 
     */
	function getPostValue($field) {
		if (isset($_POST[$field])) {
			$input = $_POST[$field];
			if ($field === "beforedate" || $field === "afterdate") {
				$dateTime = DateTime::createFromFormat("m/d/Y", $input);
				$errors = DateTime::getLastErrors();
				if ($dateTime === false || $errors['error_count'] > 0 || $errors['warning_count'] > 0 || $dateTime->format("m/d/Y") !== $input) {
					// Invalid date format or input
					$input = '';
				} else {
					// Format the date as needed
			        $_SESSION[$field] = $input;
					return $dateTime->format("Y-m-d");
				}
			}
			$_SESSION[$field] = $input;
		} else {
			$input = '';
		}
		return $input;
	}

    /**
     * Updates sql statement based on filters from search fields
     * 
     * @param array[string] $searchFields An array containing the different search fields such as sender name, address, etc
     * @param string $sql The sql statement to display all emails under the given filters
     * @param string $valueType "post" or "session" depending on the source for where to retrieve field data
     * @param string $input The content of the fields
     * @return string $sql The modified SQL statement
     * 
     */
    function getValues($searchFields, $sql, $valueType) {
        if ($valueType == "post") {
            foreach ($searchFields as $field) {
                # SQL statement changes unique for dates
                $input = getPostValue($field);
                if (substr($field, 2) == "beforedate") {
			        if ($input != '') $sql = $sql . " AND DATE(date) <= '" . $input . "'";
                } elseif (substr($field, 2) == "afterdate") {
			        if ($input != '') $sql = $sql . " AND DATE(date) >= '" . $input . "'";
                } else {
			        $sql = $sql . " AND " . substr($field, 2) . " LIKE '%" . $input . "%'";
                }
            }
        } elseif ($valueType == "session") {
            foreach ($searchFields as $field) {
                $input = getSessionValue($field);
                if (substr($field, 2) == "beforedate") {
                    # Keeps the m/d/Y format displayed in the field but converts it backend for sql
                    $dateTime = DateTime::createFromFormat("m/d/Y", $input);
                    if ($dateTime !== false) $input = $dateTime->format("Y-m-d");
			        if ($input != '') $sql = $sql . " AND DATE(date) <= '" . $input . "'";
                } elseif (substr($field, 2) == "afterdate") {
                    $dateTime = DateTime::createFromFormat("m/d/Y", $input);
                    if ($dateTime !== false) $input = $dateTime->format("Y-m-d");
			        if ($input != '') $sql = $sql . " AND DATE(date) >= '" . $input . "'";
                } else {
			        $sql = $sql . " AND " . substr($field, 2) . " LIKE '%" . $input . "%'";
                }
            }
        }
        return $sql;
    }


    # Truncates $str so that it has $maxChar max characters followed by '...' and returns it
    function truncate($str, $maxChar) {
        if (strlen($str) > $maxChar) {
            return substr($str, 0, $maxChar) . "...";
        } else {
            return $str;
        }
    }

    /**
     * Adds previous and next arrows for pagination, and page picker. Displays total page count
     * 
     * @param int $currentPage The current page displayed
     * @param int $totalPages The total number of pages given the filter(s)
     * @param int $site The site calling the function
     * @return void
     */
    function pagination($currentPage, $totalPages, $site) {
        echo "<div class=\"pagination\">";
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            echo "<a href=\"$site.php?page={$prevPage}\">◄ Previous</a>";
        }

        $minPage = max(1, $currentPage - 2);
        $maxPage = min($totalPages, $currentPage + 2);

        for ($page = $minPage; $page <= $maxPage; $page++) {
            if ($page == $currentPage) {
                echo "<span class=\"current-page\">$page</span>";
            } else {
                echo "<a href=\"$site.php?page={$page}\">$page</a>";
            }
        }

        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            echo "<a href=\"$site.php?page={$nextPage}\">Next ►</a>";
        }

        echo "</div>";

        // Add option to go to a specific page below the numerical pages
        echo "<div class=\"go-form\">";
        echo "<form action=\"$site.php\" method=\"get\">";
        echo "<input type=\"number\" name=\"page\" min=\"1\" max=\"{$totalPages}\" class=\"go-input\" placeholder=\"Go to page\">";
        echo "<input type=\"submit\" value=\"Go\" class=\"go-btn\">";
        echo "</form>";
        echo "</div>";
        
        echo "<h3 style=\"text-align: center;\">" . $totalPages . " pages in total. </h3>";

    }

    /**
     * Updates the email type (refund or cancel) as well as the order mumber, if found from regex
     * 
     * @param string $type "Refund" or "Cancel" to classify the email
     * @param object $conn Connection to database via mysqli
     * @param string $emailuid The uid of the email to classify
     * @param array[string] $row Array of email information fetched from database
     * @return void
     * 
     */
    function updateEmailType($type, $conn, $emailuid, $row) {
        $sql = "UPDATE emails SET type = '$type' WHERE emailuid LIKE '$emailuid%'";
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
            exit(1);
        }

        $sender = $row[2];
        $senderaddr = $row[3];
        $title = $row[4];
        $body = $row[5];
        $date = $row[6];
        $status = "Open";
        $closedby = '';

        $ordernum = '####';
        $pattern = '/\b\d{3}-\d{7}-\d{7}\b|\b\d{9}-A\b/';
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

    /**
     * Clear all search queries and reset page
     * 
     * @param array[string] $searchFields An array containing the different search fields such as sender name, address, etc
     * @param string $site The site calling the function
     */
    function clearFields($searchFields, $site) {
        foreach ($searchFields as $fields) {
            $_POST[$fields] = '';
            $_SESSION[$fields] = '';
        }
        header("Location: $site.php?page=1");
    }

    /**
     * Calculate the number of total pages based on the filter(s) added to the search
     * 
     * @param int $emailsPerPage The max total number of emails allowed to display on each page
     * @param string $sql The sql statement containing the filter(s), if any
     * @param object $conn Connection to database via mysqli
     * @return int $totalPages The total pages based on the filters
     */
    function calculatePages($sql, $conn) {
        global $emailsPerPage;
        $frompos = strpos($sql, "FROM");
        $orderpos = strpos($sql, "ORDER");
        $substr = substr($sql, $frompos, $orderpos - $frompos);

        $sql = "SELECT count(*) AS total " . $substr;
        $result = $conn->query($sql);
        if ($result === false) {
            echo "Error: " . $sql . "<br>" . $conn->error."<br/>";
            exit(1);
        } elseif ($result->num_rows > 0) {

            $row = $result->fetch_assoc();
            $totalEmails = $row['total'];
        }
        if ($totalEmails > 0) {
            $totalPages = ceil($totalEmails / $emailsPerPage);
        } else {
            $totalPages = 1;
        }
        return $totalPages;
    }

?>
