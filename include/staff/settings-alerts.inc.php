<h2>Alerts and Notices</h2>
<form action="settings.php?t=alerts" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="alerts" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th>
                <h4>Alerts and Notices sent to staff on ticket "events"</h4>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr><td><div class="alert-config">
            <div class="header">New Ticket Alert:
                <i class="help-tip icon-question-sign" href="#new_ticket"></i>
                <br/>
                <font class="error"><?php echo $errors['ticket_alert_active']; ?></font>
            </div><div class="switch">
                <input type="checkbox" name="ticket_alert_active" value="1" <?php echo $config['ticket_alert_active']?'checked':''; ?> />
            </div><div class="options">
                <input type="checkbox" name="ticket_alert_admin" <?php echo $config['ticket_alert_admin']?'checked':''; ?>> Admin Email <em>(<?php echo $cfg->getAdminEmail(); ?>)</em>
<br/>
                <input type="checkbox" name="ticket_alert_dept_manager" <?php echo $config['ticket_alert_dept_manager']?'checked':''; ?>> Department Manager
<br/>
                <input type="checkbox" name="ticket_alert_dept_members" <?php echo $config['ticket_alert_dept_members']?'checked':''; ?>> Department Members <em>(spammy)</em>
<br/>
                <input type="checkbox" name="ticket_alert_acct_manager" <?php echo $config['ticket_alert_acct_manager']?'checked':''; ?>> Organization Account Manager
                </div>
            </td>
        </tr>
        <tr><td><div class="alert-config">
            <div class="header">New Message Alert:
                <i class="help-tip icon-question-sign" href="#new_message"></i>
                <br/>
                <font class="error"><?php echo $errors['message_alert_active']; ?></font>
            </div>
            <div class="switch">
                <input type="checkbox" name="message_alert_active" value="1" <?php echo $config['message_alert_active']?'checked':''; ?> />
            </div><div class="options">
              <input type="checkbox" name="message_alert_laststaff" <?php echo $config['message_alert_laststaff']?'checked':''; ?>> Last Respondent <i class="help-tip icon-question-sign" href="#last_respondent"></i>
                <br/>
              <input type="checkbox" name="message_alert_assigned" <?php echo $config['message_alert_assigned']?'checked':''; ?>> Assigned Staff <i class="help-tip icon-question-sign" href="#assigned_staff"></i>
                <br/>
              <input type="checkbox" name="message_alert_dept_manager" <?php echo $config['message_alert_dept_manager']?'checked':''; ?>> Department Manager <em>(spammy)</em> <i class="help-tip icon-question-sign" href="#department_manager"></i>
                <br/>
              <input type="checkbox" name="message_alert_acct_manager" <?php echo $config['message_alert_acct_manager']?'checked':''; ?>> Organization Account Manager
            </div>
            </td>
        </tr>
        <tr><td><div class="alert-config">
            <div class="header">New Internal Note Alert:
                <i class="help-tip icon-question-sign" href="#new_activity"></i>
                <br/>
                <font class="error"><?php echo $errors['note_alert_active']; ?></font>
            </div>
            <div class="switch">
                <input type="checkbox" name="note_alert_active"  value="1" <?php echo
                    $config['note_alert_active']?'checked':''; ?> />
            </div><div class="options">
              <input type="checkbox" name="note_alert_laststaff" <?php echo $config['note_alert_laststaff']?'checked':''; ?>> Last Respondent <i class="help-tip icon-question-sign" href="#last_respondent_2"></i>
                <br/>
              <input type="checkbox" name="note_alert_assigned" <?php echo $config['note_alert_assigned']?'checked':''; ?>> Assigned Staff <i class="help-tip icon-question-sign" href="#assigned_staff_2"></i>
                <br/>
              <input type="checkbox" name="note_alert_dept_manager" <?php echo $config['note_alert_dept_manager']?'checked':''; ?>> Department Manager <em>(spammy)</em> <i class="help-tip icon-question-sign" href="#department_manager_2"></i>
            </div>
            </td>
        </tr>
        <tr><td><div class="alert-config">
            <div class="header">Ticket Assignment Alert:
                <i class="help-tip icon-question-sign" href="#assign_alert"></i>
                <br/>
                <font class="error"><?php echo $errors['assigned_alert_active']; ?></font>
            </div><div class="switch">
                  <input name="assigned_alert_active" value="1" checked="checked" type="checkbox">
            </div><div class="options">
              <input type="checkbox" name="assigned_alert_staff" <?php echo $config['assigned_alert_staff']?'checked':''; ?>> Assigned Staff <i class="help-tip icon-question-sign" href="#assigned_staff_3"></i>
<br/>
              <input type="checkbox"name="assigned_alert_team_lead" <?php echo $config['assigned_alert_team_lead']?'checked':''; ?>>Team Lead <em>(On team assignment)</em> <i class="help-tip icon-question-sign" href="#team_lead"></i>
<br/>
              <input type="checkbox"name="assigned_alert_team_members" <?php echo $config['assigned_alert_team_members']?'checked':''; ?>>
                Team Members <em>(spammy)</em> <i class="help-tip icon-question-sign" href="#team_members"></i>
            </div>
            </td>
        </tr>
        <tr><td><div class="alert-config">
            <div class="header">Ticket Transfer Alert:
                <i class="help-tip icon-question-sign" href="#transfer_alert"></i>
                <br/>
                <font class="error"><?php echo $errors['alert_alert_active']; ?></font>
            </div><div class="switch">
                <input type="checkbox" name="transfer_alert_active" value="1" <?php echo $config['transfer_alert_active']?'checked':''; ?> />
            </div><div class="options">
              <input type="checkbox" name="transfer_alert_assigned" <?php echo $config['transfer_alert_assigned']?'checked':''; ?>> Assigned Staff/Team <i class="help-tip icon-question-sign" href="#assigned_staff_team"></i>
<br/>
              <input type="checkbox" name="transfer_alert_dept_manager" <?php echo $config['transfer_alert_dept_manager']?'checked':''; ?>> Department Manager <i class="help-tip icon-question-sign" href="#department_manager_3"></i>
<br/>
              <input type="checkbox" name="transfer_alert_dept_members" <?php echo $config['transfer_alert_dept_members']?'checked':''; ?>>
                Department Members <em>(spammy)</em> <i class="help-tip icon-question-sign" href="#department_members"></i>
            </div>
            </td>
        </tr>
        <tr><td><div class="alert-config">
            <div class="header">Overdue Ticket Alert:
                <i class="help-tip icon-question-sign" href="#stale_alert"></i>
                <br/>
                <font class="error"><?php echo $errors['overdue_alert_active']; ?></font>
            </div><div class="switch">
              <input type="checkbox" name="overdue_alert_active" value="1" <?php echo $config['overdue_alert_active']?'checked':''; ?> />
            </div><div class="options">
              <input type="checkbox" name="overdue_alert_assigned" <?php echo $config['overdue_alert_assigned']?'checked':''; ?>> Assigned Staff/Team <i class="help-tip icon-question-sign" href="#assigned_staff_team_2"></i>
<br/>
              <input type="checkbox" name="overdue_alert_dept_manager" <?php echo $config['overdue_alert_dept_manager']?'checked':''; ?>> Department Manager <i class="help-tip icon-question-sign" href="#department_manager_4"></i>
<br/>
              <input type="checkbox" name="overdue_alert_dept_members" <?php echo $config['overdue_alert_dept_members']?'checked':''; ?>> Department Members <em>(spammy)</em> <i class="help-tip icon-question-sign" href="#department_members_2"></i>
            </div>
            </td>
        </tr>
        <tr><td><div class="alert-config">
            <div class="header">System Alerts:
                <i class="help-tip icon-question-sign" href="#meltdowns"></i>
            </div><div class="switch">
            </div><div class="options">
              <input type="checkbox" name="send_sys_errors" checked="checked" disabled="disabled">System Errors <i class="help-tip icon-question-sign" href="#system_errors"></i>
<br/>
              <input type="checkbox" name="send_sql_errors" <?php echo $config['send_sql_errors']?'checked':''; ?>>SQL errors
<br/>
              <input type="checkbox" name="send_login_errors" <?php echo $config['send_login_errors']?'checked':''; ?>>Excessive Login attempts <i class="help-tip icon-question-sign" href="#excessive_login_attempts"></i>
            </div>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:350px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>

<script type="text/javascript">
$(function() {
    $('.switch input:checkbox').switchButton({
        labels_placement: 'right'
    });
});
</script>
