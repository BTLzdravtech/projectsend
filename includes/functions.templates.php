<?php
/**
 * Functions related to the files list templates
 *
 * @package    ProjectSend
 * @subpackage Templates
 */

/**
 * Get the template and author information from template.php
 * Based on the WordPress (LOVE IT!) function get_file_data()
 *
 * @param  [type] $template_file
 * @return array|bool
 */
function extract_template_info($template_directory)
{
    if (empty($template_directory)) {
        return false;
    }

    $folder = str_replace(TEMPLATES_DIR . DS, '', $template_directory);

    $read_file = $template_directory . DS . 'template.php';
    $fp = fopen($read_file, 'r');
    $file_info = fread($fp, 8192);
    fclose($fp);

    $file_info = str_replace("\r", "\n", $file_info);

    $template_info = array(
        'name' => 'Template name',
        'themeuri' => 'URI',
        'author' => 'Author',
        'authoruri' => 'Author URI',
        'authoremail' => 'Author e-mail',
        'domain' => 'Domain',
        'description' => 'Description',
    );

    foreach ($template_info as $data => $regex) {
        if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $file_info, $match) && $match[1]) {
            $template_info[$data] = html_output($match[1]);
        } else {
            $template_info[$data] = '';
        }
    }

    if (empty($template_info['name'])) {
        $template_info['name'] = $template_directory;
    }

    // Location is the value saved on the DB.
    $template_info['location'] = $folder;

    // Currently active template
    /** @noinspection PhpUndefinedConstantInspection */
    if ($folder == SELECTED_CLIENTS_TEMPLATE) {
        $template_info['active'] = 1;
    }

    // Look for the screenshot
    $screenshot_file = $template_directory . DS . 'screenshot.png';
    $cover_file = $template_directory . DS . 'cover.png';
    $screenshot_url = TEMPLATES_URL . DS . $folder . DS . 'screenshot.png';
    $cover_url = TEMPLATES_URL . DS . $folder . DS . 'cover.png';

    $template_info['screenshot'] = (file_exists($screenshot_file)) ? $screenshot_url : ASSETS_IMG_URL . 'template-screenshot.png';
    if (file_exists($cover_file)) {
        $template_info['cover'] = $cover_url;
    }

    return $template_info;
}

/**
 * Generates an array of valid templates to use on the options page.
 *
 * The template name must be defined on line 4 of template.php
 *
 * @return array|void
 */
function look_for_templates()
{
    // Get all folders under the templates directory
    $templates = array();
    $templates_error = array();

    $ignore = array('.', '..');
    $base_directory = TEMPLATES_DIR . DS;
    $directories = glob($base_directory . "*");
    foreach ($directories as $directory) {
        if (is_dir($directory) && !in_array($directory, $ignore)) {
            if (check_template_integrity($directory)) {
                $template_info = extract_template_info($directory);

                // Generate the valid templates array
                $templates[] = $template_info;
            } else {
                // Generate another array with the templates that are not complete
                $templates_error[] = array(
                    'templates_error' => $directory
                );
            }
        }
    }

    // Put active template as first element of the array
    foreach ($templates as $index => $template) {
        if (array_key_exists('active', $template)) {
            unset($templates[$index]);
            array_unshift($templates, $template);
        }
    }

    //print_array($templates);
    return $templates;
}

/**
 * Define the basic files that each template must have to be considered valid
 *
 * Each template must have at least two files:
 * template.php and main.css
 *
 * @param  [type] $folder
 * @return bool
 */
function check_template_integrity($folder)
{
    $required_files = array(
        'template.php',
        'main.css'
    );
    $miss = 0;
    $found = glob($folder . "/*");
    foreach ($required_files as $required) {
        $this_file = $folder . '/' . $required;
        if (!in_array($this_file, $found)) {
            $miss++;
        }
    }

    if ($miss == 0) {
        return true;
    }

    unset($miss);

    return false;
}

/**
 * Prepare the current files template and show it
 *
 * @return void
 */
function set_up_template()
{
    // Load values from the config file

    // If config file doesn't exist, set default values

    // Include the common functions

    // Include the main template file
}
