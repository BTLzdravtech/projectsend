<?php
/**
 * Class that generates a form and its fields.
 *
 * @package		ProjectSend
 * @subpackage	Classes
 */

namespace ProjectSend\Classes;

class FormGenerate {

    private $dbh;

    private $open;
    private $close;
    private $output;
    private $contents;

    private $group_class;
    private $checkbox_group_class;
    private $label_class;
    private $wrap_class;
    private $wrap_group;
    private $checkbox_wrap;
    private $field_class;

    private $password_toggle_wrap;
    private $password_toggle_btn;
    public $new_password_fields;

    private $ignore_field_class;

    private $ignore_layout;

	function __construct() {
		global $dbh;
		$this->dbh = $dbh;
		
		$this->close				= "</form>\n";
		$this->output				= '';
		$this->contents				= '';

		$this->group_class 			= 'form-group';
		$this->checkbox_group_class = 'checkbox';
		$this->label_class			= 'col-sm-4 control-label';
		$this->wrap_class 			= 'col-sm-8';
		$this->wrap_group 			= 'input-group';
		$this->checkbox_wrap		= 'col-sm-8 col-sm-offset-4';
		$this->field_class			= 'form-control';

		$this->password_toggle_wrap = 'input-group-btn password_toggler';		
		$this->password_toggle_btn	= 'pass_toggler_show';
		$this->new_password_fields	= array();

		$this->ignore_field_class	= array(
											'hidden',
											'checkbox',
											'radio',
											'separator',
										);

		$this->ignore_layout		= array(
											'hidden',
											'separator',
										);
	}

	/**
	 * Create the form
	 */
	public function create( $arguments ) {
		$this->open			.= $this->generate_tag( 'form', false, false, false, $arguments );
	}

	/**
	 * Generate each tag
	 * form, input, textarea, etc
	 */
	private function generate_tag( $element, $close_tag, $type, $add_type, $arguments ) {

		$attributes 	= !( empty( $arguments['attributes'] ) )	? $arguments['attributes'] : null;
		$value		= !( empty( $arguments['value'] ) )			? $arguments['value'] : null;
		$content		= !( empty( $arguments['content'] ) )		? $arguments['content'] : null;
		$options		= !( empty( $arguments['options'] ) )		? $arguments['options'] : null;
		$check_var	= !( empty( $arguments['check_var'] ) )		? $arguments['check_var'] : null;
		$selected		= !( empty( $arguments['selected'] ) )		? $arguments['selected'] : null;
		$required		= !( empty( $arguments['required'] ) )		? true : false;
		$label 		= !( empty( $arguments['label'] ) )			? $arguments['label'] : null;

		$properties	= array();
		$result		= '';

		if ( $element != 'form' ) {
			$result .= "\t";
		}

		$result .= '<' . $element . ' ';
		
		if ( $add_type == true ) {
			$properties['type'] = $type;
		}

		foreach ( $attributes as $tag => $val ) {
			if ( empty( $val ) ) {
				$properties[$tag] = '';
			}
			else {
				$properties[$tag] = $val;
			}
		}

		/** If ID is not defined, use the name attr to add it */
		if ( !empty( $attributes['name'] ) && empty( $attributes['id'] ) ) {
			$properties['id'] = $attributes['name'];
		}

		if ( $required == true ) {
			$properties['required'] = '';
		}

		if ( !empty( $check_var ) ) {
			if ( $check_var == $arguments['value'] ) {
				$properties['checked'] = 'checked';
			}
		}

		if ( !empty( $value ) ) {
			$properties['value'] = $value;
		}

		$produce = array();
		foreach ( $properties as $property => $val ) {
			if ( !empty( $val ) ) {
				$produce[] = $property . '="' . $val . '"';
			}
			else {
				$produce[] = $property;
			}
		}
		
		/** Add each attribute to the tag */
		$result .= implode(' ', $produce);

		/** Close the opening tag */
		$result .= '>' . "\n";

		/** Used on textarea */
		if ( !empty( $content ) ) {
			$result .= $content;
		}

		/** Used on select */
		if ( !empty( $options ) ) {
			foreach ( $options as $val => $name ) {
				$result .= $this->generate_option( $val, $name, $selected );
			}
		}

		/** Does the element need closing tag? (textarea, select...) */
		if ( $close_tag == true ) {
			$result .= '</' . $type . '>' . "\n";
		}

		return $result;
	}
	
	/**
	 * Generate the options for a select field
	 */
	private function generate_option( $value, $name, $selected ) {
		$option_properties = array();

		$option = "\t\t\t" . '<option ';
		$option_properties[] = 'value="' . $value . '"';
		if ( !empty( $selected ) && $selected == $value ) {
			$option_properties[] = 'selected="selected"';
		}
		/** Add the properties */
		$option .= implode(' ', $option_properties);

		$option .= '>' . $name;
		$option .= '</option>' . "\n";

		return $option;
	}

	/**
	 * Generate a simple separator
	 */
	private function generate_separator() {
		$option = "\n" . '<div class="separator"></div>' . "\n\n";
		return $option;
	}
	
	/**
	 * This button goes under the password field and generates
	 * a new random password. The $field_name param is the input
	 * that the result will be applied to.
	 */
	private function generate_password_button( $field_name ) {
		$button_arguments = array(
									'type'			=> 'button',
									'content'		=> 'Generate',
									'attributes'	=> array(
															'name'			=> 'generate_password',
															'class'			=> 'btn btn-default btn-sm btn_generate_password',
															'data-ref'		=> $field_name,
															'data-min'		=> MIN_PASS_CHARS,
															'data-max'		=> MAX_PASS_CHARS,
														)
									);
		$button = $this->generate_tag( 'button', true, $button_arguments['type'], true, $button_arguments );
		$this->new_password_fields[] = $button_arguments['attributes']['name'];

		return $button;
	}


	
	public function field( $type, $arguments ) {
		/** Set default to avoid repetition */
		$label_location	= 'outside';
		$use_layout		= ( !in_array( $type, $this->ignore_layout ) ) ? true : false;

		if ( !empty( $arguments['required'] ) && $arguments['required'] == true) {
			$arguments['attributes']['class'][] = 'required';
		}

		if ( !empty( $arguments['label'] ) ) {
			$label = '<label>' . $arguments['label'] . '</label>' . "\n";
		}

		/**
		 * Try to add the default field class
		 */
		if ( !in_array( $type, $this->ignore_field_class ) ) {
			if ( empty( $arguments['default_class'] ) || $arguments['default_class'] == true ) {
				$arguments['attributes']['class'][] = $this->field_class;
			}
		}
		
		/** Concat the classes */
		if ( !empty( $arguments['attributes']['class'] ) ) {
			$arguments['attributes']['class'] = implode(' ', $arguments['attributes']['class']);
		}

		switch ( $type ) {
			case 'text':
			default:
				$field = $this->generate_tag( 'input', false, $type, true, $arguments );
				break;
			case 'password':
				$field = $this->generate_tag( 'input', false, $type, true, $arguments );
				break;
			case 'hidden':
				$field = $this->generate_tag( 'input', false, $type, true, $arguments );
				break;
			case 'textarea':
				$field = $this->generate_tag( 'textarea', true, $type, false, $arguments );
				break;
			case 'select':
				$field = $this->generate_tag( 'select', true, $type, false, $arguments );
				break;
			case 'checkbox':
			case 'radio':
				$label_location = 'wrap';
				$field = $this->generate_tag( 'input', false, $type, true, $arguments );
				break;
			case 'button':
				$field = $this->generate_tag( 'button', true, $type, false, $arguments );
				break;
			case 'separator':
				$field = $this->generate_separator();
				break;
		}
		
		/**
		 * Format according to the Bootstrap 3 layout
		 */
		if ( $use_layout == true ) {
			$layout = '<div class="' . $this->group_class . '">' . "\n";
				switch ( $label_location ) {
					case 'outside':
							$format	= "\t" . '<label for="%s" class="%s">%s</label>' . "\n";
							$layout	.= sprintf( $format, $arguments['attributes']['name'], $this->label_class, $arguments['label'] );
							$layout	.= "\t" . '<div class="' . $this->wrap_class . '">' . "\n";

							if ( $type == 'password' ) {
								$layout .= "\t\t" . '<div class="' . $this->wrap_group . '">' . "\n";
								$layout .= "\t\t" . $field;
								$layout .= "\t\t\t" . '<div class="' . $this->password_toggle_wrap . '">' . "\n";
								$layout .= "\t\t\t\t" . '<button type="button" class="btn ' . $this->password_toggle_btn . '"><i class="glyphicon glyphicon-eye-open"></i></button>' . "\n";
								$layout .= "\t\t\t" . '</div>' . "\n";
								if ( function_exists( 'password_notes' ) ) {
									$layout .= password_notes();
								}
								$layout .= "\t\t" . '</div>' . "\n";
								
								if ( !empty( $arguments['pass_type'] ) && $arguments['pass_type'] == 'create' ) {
									$layout .= $this->generate_password_button( $arguments['attributes']['name'] );
								}
							}
							else {
								$layout .= "\t" . $field;
							}

							$layout .= "\t" . '</div>' . "\n";
						break;
					case 'wrap':
							$layout .= "\t" . '<div class="' . $this->checkbox_wrap . '">' . "\n";
							$layout .= "\t\t" . '<div class="' . $type . '">' . "\n";
							$layout .= "\t\t\t" . '<label for="' . $arguments['attributes']['name'] . '">' . "\n";
							$layout .= "\t\t\t" . $field;
							$layout .= "\t\t\t\t" . ' ' . $arguments['label'] . "\n";
							$layout .= "\t\t\t" . '</label>' . "\n";
							$layout .= "\t\t" . '</div>' . "\n";
							$layout .= "\t" . '</div>' . "\n";
						break;
				}
			$layout .= "</div>\n";
		}
		else {
			$layout = $field;
		}
		
		$this->contents .= $layout;
	}
	
	public function output() {
		$this->output = $this->open . $this->contents . $this->close;
		return $this->output;
	}
}