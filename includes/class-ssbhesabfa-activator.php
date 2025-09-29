<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @class      Ssbhesabfa_Activator
 * @version    2.2.5
 * @since      1.0.0
 * @package    ssbhesabfa
 * @subpackage ssbhesabfa/includes
 * @author     Sepehr Najafi <sepehrnm78@yahoo.com>
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 */
class Ssbhesabfa_Activator {
    public static $ssbhesabfa_db_version = '1.1';

    /**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
//===============================================================================================================
	public static function activate() {
        add_option('ssbhesabfa_webhook_password', bin2hex(openssl_random_pseudo_bytes(16)));
        add_option('ssbhesabfa_last_log_check_id', 0);
        add_option('ssbhesabfa_live_mode', 0);
        add_option('ssbhesabfa_debug_mode', 0);
        add_option('ssbhesabfa_check_for_sync', 0);
        //add_option('ssbhesabfa_check_for_sync_webhook', 1);
        add_option('ssbhesabfa_invoice_save_for_one_person_in_hesabfa', 0);
        add_option('ssbhesabfa_invoice_freight', 0);
        add_option('ssbhesabfa_save_order_option', 0);
        add_option('ssbhesabfa_check_for_sync_auto', 0);
        add_option('ssbhesabfa_contact_address_status', 1);
        add_option('ssbhesabfa_contact_node_family', 'مشتریان فروشگاه آن‌لاین');
        add_option('ssbhesabfa_contact_automaatic_save_node_family', 'yes');
        add_option('ssbhesabfa_contact_automatically_save_in_hesabfa', 'yes');
        add_option('ssbhesabfa_activation_date', date("Y-m-d"));
        add_option('ssbhesabfa_use_export_product_opening_quantity', false);
        add_option('ssbhesabfa_business_expired', 0);
        add_option('ssbhesabfa_do_not_submit_product_automatically', "no");
        add_option('ssbhesabfa_do_not_update_product_price_in_hesabfa', "no");
        add_option('ssbhesabfa_contact_add_additional_checkout_fields_hesabfa', 1);

        self::ssbhesabfa_create_database_table();
        self::ssbhesabfa_create_delete_trigger();
        self::ssbhesabfa_alter_table();
	}
//===============================================================================================================
	public static function ssbhesabfa_create_database_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . "ssbhesabfa";

		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
			return; // Table exists, exit the function
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "
	    CREATE TABLE $table_name (
	        id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	        obj_type varchar(32) NOT NULL,
	        id_hesabfa int(11) UNSIGNED NOT NULL,
	        id_ps int(11) UNSIGNED NOT NULL,
	        id_ps_attribute int(11) UNSIGNED NOT NULL DEFAULT 0,
	        PRIMARY KEY  (id)
	    ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$wpdb->query('START TRANSACTION');
		try {
			dbDelta($sql);
			update_option('ssbhesabfa_db_version', self::$ssbhesabfa_db_version);
			$wpdb->query('COMMIT');
		} catch (Exception $e) {
			$wpdb->query('ROLLBACK');
			error_log("Error creating database table: " . $e->getMessage());
		}
	}

//===============================================================================================================
	public static function ssbhesabfa_create_delete_trigger() {
		global $wpdb;
		$table_name = $wpdb->prefix . "ssbhesabfa";
		$trigger_name = "prevent_delete";

		$max_retries = 3;
		$retry_count = 0;

		while ($retry_count < $max_retries) {
			try {
				$trigger_exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT TRIGGER_NAME 
                         FROM INFORMATION_SCHEMA.TRIGGERS 
                         WHERE TRIGGER_SCHEMA = %s 
                         AND EVENT_OBJECT_TABLE = %s 
                         AND TRIGGER_NAME = %s",
                        $wpdb->dbname,
						$table_name,
						$trigger_name
					)
				);

				if ($trigger_exists)
					return;

				$sql = "
                CREATE TRIGGER `$trigger_name`
                BEFORE DELETE ON `$table_name`
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'حذف رکورد از این جدول مجاز نیست.';
                END;
            ";

				$wpdb->query($sql);
				break; // Exit the loop if the query succeeds
			} catch (Exception $e) {
				$retry_count++;
				if ($retry_count >= $max_retries) {
					error_log("Error creating trigger after $max_retries attempts: " . $e->getMessage());
				} else {
					sleep(1); // Wait for 1 second before retrying
				}
			}
		}
	}
    //////////////////////////////////////////////////////////////////
	public static function ssbhesabfa_alter_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . "ssbhesabfa";

		try {
			// Use $wpdb->dbname instead of DB_NAME for better security
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
				"SELECT COLUMN_NAME 
                 FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s 
                 AND TABLE_NAME = %s 
                 AND COLUMN_NAME = %s",
					$wpdb->dbname,
					$table_name,
					'active'
				)
			);

			if ($column_exists) {
				return; // If column exists, exit early
			}

			// Start transaction
			$wpdb->query('START TRANSACTION');

			$sql = "ALTER TABLE `$table_name` ADD COLUMN `active` INT DEFAULT 1;";
			$result = $wpdb->query($sql);

			if ($result === false) {
				throw new Exception($wpdb->last_error);
			}

			// Commit transaction
			$wpdb->query('COMMIT');
		} catch (Exception $e) {
			// Rollback in case of failure
			$wpdb->query('ROLLBACK');
			error_log("Error altering table `$table_name`: " . $e->getMessage());
		}
	}
}
