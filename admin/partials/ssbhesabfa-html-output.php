<?php
/*
 * @class      Ssbhesabfa_Html_output
 * @version    2.2.3
 * @since      1.0.0
 * @package    ssbhesabfa
 * @subpackage ssbhesabfa/admin/output
 * @author     Sepehr Najafi <sepehrnm78@yahoo.com>
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 */

class Ssbhesabfa_Html_output {
    public static function init($options = array()) {
        if (!empty($options)) {
            foreach ($options as $value) {
                if (!isset($value['type'])) continue;
                if (!isset($value['id'])) $value['id'] = '';
                if (!isset($value['title'])) $value['title'] = isset($value['name']) ? $value['name'] : '';
                if (!isset($value['class'])) $value['class'] = '';
                if (!isset($value['css'])) $value['css'] = '';
                if (!isset($value['default'])) $value['default'] = '';
                if (!isset($value['desc'])) $value['desc'] = '';
                if (!isset($value['desc_tip'])) $value['desc_tip'] = false;
                $custom_attributes = array();
                if (!empty($value['custom_attributes']) && is_array($value['custom_attributes'])) {
                    foreach ($value['custom_attributes'] as $attribute => $attribute_value) {
                        $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
                    }
                }
                if (true === $value['desc_tip']) {
                    $description = '';
                    $tip = $value['desc'];
                } elseif (!empty($value['desc_tip'])) {
                    $description = $value['desc'];
                    $tip = $value['desc_tip'];
                } elseif (!empty($value['desc'])) {
                    $description = $value['desc'];
                    $tip = '';
                } else {
                    $description = $tip = '';
                }
                if ($description && in_array($value['type'], array('textarea', 'radio'))) {
                    $description = '<p style="margin-top:0" class="hesabfa-p">' . wp_kses_post($description) . '</p>';
                } elseif ($description && in_array($value['type'], array('checkbox'))) {
                    $description = wp_kses_post($description);
                } elseif ($description) {
                    //$description = '<span class="description">' . wp_kses_post($description) . '</span>';
                }
                if (isset($value['placeholder']) && !empty($value['placeholder'])) {
                    $placeholder = $value['placeholder'];
                } else {
                    $placeholder = '';
                }
                if ($tip && in_array($value['type'], array('checkbox'))) {

                    $tip = '<p class="description hesabfa-p">' . $tip . '</p>';
                }
                switch ($value['type']) {
                    case 'title':
                        if (!empty($value['title'])) {
                            echo '<h3 class="hesabfa-tab-page-title">' . esc_html($value['title']) . '</h3>';
                        }
                        if (!empty($value['desc'])) {
                            echo esc_html(wpautop(wptexturize(wp_kses_post($value['desc']))));
                        }
                        echo '<table class="form-table hesabfa-p">' . "\n\n";
                        break;
                    case 'sectionend':
                        echo '</table>';
                        break;
                    case 'text':
                    case 'email':
                    case 'number':
                    case 'color' :
                    case 'password' :
                        $type = $value['type'];
                        $class = '';
                        $option_value = self::get_option($value['id'], $value['default']);
                        if ($value['type'] == 'color') {
                            $type = 'text';
                            $value['class'] .= 'colorpick';
                            $description .= '<div id="colorPickerDiv_' . esc_attr($value['id']) . '" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>';
                        }
                        ?><tr style="vertical-align: top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
                                <?php echo esc_html($tip); ?>
                            </th>
                            <td class="forminp forminp-<?php echo esc_html(sanitize_title($value['type'])) ?>">
                                <input
                                    name="<?php echo esc_attr($value['id']); ?>"
                                    id="<?php echo esc_attr($value['id']); ?>"
                                    type="<?php echo esc_attr($type); ?>"
                                    style="<?php echo esc_attr($value['css']); ?>"
                                    value="<?php echo esc_attr($option_value); ?>"
                                    class="<?php echo esc_attr($value['class']); ?>"
                                    placeholder="<?php echo esc_attr($placeholder); ?>"
                                    <?php echo esc_attr(implode(' ', $custom_attributes)); ?>
                                    /> <?php echo wp_kses_post($description); ?>
                            </td>
                        </tr><?php
                        break;
                    case 'textarea':
                        $option_value = self::get_option($value['id'], $value['default']);
                        ?><tr style="vertical-align: top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
                                <?php echo esc_html($tip) ?>
                            </th>
                            <td class="forminp forminp-<?php echo esc_html(sanitize_title($value['type'])) ?>">
                                <?php echo esc_html($description); ?>
                                <?php
                                if (isset($value['editor']) && $value['editor'] == 'true') {

                                    echo esc_html(wp_editor($option_value, $value['id']));
                                } else {
                                    ?>
                                    <textarea
                                        name="<?php echo esc_attr($value['id']); ?>"
                                        id="<?php echo esc_attr($value['id']); ?>"
                                        style="<?php echo esc_attr($value['css']); ?>"
                                        class="<?php echo esc_attr($value['class']); ?>"
                                        <?php echo esc_attr(implode(' ', $custom_attributes)); ?>
                                        ><?php echo esc_textarea($option_value); ?></textarea>
                                    <?php } ?>
                            </td>
                        </tr><?php
                        break;
                    case 'select' :
                    case 'multiselect' :
                        $option_value = self::get_option($value['id'], $value['default']);
                        ?><tr style="vertical-align: top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
                                <?php echo esc_html($tip); ?>
                            </th>
                            <td class="forminp forminp-<?php echo esc_html(sanitize_title($value['type'])) ?>">
                                <select
                                    name="<?php echo esc_attr($value['id']); ?><?php if ($value['type'] == 'multiselect') echo '[]'; ?>"
                                    id="<?php echo esc_attr($value['id']); ?>"
                                    style="<?php echo esc_attr($value['css']); ?>"
                                    class="<?php echo esc_attr($value['class']); ?>"
                                    <?php echo esc_attr(implode(' ', $custom_attributes)); ?>
                                    <?php echo ( 'multiselect' == $value['type'] ) ? 'multiple="multiple"' : ''; ?>
                                    >
                                        <?php
                                        foreach ($value['options'] as $key => $val) {
                                            ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php
                                        if (is_array($option_value)) {
                                            selected(in_array($key, $option_value), true);
                                        } else {
                                            selected($option_value, $key);
                                        }
                                        ?>><?php echo esc_html($val) ?></option>
                                                <?php
                                            }
                                            ?>
                                </select> <?php echo esc_html($description); ?>
                            </td>
                        </tr><?php
                        break;
                    case 'radio' :
                        $option_value = self::get_option($value['id'], $value['default']);
                        ?><tr style="vertical-align: top">
                            <th scope="row" class="titledesc">
                                <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
                                <?php echo esc_html($tip); ?>
                            </th>
                            <td class="forminp forminp-<?php echo esc_html(sanitize_title($value['type'])) ?>">
                                <fieldset>
                                    <?php echo esc_html($description); ?>
                                    <ul>
                                        <?php
                                        foreach ($value['options'] as $key => $val) {
                                            ?>
                                            <li>
                                                <label><input
                                                        name="<?php echo esc_attr($value['id']); ?>"
                                                        value="<?php echo esc_attr($key); ?>"
                                                        type="radio"
                                                        style="<?php echo esc_attr($value['css']); ?>"
                                                        class="<?php echo esc_attr($value['class']); ?>"
                                                        <?php echo esc_attr(implode(' ', $custom_attributes)); ?>
                                                        <?php checked($key, $option_value); ?>
                                                        /> <?php echo esc_html($val) ?></label>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </fieldset>
                            </td>
                        </tr><?php
                        break;
                    case 'checkbox' :
                        $option_value = self::get_option($value['id'], $value['default']);
                        $visbility_class = array();
                        if (!isset($value['hide_if_checked'])) {
                            $value['hide_if_checked'] = false;
                        }
                        if (!isset($value['show_if_checked'])) {
                            $value['show_if_checked'] = false;
                        }
                        if ('yes' == $value['hide_if_checked'] || 'yes' == $value['show_if_checked']) {
                            $visbility_class[] = 'hidden_option';
                        }
                        if ('option' == $value['hide_if_checked']) {
                            $visbility_class[] = 'hide_options_if_checked';
                        }
                        if ('option' == $value['show_if_checked']) {
                            $visbility_class[] = 'show_options_if_checked';
                        }
                        if (!isset($value['checkboxgroup']) || 'start' == $value['checkboxgroup']) {
                            ?>
                            <tr style="vertical-align: top" class="<?php echo esc_attr(implode(' ', $visbility_class)); ?>">
                                <th scope="row" class="titledesc"><?php echo esc_html($value['title']) ?></th>
                                <td class="forminp forminp-checkbox">
                                    <fieldset>
                                        <?php
                                    } else {
                                        ?>
                                        <fieldset class="<?php echo esc_attr(implode(' ', $visbility_class)); ?>">
                                            <?php
                                        }
                                        if (!empty($value['title'])) {
                                            ?>
                                            <legend class="screen-reader-text"><span><?php echo esc_html($value['title']) ?></span></legend>
                                            <?php
                                        }
                                        ?>
                                        <label for="<?php echo esc_attr($value['id']) ?>">
                                            <input
                                                name="<?php echo esc_attr($value['id']); ?>"
                                                id="<?php echo esc_attr($value['id']); ?>"
                                                type="checkbox"
                                                value="1"
                                                <?php checked($option_value, 'yes'); ?>
                                                <?php echo esc_attr(implode(' ', $custom_attributes)); ?>
                                                /> <?php echo esc_html($description) ?>
                                        </label> <?php echo esc_html($tip); ?>
                                        <?php
                                        if (!isset($value['checkboxgroup']) || 'end' == $value['checkboxgroup']) {
                                            ?>
                                        </fieldset>
                                </td>
                            </tr>
                            <?php
                        } else {
                            ?>
                            </fieldset>
                            <?php
                        }
                        break;
                    case 'single_select_page' :
                        $args = array(
                            'name' => $value['id'],
                            'id' => $value['id'],
                            'sort_column' => 'menu_order',
                            'sort_order' => 'ASC',
                            'show_option_none' => ' ',
                            'class' => $value['class'],
                            'echo' => false,
                            'selected' => absint(self::get_option($value['id']))
                        );
                        if (isset($value['args'])) {
                            $args = wp_parse_args($value['args'], $args);
                        }
                        ?><tr style="vertical-align: top" class="single_select_page">
                            <th scope="row" class="titledesc"><?php echo esc_html($value['title']) ?> <?php echo esc_html($tip); ?></th>
                            <td class="forminp">
                                <?php echo esc_html(str_replace(' id=', " data-placeholder='" . __('Select a page&hellip;', 'Option') . "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages($args))); ?> <?php echo esc_html($description); ?>
                            </td>
                        </tr><?php
                        break;
                    default:
                        break;
                }
            }
        }
    }
//=====================================================================================================
    public static function get_option($option_name, $default = '') {

        if (strstr($option_name, '[')) {
            parse_str($option_name, $option_array);
            $option_name = current(array_keys($option_array));
            $option_values = get_option($option_name, '');
            $key = key($option_array[$option_name]);
            if (isset($option_values[$key])) {
                $option_value = $option_values[$key];
            } else {
                $option_value = null;
            }
        } else {
            $option_value = get_option($option_name, null);
        }
        if (is_array($option_value)) {
            $option_value = array_map('stripslashes', $option_value);
        } elseif (!is_null($option_value)) {
            $option_value = stripslashes($option_value);
            if (empty($option_value) && !empty($default)) {
                $option_value = null;
            }
        }
        return $option_value === null ? $default : $option_value;
    }
//=====================================================================================================
    public static function save_fields($options) {
        if (empty($_POST)) return false;

        if (!isset($_POST['ssbhesabfa_api_nonce']) || !wp_verify_nonce($_POST['ssbhesabfa_api_nonce'], 'ssbhesabfa_api_nonce')) {
			wp_die(__('Security check failed', 'ssbhesabfa'));
		}

        $update_options = array();
        foreach ($options as $value) {
            if (!isset($value['id']) || !isset($value['type'])) {
                continue;
            }

            if (strstr($value['id'], '[')) {
                parse_str($value['id'], $option_name_array);
                $option_name = current(array_keys($option_name_array));
                $setting_name = key($option_name_array[$option_name]);
                $option_value = isset($_POST[$option_name][$setting_name]) ? wc_clean($_POST[$option_name][$setting_name]) : null;
            } else {
                $option_name = $value['id'];
                $setting_name = '';
                $option_value = isset($_POST[$value['id']]) ? wc_clean($_POST[$value['id']]) : null;
            }
            switch (sanitize_title($value['type'])) {
                case 'checkbox' :
                    $option_value = is_null($option_value) ? 'no' : 'yes';
                    break;
                case 'textarea' :
                    $option_value = wp_kses_post(trim($option_value));
                    break;
                case 'text' :
                case 'email':
                case 'number':
                case 'select' :
                case 'color' :
                case 'password' :
                case 'radio' :
                    $option_value = $option_value;
                    break;
                default :
                    break;
            }
            if (!is_null($option_value)) {
                if ($option_name && $setting_name) {
                    if (!isset($update_options[$option_name])) {
                        $update_options[$option_name] = get_option($option_name, array());
                    }
                    if (!is_array($update_options[$option_name])) {
                        $update_options[$option_name] = array();
                    }
                    $update_options[$option_name][$setting_name] = $option_value;
                } else {
                    $update_options[$option_name] = $option_value;
                }
            }
        }
        foreach ($update_options as $name => $value) {
            if(isset($update_options["ssbhesabfa_invoice_freight"])) {
                if(!isset($update_options["ssbhesabfa_invoice_status"])) {
                    update_option("ssbhesabfa_invoice_status", 0);
                }
                if(!isset($update_options["ssbhesabfa_invoice_return_status"])) {
                    update_option("ssbhesabfa_invoice_return_status", 0);
                }
            }
            update_option($name, wc_clean($value));
        }
        echo '<div class="updated"><p class="hesabfa-p">' . esc_html__( 'Settings were saved successfully.', 'ssbhesabfa' ) . '</p></div>';
        return true;
    }
//=====================================================================================================
}

Ssbhesabfa_Html_output::init();
