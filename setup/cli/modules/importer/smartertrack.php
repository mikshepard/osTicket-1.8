<?php

function SerialNumber($start=1) {
    return function() use (&$start) {
        return $start++;
    };
}

class SmarterTrackDumper extends DatabaseExporter {
    function dump($error_stream) {
        $this->dump_header();

        # Users
        $res = db_query(
            "SELECT `TicketID`, `CustomerEmailAddress`
            FROM ".$this->remote_table('tickets')." ORDER BY DateOpenedUTC DESC LIMIT 8000"
        );
        $users = array();
        $user_emails = array();
        $ticket_users = array();
        $user_id = SerialNumber();
        $email_id = SerialNumber();

        while (list($ticket, $address) = db_fetch_row($res)) {
            if (!($mails = Mail_RFC822::parseAddressList($address)) || PEAR::isError($mails))
                continue;

            $mail = $mails[0];
            if (!$mail->mailbox || $mail->host == 'localhost')
                continue;

            $email = strtolower($mail->mailbox.'@'.$mail->host);
            if (!isset($users[$email])) {
                $m = array('id' => $email_id(),
                    'address' => $email);
                $u = $users[$email] = array('id' => $user_id(),
                    'default_email_id' => $m['id'],
                    'name' => str_replace(array('"','.'),array('',' '),
                        $mail->personal ?: $mail->mailbox));
                $m['user_id'] = $u['id'];
                $user_emails[] = $m;
            }
            else {
                $u = $users[$email];
                $u['name'] = max($u['name'],
                    str_replace(array('"','.'),array('',' '),$mail->personal ?: $mail->mailbox));
            }
            $ticket_users[$ticket] = $u['id'];
        }

        $this->transfer_array('user_email', $user_emails,
            array_keys($user_emails[0]));
        $this->transfer_array('user', $users, array_keys($u));

        # Agents
        $this->transfer('staff',
            "SELECT
                UserId as `staff_id`,
                LoweredUserName as `username`,
                COALESCE(LoweredEmail, 'internal@localhost') as `email`,
                DisplayName as `firstname`,
                1 as `timezone_id`,
                '' as `phone`,
                '' as `mobile`,
                '' as `signature`,
                DateCreatedUTC as `created`,
                DateLastLoginUTC as `lastlogin`,
                CURRENT_TIMESTAMP as `updated`
            FROM ".$this->remote_table('users')
        );

        # Departments
        $this->transfer('department',
            "SELECT
                DepartmentID as `dept_id`,
                DisplayName as `dept_name`,
                IFNULL(AutoResponderID, 0) as `tpl_id`,
                CASE WHEN IsInternal OR IsDeleted THEN 0 ELSE 1 END as `ispublic`,
                CASE WHEN IsAutoResponderEnabled THEN 0 ELSE 1 END as `ticket_auto_response`,
                '' as `dept_signature`,
                CURRENT_TIMESTAMP as `created`,
                CURRENT_TIMESTAMP as `updated`
            FROM ".$this->remote_table('departments')
        );

        # Tickets
        $this->transfer('ticket',
            "SELECT TicketID as `ticket_id`,
                TicketNumber as `number`,
                DepartmentID as `dept_id`,
                UserId as `staff_id`,
                DateOpenedUTC as `created`,
                DateClosedUTC as `closed`,
                CASE WHEN DateClosedUTC IS NULL THEN 'open' ELSE 'closed' END as `status`
            FROM ".$this->remote_table('tickets')."
            WHERE NOT IsDeleted AND NOT IsSpam
            ORDER BY DateOpenedUTC DESC",
            function (&$rec) use ($ticket_users) {
                $rec['user_id'] = $ticket_users[$rec['ticket_id']];
            }
        );
        // TODO: Transfer subject and priority from the tickets
        $this->transfer('form_entry',
            "SELECT TicketID as `id`,
                TicketID as `object_id`,
                'T' as `object_type`,
                2 as `form_id`
            FROM ".$this->remote_table('tickets')."
            ORDER BY DateOpenedUTC DESC"
        );

        # Entries for the users
        $user_entries = array();
        foreach ($users as $u) {
            $user_entries[] = array(
                'object_id' => $u['id'],
                'object_type' => 'U',
                'form_id' => 1
            );
        }
        $this->transfer_array('form_entry', $user_entries,
            array_keys($user_entries[0]),
            array('truncate'=>false));

        $this->transfer('form_entry_values',
            "SELECT TicketID as `entry_id`,
                20 as `field_id`,
                Subject as `value`
            FROM ".$this->remote_table('tickets')."
            ORDER BY DateOpenedUTC DESC"
        );
        $this->transfer('form_entry_values',
            "SELECT TicketID as `entry_id`,
                22 as `field_id`,
                A2.EnglishName as `value`,
                A1.TicketPriorityID as `value_id`
            FROM ".$this->remote_table('tickets')." A1
            JOIN ".$this->remote_table('ticketpriorities')." A2
                ON (A1.TicketPriorityID = A2.TicketPriorityID)
            ORDER BY A1.DateOpenedUTC DESC",
            false,
            array('truncate'=>false)
        );

        # Priorities
        $this->transfer('ticket_priority',
            "SELECT
                TicketPriorityID as `priority_id`,
                255 - SortOrder as `priority_urgency`,
                '#FFFFF0' as `priority_color`,
                1 as `ispublic`,
                EnglishName as `priority_desc`,
                LOWER(EnglishName) as `priority`
            FROM ".$this->remote_table('ticketpriorities')
        );

        # Ticket thread
        $this->transfer('ticket_thread',
            "SELECT
                TicketMessageId as `id`,
                TicketID as `ticket_id`,
                DateReceivedUTC as `created`,
                UserID_From as `staff_id`,
                Subject as `title`,
                CASE WHEN BodyHtml <> '' THEN 'html' ELSE 'text' END as `format`,
                CASE WHEN BodyHtml <> '' THEN BodyHtml ELSE BodyText END as `body`,
                CASE WHEN MessageDirection = 0 THEN 'M' ELSE 'R' END as `thread_type`
            FROM ".$this->remote_table('ticketmessages')."
            ORDER BY DateReceivedUTC DESC",
            function (&$rec) {
                // TODO: Convert inline images
                $rec['body'] = ($rec['format'] == 'html')
                    ? Format::safe_html($rec['body']) : Format::htmlchars($rec['body']);
                unset($rec['format']);
            }
        );
        $this->transfer('ticket_thread',
            "SELECT
                TicketCommentId as `id`,
                TicketId as `ticket_id`,
                DateEnteredUTC as `created`,
                UserId as `staff_id`,
                A2.EnglishName as `title`,
                CommentText as `body`,
                'N' as `thread_type`
            FROM ".$this->remote_table('ticketcomments')." A1
            JOIN ".$this->remote_table('commenttypes')." A2
                ON (A1.CommentTypeID = A2.CommentTypeID)
            ORDER BY DateEnteredUTC DESC",
            function (&$rec) {
                $rec['body'] = Format::htmlchars($rec['body']);
            },
            array('truncate'=>false)
        );

        # Files
        $this->transfer('file',
            "SELECT
                FileID as `id`,
                'T' as `ft`,
                'I' as `bk`,
                FileNameOriginal as `name`,
                Length as `size`,
                MD5(CONCAT(CURRENT_TIMESTAMP, FileID, FileNameOriginal, FileNameOnDisk)) as `key`,
                CONCAT('{\"filename\":\"', FileNameOnDisk, '\"}') as `attrs`
            FROM ".$this->remote_table('files')
        );
        $attachments = array();
        $file_id = SerialNumber(db_result(db_query("SELECT MAX(FileID) FROM ".$this->remote_table('files'))) + 1);
        $this->transfer('file',
            "SELECT
                FileNameOriginal as `name`,
                'T' as `ft`,
                'I' as `bk`,
                Length as `size`,
                TicketAttachmentID as `attach_id`,
                MD5(CONCAT(TicketMessageId, TicketAttachmentID, FileNameOriginal, FileNameOnDisk, CURRENT_TIMESTAMP)) as `key`,
                CONCAT('{\"filename\":\"', FileNameOnDisk, '\"}') as `attrs`
            FROM ".$this->remote_table('ticketattachments'),
            function (&$rec) use (&$attachments, $file_id) {
                $rec['id'] = $file_id();
                $attachments[$rec['attach_id']] = $rec['id'];
                unset($rec['attach_id']);
            },
            array('truncate'=>false)
        );

        # Ticket Attachments
        $this->transfer('ticket_attachment',
            "SELECT TicketAttachmentID as `attach_id`,
                TicketID as `ticket_id`,
                TicketMessageId as `ref_id`
            FROM ".$this->remote_table('ticketattachments'),
            function (&$rec) use ($attachments) {
                $rec['file_id'] = $attachments[$rec['attach_id']];
            }
        );

        # Email templates
        $this->transfer('email_template_group',
            "SELECT IF(DisplayName <> '', DisplayName, 'Imported SmarterTrack autoresponders') as `name`,
            AutoResponderID as `tpl_id`,
            1 as `isactive`
            FROM ".$this->remote_table('autoresponders')
        );
        $this->transfer('email_template',
            "SELECT AutoResponderID as `tpl_id`,
                'ticket.autoresp' as `code_name`,
                Subject as `subject`,
                Body as `body`
            FROM ".$this->remote_table('autoresponders'),
            function (&$rec) {
                static $replacements = array(
                    '[#DEPARTMENTNAME#]'        => '%{ticket.dept.name}',
                    '[#CLIENTEMAILCONTENTS#]'   => '%{message}',
                );
                $rec['body'] = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $rec['body']
                );
            }
        );

        # Email settings
        $this->transfer('email',
            "SELECT A3.DepartmentId as `dept_id`,
                A2.FromAddress as `email`,
                A2.FromFriendlyName as `name`,
                A1.ServerName as `mail_host`,
                A1.ServerPort as `mail_port`,
                'POP' as `mail_protocol`,
                CASE WHEN A1.UseSSL THEN 'SSL' ELSE 'NONE' END as `mail_encryption`,
                A1.LoginName as `userid`,
                '' as `userpass`,
                CAST(A1.IsEnabled AS INT) as `mail_active`,
                CASE WHEN LeaveMailOnServer THEN 0 ELSE 1 END as `mail_delete`,
                A2.ServerName as `smtp_host`,
                A2.ServerPort as `smtp_port`,
                CAST(A2.UseSSL AS INT) as `smtp_secure`,
                CASE WHEN A2.ServerName IS NULL THEN 0 ELSE 1 END as `smtp_active`,
                CURRENT_TIMESTAMP as `created`,
                CURRENT_TIMESTAMP as `updated`
            FROM ".$this->remote_table('smtpaccounts')." A2
            LEFT JOIN ".$this->remote_table('popaccounts')." A1
                ON (A1.LoginName = A2.LoginName)
            JOIN ".$this->remote_table('departments')." A3
                ON (A3.SmtpAccountId = A2.SmtpAccountId)"
        );

        # FAQ Categories
        $this->transfer('faq_category',
            "SELECT KbCategoryID as `category_id`,
                CategoryName as `name`,
                CASE WHEN IsPrivate THEN 0 ELSE 1 END as `ispublic`
            FROM ".$this->remote_table('kbcategories'),
            function (&$rec) {
                $rec['name'] = Format::htmlchars($rec['name']);
            }
        );

        # FAQ Articles
        $this->transfer('faq',
            "SELECT KbArticleID as `faq_id`,
                KbCategoryID as `category_id`,
                DateCreatedUTC as `created`,
                DateModifiedUTC as `updated`,
                CASE WHEN IsPrivate OR IsDeleted THEN 0 ELSE 1 END as `ispublished`,
                Subject as `question`,
                Body as `answer`
            FROM ".$this->remote_table('kbarticles'),
            function (&$rec) {
                $rec['answer'] = Format::safe_html($rec['answer']);
            }
        );

        # Canned Responses
        $this->transfer('canned_response',
            "SELECT CannedReplyID as `canned_id`,
                DepartmentID as `dept_id`,
                Subject as `title`,
                Body as `response`,
                DateCreatedUTC as `created`,
                DateModifiedUTC as `updated`,
                CASE WHEN IsDeleted OR IsDraft THEN 0 ELSE 1 END as `isenabled`
            FROM ".$this->remote_table('cannedreplies'),
            function (&$rec) {
                $rec['response'] = Format::safe_html($rec['response']);
            }
        );
    }

    function transfer($destination, $query, $callback=false, $options=array()) {
        $header_out = false;
        $res = db_query($query." LIMIT 8000", true, false);
        $i = 0;
        while ($row = db_fetch_array($res)) {
            if (is_callable($callback))
                $callback($row);
            if (!$header_out) {
                $fields = array_keys($row);
                $this->write_block(
                    array('table', $destination, $fields, $options));
                $header_out = true;

            }
            $this->write_block(array_values($row));
        }
        $this->write_block(array('end-table'));
    }

    function transfer_array($destination, $array, $keys, $options=array()) {
        $this->write_block(
            array('table', $destination, $keys, $options));
        foreach ($array as $row) {
            $this->write_block(array_values($row));
        }
        $this->write_block(array('end-table'));
    }

    function remote_table($name) {
        return $this->options['prefix'].$name;
    }
}

class SmarterTrackExporter extends Exporter {
    var $prologue =
        "Converts and exports a SmarterTrack database in an osTicket export";

    var $arguments = array();

    var $autohelp = true;

    function __construct() {
        $this->options['prefix'] = array('-P', '--prefix', 'default'=>'st_',
            'help'=>'Table prefix for the SmarterTrack database');
        $this->options['db'] = array('-D', '--db',
            'help'=>'Schema of the SmarterTrack database');
        $this->options['host'] = array('-H', '--host', 'default'=>'localhost',
            'help'=>'Hostname of the MySQL database');
        $this->options['port'] = array('-p', '--port',
            'help'=>'Port number of the MySQL database server for SmarterTrack');
        $this->options['user'] = array('-u', '--user',
            'help'=>'Username for the MySQL user for SmarterTrack');
        $this->options['passwd'] = array('-w', '--passwd',
            'help'=>'Password for the MySQL user for SmarterTrack');

        call_user_func_array(array('parent', '__construct'), func_get_args());
    }

    function run($args, $options) {
        $host = $options['host'];
        if ($options['port'])
            $host .= ':'.$options['port'];

        if (!db_connect($host, $options['user'], $options['passwd'],
                array('db'=>$options['db'])))
            $this->fail('Unable to connect to the SmarterTrack database');

        $this->dump('SmarterTrackDumper');
    }
}

return 'SmarterTrackExporter';
?>
