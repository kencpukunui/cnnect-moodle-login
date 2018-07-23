<style type="text/css">
/* Ticket number is LJA-305087 
 * 08062018 This style will hide specific username text and its column field for the new registering user*/
.fitemtitle label[for="id_username"] {display: none;}
#id_username {display: none;}
</style>
<?php
/**
 * Ticket number is LJA-305087
 * Customised a moodle sign up form (This is a new hook form)
 * New user can use email to be their account for registration
 * Basically this new_signup_form.php is the same like signup_form.php under folder /home/ken/public_html/login
 * I have changed the associated php file. You can use keyword "08062018" or "LJA-305087" to search it on the below file paths.
 * (1) /home/ken/public_html/lib/authlib.php
 * Created a customise function named signup_hook_form() [line 308]
 * | When call the function will redirect user to the hook form
 * Created a customise function named signup_setup_new_user_username_as_email($user) [line 971]
 * | When call the function will replace username to be email
 * (2) /home/ken/public_html/login/new_signup_form.php
 * Hide Username text and column from the new_signup_form [Line 1] to [Line 5] CSS style
 * | .fitemtitle label[for="id_username"] {display: none;}
 * | #id_username {display: none;}
 * (3) /home/ken/public_html/login/signup.php
 * Redirect user to hook form [line 83]
 * | $mform_signup = $authplugin->signup_hook_form();
 * Call customise function to replace the username to be an email [line 93]
 * | $user = signup_setup_new_user_username_as_email($user);
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @author     Ken Chang, Pukunui Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

class new_login_signup_form extends moodleform {

    function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'createuserandpass', get_string('createuserandpass'), ''); // Title

        $mform->addElement('text', 'email', get_string('usernameemail'), 'maxlength="100" size="25"');
        $mform->setType('email', core_user::get_property_type('email'));
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');
        $mform->setForceLtr('email');

        // You will not be able to see this field cause the css setting (Hide for user registration).
        // Please see https://ken.test.pukunui.net/admin/search.php?query=css
        // You can disable those mehtod below by using /**/ and refresh the new signup page to see the hidden username field.
        $mform->addElement('text', 'username', get_string('username'), 'maxlength="100" size="12" autocapitalize="none"');
        $mform->setType('username', PARAM_RAW);
        // We need make username filed got something inside, otherwise after validation the system will show you error.
        // It's doesn't matter what value that you are filling in to it.
        // Because I will manipulate the user email parameter to be username in /home/ken/public_html/user/lib.php
        // In this case, I filled the current timestamp into this field.
        // But you will not be able to see it, cause I use some css to hide it.
        $mform->setDefault('username', time());
        
        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }

        $mform->addElement('password', 'password', get_string('password'), 'maxlength="32" size="12"');
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');

        $mform->addElement('header', 'supplyinfo', get_string('supplyinfo'), ''); // Title

        $namefields = useredit_get_required_name_fields(); // See editlib.php - Return required user name fields for forms. firstname & lastname
        foreach ($namefields as $field) {
            $mform->addElement('text', $field, get_string($field), 'maxlength="100" size="30"'); // Use the $field parameter to add element on form
            $mform->setType($field, core_user::get_property_type('firstname')); // Set each type for the $field
            $stringid = 'missing' . $field; // $stringid = 'missingfirstname' and $stringid = 'missingsurname'
            if (!get_string_manager()->string_exists($stringid, 'moodle')) { // string_exists('stringidentifier', 'component_name')
                $stringid = 'required'; // If the string can't be found in moodle string folder then $stringid = 'required'
            }
            $mform->addRule($field, get_string($stringid), 'required', null, 'client'); // When those field enter is null will show user required text
        }

        $mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="20"');
        $mform->setType('city', core_user::get_property_type('city'));
        if (!empty($CFG->defaultcity)) {
            $mform->setDefault('city', $CFG->defaultcity);
        }

        // See moodle string api -> https://docs.moodle.org/dev/String_API
        $country = get_string_manager()->get_list_of_countries(); // Returns a localised list of all country names, sorted by country keys.
        $defaultcountry[''] = get_string('selectacountry'); // Put string selectacountry into string array[0]
        $country = array_merge($defaultcountry, $country); // Combine two arrays, $country will override $defaultcountry if $defaultcountry got same key values
        $mform->addElement('select', 'country', get_string('country'), $country);

        if (!empty($CFG->country)) {
            $mform->setDefault('country', $CFG->country);
        } else {
            $mform->setDefault('country', '');
        }

        profile_signup_fields($mform);

        if (signup_captcha_enabled()) { // Robot validation
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
            $mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('recaptcha_element');
        }

        // Add "Agree to sitepolicy" controls. By default it is a link to the policy text and a checkbox but
        // It can be implemented differently in custom sitepolicy handlers.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // Buttons
        $this->add_action_buttons(true, get_string('createaccount'));
    }

    function definition_after_data() {
        $mform = $this->_form;
    }

    /* 08062018
     * Basically, copy the validate function signup_validate_data() from /home/ken/public_html/lib/authlib.php
    */
    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if (signup_captcha_enabled()) {
            $recaptchaelement = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['g-recaptcha-response'])) {
                $response = $this->_form->_submitValues['g-recaptcha-response'];
                if (!$recaptchaelement->verify($response)) {
                    $errors['recaptcha_element'] = get_string('incorrectpleasetryagain', 'auth');
                }
            } else {
                $errors['recaptcha_element'] = get_string('missingrecaptchachallengefield');
            }
        }

        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');

        } else if ($DB->record_exists('user', array('email' => $data['email']))) {
            $errors['email'] = get_string('emailexists') . ' ' .
                    get_string('emailexistssignuphint', 'moodle',
                            html_writer::link(new moodle_url('/login/forgot_password.php'), get_string('emailexistshintlink')));
        }

        if (!isset($errors['email'])) {
            if ($err = email_is_not_allowed($data['email'])) {
                $errors['email'] = $err;
            }
        }

        // validate the email field, if it contained any uppercase will return error
        if ($data['email'] !== core_text::strtolower($data['email'])) {
                $errors['email'] = 'Your email must not contained any uppercase letter';
        } else {
            if ($data['email'] !== core_user::clean_field($data['email'], 'email')) {
                $errors['email'] = get_string('invalidemail');
            }
        }

        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg)) {
            $errors['password'] = $errmsg;
        }

        return $errors;
    }

    /**
     * Returns whether or not the captcha element is enabled, and the admin settings fulfil its requirements.
     * @return bool
     */
    function signup_captcha_enabled() {
        global $CFG;
        return !empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey) && get_config('auth/emailwc', 'recaptcha');
    }
}