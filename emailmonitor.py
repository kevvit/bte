import imaplib
import email
import base64
import quopri
import os
import chardet  # pip install chardet
import MySQLdb  # pip install mysqlclient
from dateutil.parser import parse # pip install python-dateutil
import configparser
import datetime
import pytz  # pip install pytz
from email.header import decode_header

def get_last_run_timestamp():  # Get the last date and time that emailmonitor.py was executed
    try:
        # Seek the last line of emailmonitorlog.txt to find the last execution date
        with open('emailmonitorlog.txt', 'rb') as f:
            try:  # catch OSError in case of a one line file 
                f.seek(-2, os.SEEK_END)
                while f.read(1) != b'\n':
                    f.seek(-2, os.SEEK_CUR)
            except OSError:
                f.seek(0)
            timestamp_str = f.readline().decode()
        if timestamp_str == '': return None
        parsed_datetime = parse(timestamp_str)
        formatted_date = parsed_datetime.strftime("%d-%b-%Y")
        return formatted_date
    except FileNotFoundError:
        return None
    
def save_current_timestamp():  # Save the current date and time to the emailmonitorlog.txt after exectuting
    current_timestamp = datetime.datetime.now()
    timestamp_str = current_timestamp.strftime("%m-%d-%Y %H:%M:%S")
    # Append the current timestamp to the log file
    try:
        with open("emailmonitorlog.txt", "a") as log_file:
            log_file.write(timestamp_str + "\n")
    except FileNotFoundError:
        print("emailmonitorlog.txt missing")

# Get the body of differently encoded emails
def get_email_body(email_message):
    # Function to handle decoding with replacement for invalid characters
    def safe_decode(text, encoding='utf-8'):
        try:
            return text.decode(encoding)
        except UnicodeDecodeError:
            return text.decode(encoding, 'replace')

    # Get the entire HTML email body if available
    email_body = ''
    if email_message.is_multipart():
        for part in email_message.walk():
            content_type = part.get_content_type()
            if "text/html" in content_type:
                payload = part.get_payload(decode=True)
                charset = part.get_content_charset() or 'utf-8'  # Default to utf-8 if charset is not provided
                email_body = safe_decode(payload, encoding=charset)
                break
    else:
        try:
            payload = email_message.get_payload(decode=True)
            charset = email_message.get_content_charset() or 'utf-8'  # Default to utf-8 if charset is not provided
            email_body = safe_decode(payload, encoding=charset)
        except AttributeError:
            pass  # In case the content charset is not available
    return email_body

def get_decoded_part(encoded_subject):
    """
    encoded_subject = email_message['subject']
    decoded_subject_bytes, charset = decode_header(encoded_subject)[0]
    if isinstance(decoded_subject_bytes, bytes):
        decoded_subject = decoded_subject_bytes.decode(charset if charset else 'utf-8')
    else:
        decoded_subject = decoded_subject_bytes
    
    return email_message['subject']
    """
    decoded_parts = []
    
    # Iterate over each encoded part
    for decoded_header_bytes, charset in decode_header(encoded_subject):
        if isinstance(decoded_header_bytes, bytes):
            # If the bytes are Quoted-Printable encoded, decode them
            if charset and charset.lower() == 'utf-8':
                try:
                    decoded_header_bytes = quopri.decodestring(decoded_header_bytes)
                except quopri.Error:
                    pass

            # Decode Base64 encoded parts
            if charset and charset.lower() == 'utf-8' and decoded_header_bytes.startswith(b'=?utf-8?B?'):
                try:
                    base64_encoded_part = decoded_header_bytes[len(b'=?utf-8?B?'):-len(b'?=')]
                    decoded_header_bytes = base64.b64decode(base64_encoded_part)
                except base64.binascii.Error:
                    pass

        # Convert decoded bytes to string if needed
        if isinstance(decoded_header_bytes, bytes):
            decoded_part = decoded_header_bytes.decode(charset if charset else 'utf-8')
        else:
            decoded_part = decoded_header_bytes

        decoded_parts.append(decoded_part)

    # Concatenate multiple parts
    decoded_header = ''.join(decoded_parts)
    
    return decoded_header

config = configparser.ConfigParser()
config.read("config.ini")

email_address = config.get("credentials", "username")
password = config.get("credentials", "password")
imap_server = config.get("credentials", "host")
mailbox = "INBOX"
# Connect to inbox

db_config = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": config.get("database", "database")
}

try:
    conn = MySQLdb.connect(**db_config)
except MySQLdb.OperationalError:
    print("Can't connect to the database server")
    exit(1)

cursor = conn.cursor()

# Use mail credentials to log in
mail = imaplib.IMAP4_SSL(imap_server)
mail.login(email_address, password)
mail.select(mailbox)

# If this program is running for the first time (emailmonitoring.txt is blank, retrieve all emails.)
from_date = get_last_run_timestamp()
if from_date is None:
    from_date = "01-Jan-1970"


print("Saving emails received after:", from_date)
status, uids = mail.uid("search", None, f'SINCE "{from_date}"')

count = 0
est_timezone = pytz.timezone("America/New_York")
for uid in uids[0].split():

    status, msg_data = mail.uid("fetch", uid, "(RFC822)")
    raw_email = msg_data[0][1]
    email_message = email.message_from_bytes(raw_email)

    # Extract necessary details like email address, subject, date, etc.
    email_id = email_message["Message-ID"]
    email_subject = get_decoded_part(email_message['subject'])
    email_from = email_message["from"]
    email_date = email_message["date"]
    start_bracket = email_from.find("<")
    end_bracket = email_from.find(">")
    sendername = email_from[:start_bracket - 1]
    sendername = get_decoded_part(sendername)
    senderaddr = email_from[start_bracket + 1:end_bracket]


    # Parse date into SQL datetime format and EST timezone
    if email_date is not None:
        email_date = email_date.split(" (")[0]
        parsed_datetime = parse(email_date)
        parsed_datetime_est = parsed_datetime.astimezone(est_timezone)
        email_date = parsed_datetime_est.strftime("%Y-%m-%d %H:%M:%S")
    
    email_body = get_email_body(email_message)

    # Additional processing can be done here if required.

    # Print or save the extracted data to the SQL database.
    # Perform SQL database operations here.
    sql = "INSERT IGNORE INTO emails (emailuid, sendername, senderaddr, title, body, date) VALUES (%s, %s, %s, %s, %s, %s)"
    data = (email_id, sendername, senderaddr, email_subject, email_body, email_date)

    # Execute the INSERT query
    try:
        cursor.execute(sql, data)
        affected_rows = cursor.rowcount
        conn.commit()

        if affected_rows > 0:
            count += 1
            # Print information of email(s) saved
            print("Saved (", count, ") - ", sendername, " | ", senderaddr, " | ", email_date, " | ", email_subject[:20], "... | ", email_body.replace("\n", " ")[:20], "...")
    except Exception as e:
        print(f"An error occured: {e}")
        conn.rollback()
    
    # if count >= 50: break      # Limit to this many emails to save

if count == 0: print ("No new emails found.")

save_current_timestamp()
cursor.close()
conn.close()
mail.logout()
