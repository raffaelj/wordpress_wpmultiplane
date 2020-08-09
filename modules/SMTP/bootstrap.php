<?php


$this->module('smtp')->extend([

    'name'        => 'smtp',
    'title'       => $this('i18n')->get('Mailer Settings'),
    'description' => $this('i18n')->get('Use SMTP Mailer, is required by Forms module'),
    'hasSettings' => true,

]);

// overwrite PHPMailer config for wp_mail function
\add_action('phpmailer_init', function(\PHPMailer $mailer) {

    $settingsPrefix = $this('settings')->prefix;
    $options = \get_option($settingsPrefix.'smtp');

    if ($options['transport'] == 'smtp') {

        $mailer->isSMTP();

        if (isset($options['host']) && $options['host'])      {
            $mailer->Host = $options['host']; // Specify main and backup server
        }

        if (isset($options['auth']) && $options['auth']) {
            $mailer->SMTPAuth = $options['auth']; // Enable SMTP authentication
        }

        if (isset($options['user']) && $options['user']) {
            $mailer->Username = $options['user']; // SMTP username
        }

        if (isset($options['password']) && $options['password']) {
            $mailer->Password = $options['password']; // SMTP password
        }

        if (isset($options['port']) && $options['port']) {
            $mailer->Port = $options['port']; // smtp port
        }

        if (isset($options['encryption']) && $options['encryption']) {
            $mailer->SMTPSecure = $options['encryption']; // Enable encryption: 'ssl' , 'tls', 'starttls' accepted
        }

        // Extra smtp options
        if (isset($options['smtp']) && is_array($options['smtp'])) {
            $mailer->SMTPOptions = $options['smtp'];
        }
    }

    // $mailer->SMTPDebug = 0;
    $mailer->CharSet = 'utf-8';

    if (isset($options['from']) && $options['port']) {
        $mailer->From = $options['from'];
    }
    if (!empty($options['from_name'])) {
        $mailer->FromName = $options['from_name'];
    }

}, 10, 1);


// only backend
\add_action('init', function() {
    if (COCKPIT_ADMIN) {
        include_once(__DIR__.'/admin.php');
    }
});
