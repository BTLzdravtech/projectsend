<?php
/**
 * Class that handles all the e-mails that the system can send.
 *
 * Currently there are emails defined for the following actions:
 * - A new file has been uploaded by a system user.
 * - A new file has been uploaded by a client.
 * - A new client has been created by a system user.
 * - A new client has self-registered.
 * - A new system user has been created.
 * - A user or client requested a password reset.
 * - Two days left before the expiration of file storage.
 *
 * @package    ProjectSend
 * @subpackage Classes
 *
 * @todo move the sending part into its own class
 * @todo add a method to send a fully custom email, for example, from a credentials testing page
 */

namespace ProjectSend\Classes;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Emails
{
    private $dbh;
    private $header;
    private $footer;
    private $strings_file_by_user;
    private $strings_file_by_client;
    private $strings_new_client;
    private $strings_new_client_self;
    private $strings_account_approved;
    private $strings_account_denied;
    private $strings_new_user;
    private $strings_pass_reset;
    private $strings_client_edited;
    private $strings_limit_retention;
    private $strings_public_links;

    public function __construct()
    {
        global $dbh;
        $this->dbh = $dbh;

        /**
         * Define the messages texts
        */
        $this->header = file_get_contents(EMAIL_TEMPLATES_DIR . DS . EMAIL_TEMPLATE_HEADER);
        $this->footer = file_get_contents(EMAIL_TEMPLATES_DIR . DS . EMAIL_TEMPLATE_FOOTER);
        
        /**
         * Strings for the "New file uploaded" BY A SYSTEM USER e-mail
        */
        $this->strings_file_by_user = array(
            'subject' => (defined('EMAIL_NEW_FILE_BY_USER_SUBJECT_CUSTOMIZE') && EMAIL_NEW_FILE_BY_USER_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_NEW_FILE_BY_USER_SUBJECT')) ? EMAIL_NEW_FILE_BY_USER_SUBJECT : __('New files uploaded for you', 'cftp_admin'),
            'body' => __('The following files are now available for you to download.', 'cftp_admin'),
            'body2' => __("If you prefer not to be notified about new files, please go to My Account and deactivate the notifications checkbox.", 'cftp_admin'),
            'body3' => __('You can access a list of all your files or upload your own', 'cftp_admin'),
            'body4' => __('by logging in here', 'cftp_admin')
        );

        /**
         * Strings for the "New file uploaded" BY A CLIENT e-mail
        */
        $this->strings_file_by_client = array(
            'subject' => (defined('EMAIL_NEW_FILE_BY_CLIENT_SUBJECT_CUSTOMIZE') && EMAIL_NEW_FILE_BY_CLIENT_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_NEW_FILE_BY_CLIENT_SUBJECT')) ? EMAIL_NEW_FILE_BY_CLIENT_SUBJECT : __('New files uploaded by clients', 'cftp_admin'),
            'body' => __('New files has been uploaded by the following clients', 'cftp_admin'),
            'body2' => __("You can manage these files", 'cftp_admin'),
            'body3' => __('by logging in here', 'cftp_admin')
        );


        /**
         * Strings for the "New client created" e-mail
        */
        $this->strings_new_client = array(
            'subject' => (defined('EMAIL_NEW_CLIENT_BY_USER_SUBJECT_CUSTOMIZE') && EMAIL_NEW_CLIENT_BY_USER_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_NEW_CLIENT_BY_USER_SUBJECT')) ? EMAIL_NEW_CLIENT_BY_USER_SUBJECT : __('Welcome to ProjectSend', 'cftp_admin'),
            'body' => __('A new account was created for you. From now on, you can access the files that have been uploaded under your account using the following credentials:', 'cftp_admin'),
            'body2' => __('You can log in following this link', 'cftp_admin'),
            'body3' => __('Please contact the administrator if you need further assistance.', 'cftp_admin'),
            'label_user' => __('Your username', 'cftp_admin'),
            'label_pass' => __('Your password', 'cftp_admin')
        );

        /**
         * Strings for the "New client" e-mail to the admin
         * on self registration.
        */
        $this->strings_new_client_self = array(
            'subject' => (defined('EMAIL_NEW_CLIENT_BY_SELF_SUBJECT_CUSTOMIZE') && EMAIL_NEW_CLIENT_BY_SELF_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_NEW_CLIENT_BY_SELF_SUBJECT')) ? EMAIL_NEW_CLIENT_BY_SELF_SUBJECT : __('A new client has registered.', 'cftp_admin'),
            'body' => __('A new account was created using the self registration form on your site. Registration information:', 'cftp_admin'),
            'label_name' => __('Full name', 'cftp_admin'),
            'label_user' => __('Username', 'cftp_admin'),
            'label_request' => __('Additionally, the client requests access to the following group(s)', 'cftp_admin')
        );
        if (defined('CLIENTS_AUTO_APPROVE') && CLIENTS_AUTO_APPROVE == '0') {
            $this->strings_new_client_self['body2'] = __('Please log in to process the request.', 'cftp_admin');
            $this->strings_new_client_self['body3'] = __('Remember, your new client will not be able to log in until an administrator has approved their account.', 'cftp_admin');
        } else {
            $this->strings_new_client_self['body2'] = __('Auto-approvals of new accounts are currently enabled.', 'cftp_admin');
            $this->strings_new_client_self['body3'] = __('You can log in to manually deactivate it.', 'cftp_admin');
        }

        /**
         * Strings for the "Account approved" e-mail
        */
        $this->strings_account_approved = array(
            'subject' => (defined('EMAIL_ACCOUNT_APPROVE_SUBJECT_CUSTOMIZE') && EMAIL_ACCOUNT_APPROVE_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_ACCOUNT_APPROVE_SUBJECT')) ? EMAIL_ACCOUNT_APPROVE_SUBJECT : __('You account has been approved', 'cftp_admin'),
            'body' => __('Your account has been approved.', 'cftp_admin'),
            'title_memberships' => __('Additionally, your group membership requests have been processed.', 'cftp_admin'),
            'title_approved' => __('Approved requests:', 'cftp_admin'),
            'title_denied' => __('Denied requests:', 'cftp_admin'),
            'body2' => __('You can log in following this link', 'cftp_admin'),
            'body3' => __('Please contact the administrator if you need further assistance.', 'cftp_admin')
        );

        /**
         * Strings for the "Account denied" e-mail
        */
        $this->strings_account_denied = array(
            'subject' => (defined('EMAIL_ACCOUNT_DENY_SUBJECT_CUSTOMIZE') && EMAIL_ACCOUNT_DENY_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_ACCOUNT_DENY_SUBJECT')) ? EMAIL_ACCOUNT_DENY_SUBJECT : __('You account has been denied', 'cftp_admin'),
            'body' => __('Your account request has been denied.', 'cftp_admin'),
            'body2' => __('Please contact the administrator if you need further assistance.', 'cftp_admin')
        );

        /**
         * Strings for the "New system user created" e-mail
        */
        $this->strings_new_user = array(
            'subject' => (defined('EMAIL_NEW_USER_SUBJECT_CUSTOMIZE') && EMAIL_NEW_USER_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_NEW_USER_SUBJECT')) ? EMAIL_NEW_USER_SUBJECT : __('Welcome to ProjectSend', 'cftp_admin'),
            'body' => __('A new account was created for you. From now on, you can access the system administrator using the following credentials:', 'cftp_admin'),
            'body2' => __('Access the system panel here', 'cftp_admin'),
            'body3' => __('Thank you for using ProjectSend.', 'cftp_admin'),
            'label_user' => __('Your username', 'cftp_admin'),
            'label_pass' => __('Your password', 'cftp_admin')
        );

        /**
         * Strings for the "Reset password" e-mail
        */
        $this->strings_pass_reset = array(
            'subject' => (defined('EMAIL_PASS_RESET_SUBJECT_CUSTOMIZE') && EMAIL_PASS_RESET_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_PASS_RESET_SUBJECT')) ? EMAIL_PASS_RESET_SUBJECT : __('Password reset instructions', 'cftp_admin'),
            'body' => __('A request has been received to reset the password for the following account:', 'cftp_admin'),
            'body2' => __('To continue, please visit the following link', 'cftp_admin'),
            'body3' => __('The request is valid only for 24 hours.', 'cftp_admin'),
            'body4' => __('If you did not make this request, simply ignore this email.', 'cftp_admin'),
            'label_user' => __('Username', 'cftp_admin'),
        );

        /**
         * Strings for the "Review client group requests" e-mail to the admin
        */
        $this->strings_client_edited = array(
            'subject' => (defined('EMAIL_CLIENT_EDITED_SUBJECT_CUSTOMIZE') && EMAIL_CLIENT_EDITED_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_CLIENT_EDITED_SUBJECT')) ? EMAIL_CLIENT_EDITED_SUBJECT : __('A client has changed memberships requests.', 'cftp_admin'),
            'body' => __('A client on you site has just changed his groups membership requests and needs your approval.', 'cftp_admin'),
            'label_name' => __('Full name', 'cftp_admin'),
            'label_user' => __('Username', 'cftp_admin'),
            'label_request' => __('The client requests access to the following group(s)', 'cftp_admin'),
            'body2' => __('Please log in to process the request.', 'cftp_admin')
        );

        /**
         * Strings for the "data retention period ends" e-mail
        */
        $this->strings_limit_retention = array(
            'subject' => (defined('EMAIL_LIMIT_RETENTION_SUBJECT_CUSTOMIZE') && EMAIL_LIMIT_RETENTION_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_LIMIT_RETENTION_SUBJECT')) ? EMAIL_LIMIT_RETENTION_SUBJECT : __('Expiration reminder', 'cftp_admin'),
            'body' => __('Two days left before the expiration of your files', 'cftp_admin'),
            'body2' => __('To see the expiration time of your files, please visit the following link', 'cftp_admin'),
            'body3' => __('After the expiration, your uploaded files will be deleted.', 'cftp_admin')
        );

        /**
         * Strings for the "send public links" e-mail
        */
        $this->strings_public_links = array(
            'subject' => (defined('EMAIL_PUBLIC_LINKS_SUBJECT_CUSTOMIZE') && EMAIL_PUBLIC_LINKS_SUBJECT_CUSTOMIZE == 1 && defined('EMAIL_PUBLIC_LINKS_SUBJECT')) ? EMAIL_PUBLIC_LINKS_SUBJECT : __('Links to download files', 'cftp_admin'),
            'body' => __('These files was uploaded for you by', 'cftp_admin')
        );
    }

    /**
     * The body of the e-mails is gotten from the html templates
     * found on the /emails folder.
     * @param $type
     * @return false|string
     */
    private function email_prepare_body($type)
    {
        $filename = "";
        $body_check = "";
        $body_text = "";

        switch ($type) {
            case 'new_client':
                $filename    = EMAIL_TEMPLATE_NEW_CLIENT;
                $body_check    = (!defined('EMAIL_NEW_CLIENT_BY_USER_CUSTOMIZE') || EMAIL_NEW_CLIENT_BY_USER_CUSTOMIZE == '0') ? '0' : EMAIL_NEW_CLIENT_BY_USER_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_NEW_CLIENT_BY_USER_TEXT;
                break;
            case 'new_client_self':
                $filename    = EMAIL_TEMPLATE_NEW_CLIENT_SELF;
                $body_check    = (!defined('EMAIL_NEW_CLIENT_BY_SELF_CUSTOMIZE') || EMAIL_NEW_CLIENT_BY_SELF_CUSTOMIZE == '0') ? '0' : EMAIL_NEW_CLIENT_BY_SELF_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_NEW_CLIENT_BY_SELF_TEXT;
                break;
            case 'account_approve':
                $filename    = EMAIL_TEMPLATE_ACCOUNT_APPROVE;
                $body_check    = (!defined('EMAIL_ACCOUNT_APPROVE_CUSTOMIZE') || EMAIL_ACCOUNT_APPROVE_CUSTOMIZE == '0') ? '0' : EMAIL_ACCOUNT_APPROVE_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_ACCOUNT_APPROVE_TEXT;
                break;
            case 'account_deny':
                $filename    = EMAIL_TEMPLATE_ACCOUNT_DENY;
                $body_check    = (!defined('EMAIL_ACCOUNT_DENY_CUSTOMIZE') || EMAIL_ACCOUNT_DENY_CUSTOMIZE == '0') ? '0' : EMAIL_ACCOUNT_DENY_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_ACCOUNT_DENY_TEXT;
                break;
            case 'new_user':
                $filename    = EMAIL_TEMPLATE_NEW_USER;
                $body_check    = (!defined('EMAIL_NEW_USER_CUSTOMIZE') || EMAIL_NEW_USER_CUSTOMIZE == '0') ? '0' : EMAIL_NEW_USER_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_NEW_USER_TEXT;
                break;
            case 'new_file_by_user':
                $filename    = EMAIL_TEMPLATE_NEW_FILE_BY_USER;
                $body_check    = (!defined('EMAIL_NEW_FILE_BY_USER_CUSTOMIZE') || EMAIL_NEW_FILE_BY_USER_CUSTOMIZE == '0') ? '0' : EMAIL_NEW_FILE_BY_USER_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_NEW_FILE_BY_USER_TEXT;
                break;
            case 'new_files_by_client':
                $filename    = EMAIL_TEMPLATE_NEW_FILE_BY_CLIENT;
                $body_check    = (!defined('EMAIL_NEW_FILE_BY_CLIENT_CUSTOMIZE') || EMAIL_NEW_FILE_BY_CLIENT_CUSTOMIZE == '0') ? '0' : EMAIL_NEW_FILE_BY_CLIENT_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_NEW_FILE_BY_CLIENT_TEXT;
                break;
            case 'password_reset':
                $filename    = EMAIL_TEMPLATE_PASSWORD_RESET;
                $body_check    = (!defined('EMAIL_PASS_RESET_CUSTOMIZE') || EMAIL_PASS_RESET_CUSTOMIZE == '0') ? '0' : EMAIL_PASS_RESET_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_PASS_RESET_TEXT;
                break;
            case 'client_edited':
                $filename    = EMAIL_TEMPLATE_CLIENT_EDITED;
                $body_check    = (!defined('EMAIL_CLIENT_EDITED_CUSTOMIZE') || EMAIL_CLIENT_EDITED_CUSTOMIZE == '0') ? '0' : EMAIL_CLIENT_EDITED_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_CLIENT_EDITED_TEXT;
                break;
            case 'limit_retention':
                $filename    = EMAIL_TEMPLATE_LIMIT_RETENTION;
                $body_check    = (!defined('EMAIL_LIMIT_RETENTION_CUSTOMIZE') || EMAIL_LIMIT_RETENTION_CUSTOMIZE == '0') ? '0' : EMAIL_LIMIT_RETENTION_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_LIMIT_RETENTION_TEXT;
                break;
            case 'public_links':
                $filename    = EMAIL_TEMPLATE_PUBLIC_LINKS;
                $body_check    = (!defined('EMAIL_PUBLIC_LINKS_CUSTOMIZE') || EMAIL_PUBLIC_LINKS_CUSTOMIZE == '0') ? '0' : EMAIL_PUBLIC_LINKS_CUSTOMIZE;
                /** @noinspection PhpUndefinedConstantInspection */
                $body_text    = EMAIL_PUBLIC_LINKS_TEXT;
                break;
        }

        if ($body_check == '0') {
            $get_body = file_get_contents(EMAIL_TEMPLATES_DIR . DS . $filename);
        } else {
            $get_body = $body_text;
        }

        /**
         * Header
         */
        if (!defined('EMAIL_HEADER_FOOTER_CUSTOMIZE') || EMAIL_HEADER_FOOTER_CUSTOMIZE == '0') {
            $make_body = $this->header;
        } else {
            /** @noinspection PhpUndefinedConstantInspection */
            $make_body = EMAIL_HEADER_TEXT;
        }

        /**
         * Body
         */
        $make_body .= $get_body;

        /**
         * Footer
         */
        if (!defined('EMAIL_HEADER_FOOTER_CUSTOMIZE') || EMAIL_HEADER_FOOTER_CUSTOMIZE == '0') {
            $make_body .= $this->footer;
        } else {
            /** @noinspection PhpUndefinedConstantInspection */
            $make_body .= EMAIL_FOOTER_TEXT;
        }


        return $make_body;
    }

    /**
     * Prepare the body for the "New Client" e-mail.
     * The new username and password are also sent.
     * @param $username
     * @param $password
     * @return array
     */
    private function email_new_client($username, $password)
    {
        $email_body = $this->email_prepare_body('new_client');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%BODY2%','%BODY3%','%LBLUSER%','%LBLPASS%','%USERNAME%','%PASSWORD%','%URI%'),
            array(
                $this->strings_new_client['subject'],
                $this->strings_new_client['body'],
                $this->strings_new_client['body2'],
                $this->strings_new_client['body3'],
                $this->strings_new_client['label_user'],
                $this->strings_new_client['label_pass'],
                $username,
                $password,
                BASE_URI
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_new_client['subject'],
            'body' => $email_body
        );
    }

    /**
     * Prepare the body for the "New Client" self registration e-mail.
     * The name of the client and username are also sent.
     * @param $username
     * @param $fullname
     * @param $memberships_requests
     * @return array
     */
    private function email_new_client_self($username, $fullname, $memberships_requests)
    {
        $email_body = $this->email_prepare_body('new_client_self');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%BODY2%','%BODY3%','%LBLNAME%','%LBLUSER%','%FULLNAME%','%USERNAME%','%URI%'),
            array(
                $this->strings_new_client_self['subject'],
                $this->strings_new_client_self['body'],
                $this->strings_new_client_self['body2'],
                $this->strings_new_client_self['body3'],
                $this->strings_new_client_self['label_name'],
                $this->strings_new_client_self['label_user'],
                $fullname,$username,BASE_URI
            ),
            $email_body
        );
        if (!empty($memberships_requests)) {
            $get_groups = get_groups(
                [
                    'group_ids' => $memberships_requests
                ]
            );

            $groups_list = '<ul>';
            foreach ($get_groups as $group) {
                $groups_list .= '<li>' . $group['name'] . '</li>';
            }
            $groups_list .= '</ul>';

            $email_body = str_replace(
                array('%LABEL_REQUESTS%', '%GROUPS_REQUESTS%'),
                array(
                    $this->strings_new_client_self['label_request'],
                    $groups_list
                ),
                $email_body
            );
        }
        return array(
            'subject' => $this->strings_new_client_self['subject'],
            'body' => $email_body
        );
    }

    /**
     * Prepare the body for the "Account approved" e-mail.
     * Also sends the memberships requests approval status.
     * @param $username
     * @param $name
     * @param $memberships_requests
     * @return array
     */
    private function email_account_approve($username, $name, $memberships_requests)
    {
        $requests_title_replace = false;

        $get_groups = get_groups([]);

        if (!empty($memberships_requests['approved'])) {
            $requests_title_replace = true;
            $approved_title = '<p>'.$this->strings_account_approved['title_approved'].'</p>';
            // Make the list
            $approved_list = '<ul>';
            foreach ($memberships_requests['approved'] as $group_id) {
                $approved_list .= '<li style="list-style:disc;">' . $get_groups[$group_id]['name'] . '</li>';
            }
            $approved_list .= '</ul><hr>';
        } else {
            $approved_list =  '';
            $approved_title = '';
        }
        if (!empty($memberships_requests['denied'])) {
            $requests_title_replace = true;
            $denied_title = '<p>'.$this->strings_account_approved['title_denied'].'</p>';
            // Make the list
            $denied_list = '<ul>';
            foreach ($memberships_requests['denied'] as $group_id) {
                $denied_list .= '<li style="list-style:disc;">' . $get_groups[$group_id]['name'] . '</li>';
            }
            $denied_list .= '</ul><hr>';
        } else {
            $denied_list =  '';
            $denied_title = '';
        }

        $requests_title = ($requests_title_replace == true) ? '<p>'.$this->strings_account_approved['title_approved'].'</p>' : '';

        $email_body = $this->email_prepare_body('account_approve');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%', '%REQUESTS_TITLE%', '%APPROVED_TITLE%','%GROUPS_APPROVED%','%DENIED_TITLE%','%GROUPS_DENIED%','%BODY2%','%BODY3%','%URI%'),
            array(
                $this->strings_account_approved['subject'],
                $this->strings_account_approved['body'],
                '<p>'.$this->strings_account_approved['title_memberships'].'</p>',
                $approved_title,
                $approved_list,
                $denied_title,
                $denied_list,
                $this->strings_account_approved['body2'],
                $this->strings_account_approved['body3'],
                BASE_URI
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_account_approved['subject'],
            'body' => $email_body
        );
    }

    /**
     * Prepare the body for the "Account denied" e-mail.
     * @param $username
     * @param $name
     * @return array
     */
    private function email_account_deny($username, $name)
    {
        $email_body = $this->email_prepare_body('account_deny');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%BODY2%'),
            array(
                $this->strings_account_denied['subject'],
                $this->strings_account_denied['body'],
                $this->strings_account_denied['body2'],
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_account_denied['subject'],
            'body' => $email_body
        );
    }

    /**
     * Prepare the body for the "New User" e-mail.
     * The new username and password are also sent.
     * @param $username
     * @param $password
     * @return array
     */
    private function email_new_user($username, $password)
    {
        $email_body = $this->email_prepare_body('new_user');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%BODY2%','%BODY3%','%LBLUSER%','%LBLPASS%','%USERNAME%','%PASSWORD%','%URI%'),
            array(
                $this->strings_new_user['subject'],
                $this->strings_new_user['body'],
                $this->strings_new_user['body2'],
                $this->strings_new_user['body3'],
                $this->strings_new_user['label_user'],
                $this->strings_new_user['label_pass'],
                $username,
                $password,
                BASE_URI
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_new_user['subject'],
            'body' => $email_body
        );
    }

    /**
     * Prepare the body for the "New files for client" e-mail and replace the
     * tags with the strings values set at the top of this file and the
     * link to the log in page.
     * @param $files_list
     * @return array
     */
    private function email_new_files_by_user($files_list)
    {
        $email_body = $this->email_prepare_body('new_file_by_user');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%FILES%','%BODY2%','%BODY3%','%BODY4%','%URI%'),
            array(
                $this->strings_file_by_user['subject'],
                $this->strings_file_by_user['body'],
                $files_list,
                $this->strings_file_by_user['body2'],
                $this->strings_file_by_user['body3'],
                $this->strings_file_by_user['body4'],
                BASE_URI
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_file_by_user['subject'],
            'body' => $email_body
        );
    }

    /**
     * Prepare the body for the "New files by client" e-mail and replace the
     * tags with the strings values set at the top of this file and the
     * link to the log in page.
     * @param $files_list
     * @return array
     */
    private function email_new_files_by_client($files_list)
    {
        $email_body = $this->email_prepare_body('new_files_by_client');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%FILES%','%BODY2%','%BODY3%','%URI%'),
            array(
                $this->strings_file_by_client['subject'],
                $this->strings_file_by_client['body'],
                $files_list,
                $this->strings_file_by_client['body2'],
                $this->strings_file_by_client['body3'],
                BASE_URI
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_file_by_client['subject'],
            'body' => $email_body
        );
    }

    /**
     * Prepare the body for the "Password reset" e-mail and replace the
     * tags with the strings values set at the top of this file and the
     * link to the log in page.
     * @param $username
     * @param $token
     * @return array
     */
    private function email_password_reset($username, $token)
    {
        $email_body = $this->email_prepare_body('password_reset');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%BODY2%','%BODY3%','%BODY4%','%LBLUSER%','%USERNAME%','%URI%'),
            array(
                $this->strings_pass_reset['subject'],
                $this->strings_pass_reset['body'],
                $this->strings_pass_reset['body2'],
                $this->strings_pass_reset['body3'],
                $this->strings_pass_reset['body4'],
                $this->strings_pass_reset['label_user'],
                $username,
                BASE_URI.'reset-password.php?token=' . $token . '&user=' . $username,
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_pass_reset['subject'],
            'body' => $email_body
        );
    }

    /**
     * Prepare the body for the e-mail sent when a client changes group
     *  membeship requests.
     * @param $username
     * @param $fullname
     * @param $memberships_requests
     * @return array
     */
    private function email_client_edited($username, $fullname, $memberships_requests)
    {
        $email_body = $this->email_prepare_body('client_edited');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%BODY2%','%LBLNAME%','%LBLUSER%','%FULLNAME%','%USERNAME%','%URI%'),
            array(
                $this->strings_client_edited['subject'],
                $this->strings_client_edited['body'],
                $this->strings_client_edited['body2'],
                $this->strings_client_edited['label_name'],
                $this->strings_client_edited['label_user'],
                $fullname,$username,BASE_URI
            ),
            $email_body
        );
        if (!empty($memberships_requests)) {
            $get_groups = get_groups(
                [
                'group_ids' => $memberships_requests
                ]
            );

            $groups_list = '<ul>';
            foreach ($get_groups as $group) {
                $groups_list .= '<li>' . $group['name'] . '</li>';
            }
            $groups_list .= '</ul>';

            $email_body = str_replace(
                array('%LABEL_REQUESTS%', '%GROUPS_REQUESTS%'),
                array(
                    $this->strings_client_edited['label_request'],
                    $groups_list
                ),
                $email_body
            );
        }
        return array(
            'subject' => $this->strings_client_edited['subject'],
            'body' => $email_body
        );
    }

    /**
     *  Prepare the body for email sent when the data upload expires
     * @param $files_list
     * @return array
     */
    public function email_limit_retention($files_list)
    {
        $email_body = $this->email_prepare_body('limit_retention');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%FILES%','%BODY2%','%BODY3%','%URI%'),
            array(
                $this->strings_limit_retention['subject'],
                $this->strings_limit_retention['body'],
                $files_list,
                $this->strings_limit_retention['body2'],
                $this->strings_limit_retention['body3'],
                BASE_URI
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_limit_retention['subject'],
            'body' => $email_body
         );
    }

    /**
     *  Prepare the body for email sent when the data upload expires
     * @param $links
     * @param $note
     * @param $uploader
     * @return array
     */
    public function email_public_links($links, $note, $uploader)
    {
        $email_body = $this->email_prepare_body('public_links');
        $email_body = str_replace(
            array('%SUBJECT%','%BODY1%','%UPLOADER%','%LINKS%','%NOTE%'),
            array(
                $this->strings_public_links['subject'],
                $this->strings_public_links['body'],
                $uploader,
                $links,
                $note
            ),
            $email_body
        );
        return array(
            'subject' => $this->strings_public_links['subject'],
            'body' => $email_body
        );
    }

    /**
     * Finally, try to send the e-mail and return a status, where
     * 1 = Message sent OK
     * 2 = Error sending the e-mail
     *
     * Returns custom values instead of a boolean value to allow more
     * codes in the future, on new validations and functions.
     * @param $arguments
     * @return int|string|string[]
     * @throws Exception
     */
    public function send($arguments)
    {
        /**
         * Generate the values from the arguments
        */
        $preview = (!empty($arguments['preview'])) ? $arguments['preview'] : false;
        $type = $arguments['type'];
        $addresses = (!empty($arguments['address'])) ? $arguments['address'] : '';
        $username = (!empty($arguments['username'])) ? $arguments['username'] : '';
        $password = (!empty($arguments['password'])) ? $arguments['password'] : '';
        $client_id = (!empty($arguments['client_id'])) ? $arguments['client_id'] : '';
        $name = (!empty($arguments['name'])) ? $arguments['name'] : '';
        $files_list = (!empty($arguments['files_list'])) ? $arguments['files_list'] : '';
        $file_id = (!empty($arguments['file_id'])) ? $arguments['file_id'] : '';
        $token = (!empty($arguments['token'])) ? $arguments['token'] : '';
        $memberships = (!empty($arguments['memberships'])) ? $arguments['memberships'] : '';
        $note = (!empty($arguments['note'])) ? $arguments['note'] : '';
        $links = (!empty($arguments['links'])) ? $arguments['links'] : '';
        $uploader = (!empty($arguments['uploader'])) ? $arguments['uploader'] : '';

        $try_bcc = false;
        switch ($type) {
            case 'new_files_by_user':
                $body_variables = [ $files_list, ];
                /** @noinspection PhpUndefinedConstantInspection */
                if (MAIL_COPY_USER_UPLOAD == '1') {
                    $try_bcc = true;
                }
                break;
            case 'new_files_by_client':
                $body_variables = [ $files_list, ];
                /** @noinspection PhpUndefinedConstantInspection */
                if (MAIL_COPY_CLIENT_UPLOAD == '1') {
                    $try_bcc = true;
                }
                break;
            case 'limit_retention':
                $body_variables = [ $files_list, ];
                break;
            case 'new_user':
            case 'new_client':
                $body_variables = [ $username, $password, ];
                break;
            case 'new_client_self':
                $body_variables = [ $username, $name, $memberships ];
                break;
            case 'client_edited':
            case 'account_approve':
                $body_variables = [ $username, $name, $memberships, ];
                break;
            case 'account_deny':
                $body_variables = [ $username, $name, ];
                break;
            case 'password_reset':
                $body_variables = [ $username, $token, ];
                break;
            case 'public_links':
                $body_variables = [ $links, $note, $uploader ];
                break;
        }

        /**
         * Generates the subject and body contents
        */
        $method = 'email_' . $type;
        $mail_info = call_user_func_array([$this, $method], $body_variables);

        /**
         * Replace the default info on the footer
         */
        $mail_info['body'] = str_replace(
            array(
                '%FOOTER_SYSTEM_URI%',
                '%FOOTER_URI%'
            ),
            array(
                SYSTEM_URI,
                BASE_URI
            ),
            $mail_info['body']
        );

        /**
         * If we are generating a preview, just return the html content
         */
        if ($preview == true) {
            return $mail_info['body'];
        } else {

            /**
             * phpMailer
             */
            $send_mail = new PHPMailer();
            $send_mail->SMTPDebug = 0;
            $send_mail->CharSet = EMAIL_ENCODING;

            /** @noinspection PhpUndefinedConstantInspection */
            switch (MAIL_SYSTEM_USE) {
                case 'smtp':
                    $send_mail->IsSMTP();
                    /** @noinspection PhpUndefinedConstantInspection */
                    $send_mail->Host = MAIL_SMTP_HOST;
                    /** @noinspection PhpUndefinedConstantInspection */
                    $send_mail->Port = MAIL_SMTP_PORT;
                    /** @noinspection PhpUndefinedConstantInspection */
                    $send_mail->Username = MAIL_SMTP_USER;
                    /** @noinspection PhpUndefinedConstantInspection */
                    $send_mail->Password = MAIL_SMTP_PASS;

                    $send_mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ),
                    );

                    if (defined('MAIL_SMTP_AUTH') && MAIL_SMTP_AUTH != 'none') {
                        $send_mail->SMTPAuth = true;
                        $send_mail->SMTPSecure = MAIL_SMTP_AUTH;
                    } else {
                        $send_mail->SMTPAuth = false;
                    }
                    break;
                case 'gmail':
                    $send_mail->IsSMTP();
                    $send_mail->SMTPAuth = true;
                    $send_mail->SMTPSecure = "tls";
                    $send_mail->Host = 'smtp.gmail.com';
                    $send_mail->Port = 587;
                    /** @noinspection PhpUndefinedConstantInspection */
                    $send_mail->Username = MAIL_SMTP_USER;
                    /** @noinspection PhpUndefinedConstantInspection */
                    $send_mail->Password = MAIL_SMTP_PASS;
                    break;
                case 'sendmail':
                    $send_mail->IsSendmail();
                    break;
            }

            $send_mail->Subject = $mail_info['subject'];
            $send_mail->MsgHTML($mail_info['body']);
            $send_mail->AltBody = __('This email contains HTML formatting and cannot be displayed right now. Please use an HTML compatible reader.', 'cftp_admin');

            /** @noinspection PhpUndefinedConstantInspection */
            $send_mail->SetFrom(ADMIN_EMAIL_ADDRESS, MAIL_FROM_NAME);
            /** @noinspection PhpUndefinedConstantInspection */
            $send_mail->AddReplyTo(ADMIN_EMAIL_ADDRESS, MAIL_FROM_NAME);

            if (!empty($name)) {
                $send_mail->AddAddress($addresses, $name);
            } else {
                if (is_array($addresses)) {
                    foreach ($addresses as $address) {
                        $send_mail->AddAddress($address);
                    }
                } else {
                    $send_mail->AddAddress($addresses);
                }
            }

            /**
             * Check if BCC is enabled and get the list of
             * addresses to add, based on the email type.
             */
            if ($try_bcc === true) {
                $add_bcc_to = array();
                if (MAIL_COPY_MAIN_USER == '1') {
                    /** @noinspection PhpUndefinedConstantInspection */
                    $add_bcc_to[] = ADMIN_EMAIL_ADDRESS;
                }
                /** @noinspection PhpUndefinedConstantInspection */
                $more_addresses = MAIL_COPY_ADDRESSES;
                if (!empty($more_addresses)) {
                    $more_addresses = explode(',', $more_addresses);
                    foreach ($more_addresses as $add_bcc) {
                        $add_bcc_to[] = $add_bcc;
                    }
                }
                /**
                 * Add the BCCs with the compiled array.
                 * First, clean the array to make sure the admin
                 * address is not written twice.
                 */
                if (!empty($add_bcc_to)) {
                    $add_bcc_to = array_unique($add_bcc_to);
                    foreach ($add_bcc_to as $set_bcc) {
                        $send_mail->AddBCC($set_bcc);
                    }
                }
            }

            /**
             * Debug by echoing the email on page
            */
            //echo $mail_info['body'];
            //die();

            /**
             * Finally, send the e-mail.
             */
            if ($send_mail->Send()) {
                return 1;
            } else {
                return 2;
            }
        }
    }
}
