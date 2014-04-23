<?php
    
    $ini_array = parse_ini_file('../../db_stuff/creds.ini', true);
    $user = $ini_array['mysql creds']['username'];
    $pass = $ini_array['mysql creds']['password'];
    $dbname = "pro_signup";
    $number = $_REQUEST['From'];
    $text   = $_REQUEST['Body'];
    date_default_timezone_set('America/Los_Angeles');
    if(isset($number) && isset($text)) {
        try {
            $tablename = "text_data";
            $conn = new PDO("mysql:host=localhost;dbname=$dbname", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            $stmt = $conn->prepare("SELECT * from $tablename WHERE number = :number");
            $stmt->execute(array(':number' => $number));

            // number already exists
            if($row = $stmt->fetch()) {
                if($row["first_last_name"] !== '' && $row["company_type"] !== '' 
                    && $row["business_address"] !== '' && $row["email_password"] !== '') {
                    $update = $conn->prepare("UPDATE $tablename SET followup_texts = CONCAT(followup_texts, :followup_texts) WHERE number = :number");
                    $update->execute(array(':number' => $number,
                                           ':followup_texts' => $text . "\n(" . date('M j, y h:i A') . ")" . "\n"));
                    $response = "(This is an automated message)\nWe are currently in the process of creating your account. If you have any questions, comments, or made a typo, email sales@porch.com or call 888-549-6019 and we'll be happy help you out!";
                }
                
                if($row["first_last_name"] == '' && $text !== $row["company_name"]) {
                    $update = $conn->prepare("UPDATE $tablename SET first_last_name = :first_last_name WHERE number = :number");
                    $update->execute(array(':number' => $number,
                                           ':first_last_name' => $text . "\n(" . date('M j, y h:i A') . ")"));
                    $response = "Step 2/4:\nThanks! Now, about your business. What is your company type (painter, plumber, carpenter, etc)?";

                } else if($row["company_type"] == '' && $text !== $row["first_last_name"]) {
                    $update = $conn->prepare("UPDATE $tablename SET company_type = :company_type WHERE number = :number");
                    $update->execute(array(':number' => $number,
                                           ':company_type' => $text . "\n(" . date('M j, y h:i A') . ")"));
                    $response = "Step 3/4:\nAlmost there. What is your full business address (city, state, zip)?";

                } else if($row["business_address"] == '' && $text !== $row["company_type"]) {
                    $update = $conn->prepare("UPDATE $tablename SET business_address = :business_address WHERE number = :number");
                    $update->execute(array(':number' => $number,
                                           ':business_address' => $text . "\n(" . date('M j, y h:i A') . ")"));
                    $response = "Step 4/4:\nLast step! Please share your email address and a temporary password to set up access to your account.";

                } else if($row["email_password"] == '' && $text !== $row["business_address"]) {
                    $update = $conn->prepare("UPDATE $tablename SET email_password = :email_password WHERE number = :number");
                    $update->execute(array(':number' => $number,
                                           ':email_password' => $text . "\n(" . date('M j, y h:i A') . ")"));
                    $response = "Thanks for joining Porch! We will setup a profile for your company and send you an email once it's complete.";
                }

            } else { // number does not exist
                $update = $conn->prepare("INSERT INTO $tablename (number, company_name) 
                                          VALUES (:new_number, :company_name_text)");
                $update->execute(array(':new_number' => $number,
                                       ':company_name_text' => $text . "\n(" . date('M j, y h:i A') . ")"));
                $response = "Thanks for signing up with Porch! This is a short 4 step process.\n\nStep 1/4:\nLet's get started! What's your first and last name?";
            }
        } catch(PDOException $e) {
            error_messages($e);
        }
    } else {
        error_messages();
    }
    
    function error_messages($e = NULL) {
        $date = new DateTime();
        $response = "This is awkward, it looks like we've run into a problem on our side. Please email support@porch.com or call 888-549-6019 and we'll get you signed up quickly. Thanks!";
        if(is_null($e)) {
            $error_message = "user didn't set From or Body\n" . date('M j, y h:i A') . "\n-----------------------------------\n";
        } else {
            $error_message = "$number, $text\n" . $e->getMessage() . "\n" . date('M j, y h:i A') . "\n-----------------------------------\n";   
        }
        file_put_contents('logfile.txt', $error_message, FILE_APPEND);
    }

    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Message><?php echo $response ?></Message>
</Response>
