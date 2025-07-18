<?php

include_once(plugin_dir_path(__DIR__) . 'admin/services/HesabfaLogService.php');
include_once(plugin_dir_path(__DIR__) . 'admin/services/HesabfaWpFaService.php');

/**
 * The admin-specific functionality of the plugin.
 *
 * @class      Ssbhesabfa_Admin
 * @version    2.2.3
 * @since      1.0.0
 * @package    ssbhesabfa
 * @subpackage ssbhesabfa/admin
 * @author     Sepehr Najafi <sepehrnm78@yahoo.com>
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 */
class Ssbhesabfa_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;
//=========================================================================================================================
    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->load_dependencies();
    }

    /**
     * Check DB ver on plugin update and do necessary actions
     *
     * @since    1.0.7
     */
//=========================================================================================================================
    public function ssbhesabfa_update_db_check()
    {
        $current_db_ver = get_site_option('ssbhesabfa_db_version');
        if ($current_db_ver === false || $current_db_ver < 1.1) {
            global $wpdb;
            $table_name = $wpdb->prefix . "ssbhesabfa";

            $sql = "ALTER TABLE $table_name
                    ADD `id_ps_attribute` INT(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `id_ps`;";

            if (!$wpdb->query($sql)) {
                HesabfaLogService::log(array("Cannot alter table $table_name. Current DB Version: $current_db_ver"));
            } else {
                update_option('ssbhesabfa_db_version', 1.1);
                HesabfaLogService::log(array("Alter table $table_name. Current DB Version: $current_db_ver"));
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabfa_check_db() {
        HesabfaLogService::writeLogStr("Check DB");
	    require_once plugin_dir_path(__DIR__) . 'includes/class-ssbhesabfa-activator.php';
	    Ssbhesabfa_Activator::activate();
    }
//=========================================================================================================================
    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ssbhesabfa_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ssbhesabfa_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        if( isset($_GET['page']) && str_contains($_GET['page'], "hesabfa") ){
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ssbhesabfa-admin.css?v=1', array(), $this->version, 'all');
            wp_enqueue_style('bootstrap_css', plugin_dir_url(__FILE__) . 'css/bootstrap.css', array(), $this->version, 'all');
        }
    }
//=========================================================================================================================
    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ssbhesabfa_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ssbhesabfa_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ssbhesabfa-admin.js', array('jquery'), $this->version, false);

        if( isset($_GET['page']) && str_contains($_GET['page'], "hesabfa") )
            wp_enqueue_script('bootstrap_js', plugin_dir_url(__FILE__) . 'js/bootstrap.bundle.min.js', array('jquery'), $this->version, false);
    }
//=========================================================================================================================
    private function load_dependencies()
    {
        /**
         * The class responsible for defining all actions that occur in the Dashboard
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ssbhesabfa-admin-display.php';

        /**
         * The class responsible for defining function for display Html element
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ssbhesabfa-html-output.php';

        /**
         * The class responsible for defining function for display general setting tab
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ssbhesabfa-admin-setting.php';

        /**
         * The class responsible for defining function for admin area
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ssbhesabfa-admin-functions.php';
    }

    /**
     * WC missing notice for the admin area.
     *
     * @since    1.0.0
     */
//=========================================================================================================================
    public function ssbhesabfa_missing_notice()
    {
        echo '<div class="error"><p>' . esc_html(sprintf(__('Hesabfa Plugin requires the %s to work!', 'ssbhesabfa'), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . __('WooCommerce', 'ssbhesabfa') . '</a>') . '</p></div>');
    }

    /**
     * Hesabfa Plugin Live mode notice for the admin area.
     *
     * @since    1.0.0
     */
//=========================================================================================================================
    public function ssbhesabfa_live_mode_notice()
    {
        echo '<div class="error"><p>' . esc_html__('Hesabfa Plugin need to connect to Hesabfa Accounting, Please check the API credential!', 'ssbhesabfa') . '</p></div>';
    }
//=========================================================================================================================
    public function ssbhesabfa_business_expired_notice()
    {
        echo '<div class="error"><p>' . esc_html__('Cannot connect to Hesabfa. Business expired.', 'ssbhesabfa') . '</p></div>';
    }

    /**
     * Missing hesabfa default currency notice for the admin area.
     *
     * @since    1.0.0
     */
//=========================================================================================================================
    public function ssbhesabfa_currency_notice()
    {
        echo '<div class="error"><p>' . esc_html__('Hesabfa Plugin cannot works! because WooCommerce currency in not match with Hesabfa.', 'ssbhesabfa') . '</p></div>';
    }
//=========================================================================================================================
    public function ssbhesabfa_general_notices() {
        if (!empty( $_REQUEST['submit_selected_orders_invoice_in_hesabfa'])) {
            if(!empty($_REQUEST['error_msg']) && $_REQUEST['error_msg'] == "select_max_10_items") {
	            printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html__('Error: Select maximum 10 orders. Due to some limitations in Hesabfa API, sending too many requests in one minute is not possible.', 'ssbhesabfa'));
            } else {
                $success_count = intval( $_REQUEST['success_count'] );
	            printf( '<div class="notice notice-success is-dismissible"><p>%s %d</p></div>', esc_html__('Selected orders invoices have been saved. Number of saved invoices: ', 'ssbhesabfa'), esc_html($success_count));
            }
        }
    }

//=========================================================================================================================
    /*
     * Action - Ajax 'export products' from Hesabfa/Export tab
     * @since	1.0.0
     */
    public function adminExportProductsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);
            $updateCount = wc_clean($_POST['updateCount']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->exportProducts($batch, $totalBatch, $total, $updateCount);

            if ($result['error']) {
                if ($updateCount === -1) {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productExportResult=false&error=-1');
                } else {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productExportResult=false');
                }
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productExportResult=true&processed=' . $result['updateCount']);
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    public function adminImportProductsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);
            $updateCount = wc_clean($_POST['updateCount']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->importProducts($batch, $totalBatch, $total, $updateCount);
            $import_count = $result['updateCount'];

            if ($result['error']) {
                if ($import_count === -1) {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productImportResult=false&error=-1');
                } else {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productImportResult=false');
                }
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productImportResult=true&processed=' . $import_count);
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'export products Opening Quantity' from Hesabfa/Export tab
     * @since	1.0.6
     */
    public function adminExportProductsOpeningQuantityCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->exportOpeningQuantity($batch, $totalBatch, $total);
            if ($result['error']) {
                if ($result['errorType'] == 'shareholderError') {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productOpeningQuantityExportResult=false&shareholderError=true');
                } else if ($result['errorType'] == 'noProduct') {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productOpeningQuantityExportResult=false&noProduct=true');
                } else {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productOpeningQuantityExportResult=false');
                }
            } else {
                if ($result["done"] == true)
                    update_option('ssbhesabfa_use_export_product_opening_quantity', true);
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&productOpeningQuantityExportResult=true');
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'export customers' from Hesabfa/Export tab
     * @since	1.0.0
     */
    public function adminExportCustomersCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);
            $updateCount = wc_clean($_POST['updateCount']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->exportCustomers($batch, $totalBatch, $total, $updateCount);

            if ($result["error"]) {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&customerExportResult=false');
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=export&customerExportResult=true&processed=' . $result["updateCount"]);
            }
            echo json_encode($result);

            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'Sync Changes' from Hesabfa/Sync tab
     * @since	1.0.0
     */
    public function adminSyncChangesCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            include(plugin_dir_path(__DIR__) . 'includes/class-ssbhesabfa-webhook.php');
            new Ssbhesabfa_Webhook();

            $redirect_url = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&changesSyncResult=true');
            echo esc_html($redirect_url);

            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'Sync Products' from Hesabfa/Sync tab
     * @since	1.0.0
     */
    public function adminSyncProductsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->syncProducts($batch, $totalBatch, $total);
            if ($result['error']) {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&productSyncResult=false');
                echo json_encode($result);
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&productSyncResult=true');
                echo json_encode($result);
            }
            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'Sync Orders from Hesabfa/Sync tab
     * @since	1.0.0
     */
    public function adminSyncOrdersCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);
            $updateCount = wc_clean($_POST['updateCount']);
            $from_date = wc_clean($_POST['date']);
            $end_date = wc_clean($_POST['endDate']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->syncOrders($from_date, $end_date, $batch, $totalBatch, $total, $updateCount);

            if (!$result['error'])
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&orderSyncResult=true&processed=' . $result["updateCount"]);
            else {
                switch ($result['error']) {
                    case 'fiscalYearError':
                        $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&orderSyncResult=false&fiscal=true');
                        break;
                    case 'inputDateError':
                        $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&orderSyncResult=false');
                        break;
                    default:
                        $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&orderSyncResult=true&processed=' . $updateCount);
                }
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    /*
    * Action - Ajax 'Update Products' from Hesabfa/Sync tab
    * @since	1.0.0
    */
    public function adminUpdateProductsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->updateProductsInHesabfaBasedOnStore($batch, $totalBatch, $total);

            if ($result['error']) {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&$productUpdateResult=false');
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&$productUpdateResult=true');
            }
            echo json_encode($result);
            die();
        }
    }

//=========================================================================================================================
    public function adminUpdateProductsWithFilterCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $offset = wc_clean($_POST['offset']);
            $rpp = wc_clean($_POST['rpp']);
            if(abs($rpp-$offset) <= 200) {
                $func = new Ssbhesabfa_Admin_Functions();
                $result = $func->updateProductsInHesabfaBasedOnStoreWithFilter($offset, $rpp);

                if ($result['error']) {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&$productUpdateWithFilterResult=false');
                } else {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&$productUpdateWithFilterResult=true');
                }
                echo json_encode($result);
                die();
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabfa-option&tab=sync&$productUpdateWithFilterResult=false');
                echo json_encode($result);
                die();
            }
        }
    }
//==========================================================================================================================
    public function adminSubmitInvoiceCallback()
    {
        HesabfaLogService::writeLogStr('Submit Invoice Manually');

        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $orderId = wc_clean($_POST['orderId']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->setOrder($orderId);
            if ($result)
                $func->setOrderPayment($orderId);

            echo json_encode($result);
            die();
        }
    }
//==========================================================================================================================
    public function adminRemoveInvoiceCallback()
    {
        HesabfaLogService::writeLogStr('Remove Invoice Manually');

        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $orderId = wc_clean($_POST['orderId']);

            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->deleteInvoiceLink($orderId);

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    public function adminSyncProductsManuallyCallback()
    {
        HesabfaLogService::writeLogStr('Sync Products Manually');

        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $page = wc_clean($_POST["page"]);
            $rpp = wc_clean($_POST["rpp"]);
            if (!$page) $page = 1;
            if (!$rpp) $rpp = 10;

            if (isset($_POST["data"])) {
                $data = wc_clean($_POST['data']);
                $data = str_replace('\\', '', $data);
                $data = json_decode($data, true);
            } else {
                $errors = true;
            }

            $func = new Ssbhesabfa_Admin_Functions();
            $res = $func->syncProductsManually($data);
            if ($res["result"] == true) {
                $redirect_url = admin_url("admin.php?page=hesabfa-sync-products-manually&p=$page&rpp=$rpp&result=true");
            } else {
                $data = implode(",", $res["data"]);
                $redirect_url = admin_url("admin.php?page=hesabfa-sync-products-manually&p=$page&rpp=$rpp&result=false&data=$data");
            }
            echo esc_html($redirect_url);

            die();
        }
    }
//=========================================================================================================================
    public function adminClearPluginDataCallback()
    {

        HesabfaLogService::writeLogStr('Clear Plugin Data');
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            //Call API
            $hesabfaApi = new Ssbhesabfa_Api();
            $result = $hesabfaApi->fixClearTags();
            if (!$result->Success) {

                HesabfaLogService::log(array("ssbhesabfa - Cannot clear tags. Error Message: " . (string)$result->ErrorMessage . ". Error Code: " . (string)$result->ErrorCode));
            }

            global $wpdb;
            $options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%ssbhesabfa%'");
            foreach ($options as $option) {
                delete_option($option->option_name);
            }

            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ssbhesabfa");

            die();
        }
    }
//=========================================================================================================================
    public function adminInstallPluginDataCallback()
    {
        HesabfaLogService::writeLogStr('Install Plugin Data');
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            // create table and settings
            require_once plugin_dir_path(__DIR__) . 'includes/class-ssbhesabfa-activator.php';
            Ssbhesabfa_Activator::activate();

            die();
        }
    }
//=========================================================================================================================
    public function admin_product_add_column( $columns ) {
        $hesabfaArray = array("hesabfa_code" => "کد در حسابفا");
        $columns = $hesabfaArray + $columns;
        return $columns;
    }
//=========================================================================================================================
    public function admin_product_export_rows($rows, $products) {
        $rowsArray = explode("\n", $rows);
        $exportRows = [];

        $reflection = new ReflectionClass($products);
        $property = $reflection->getProperty('row_data');
        $property->setAccessible(true);
        $productsArray = $property->getValue($products);
        $matchingArray = [];

        if (!empty($productsArray)) {
            foreach ($productsArray as $product) {
                if (is_array($product) && isset($product['id'])) {
                    $wpFaService = new HesabfaWpFaService();

                    if ($product["type"] == "variation") {
                        $wpFa = $wpFaService->getWpFaSearch('', $product['id'], '', "product");
                    } elseif ($product["type"] == "simple" || $product["type"] == "variable") {
                        $wpFa = $wpFaService->getWpFaSearch($product['id'], 0, '', "product");
                    }

                    if (is_array($wpFa)) {
                        foreach ($wpFa as $item) {
                            if ($item->idWpAttribute != 0) {
                                $matchingArray[$item->idWpAttribute] = $item->idHesabfa;
                            } else {
                                $matchingArray[$item->idWp] = $item->idHesabfa;
                            }
                        }
                    }
                }
            }
        }

        foreach ($rowsArray as $row) {
            if (empty(trim($row))) {
                continue;
            }
            $columns = str_getcsv($row);
            $inserted = false;

            if (isset($columns[1])) {
                foreach ($matchingArray as $wpId => $hesabfaId) {
                    if ($columns[1] == $wpId && !$inserted) {
                        $columns[0] = $hesabfaId;
                        $inserted = true;
                        break;
                    }
                }
            }

            if (!$inserted) {
                $columns[0] = "کد ندارد";
            }

            $exportRows[] = implode(",", $columns);
        }

        return implode("\n", $exportRows);
    }
//=========================================================================================================================
    public function ssbhesabfa_init_internal()
    {
        add_rewrite_rule('ssbhesabfa-webhook.php$', 'index.php?ssbhesabfa_webhook=1', 'top');

        require_once plugin_dir_path(__DIR__) . 'includes/class-ssbhesabfa-activator.php';
        Ssbhesabfa_Activator::activate();

        if(get_option("ssbhesabfa_check_for_sync") == 1) {
            $this->checkForSyncChanges();
        }
    }
//=========================================================================================================================
    private function checkForSyncChanges()
    {
        // Get the last sync time
        $syncChangesLastDate = get_option('ssbhesabfa_sync_changes_last_date');
        if (!$syncChangesLastDate) {
            // Initialize the last sync date if not set
            $syncChangesLastDate = time(); // Use PHP's time() function
            add_option('ssbhesabfa_sync_changes_last_date', $syncChangesLastDate);
        }

        // Get the current time
        $nowDateTime = time(); // Current UNIX timestamp

        // Time interval (default: 4 minutes)
        $timeInterval = 4;
        $extraSettingTimeInterval = get_option("ssbhesabfa_check_for_sync_select");

        if ($extraSettingTimeInterval && $extraSettingTimeInterval != '-1' && $extraSettingTimeInterval != '0') {
            $timeInterval = (int)$extraSettingTimeInterval;
        }

        // Calculate the time difference in minutes
        $diffMinutes = ($nowDateTime - (int)$syncChangesLastDate) / 60;

        // Check if it's time to sync
        if ($diffMinutes >= $timeInterval) {
            $this->performSync();
        }
    }

    private function performSync()
    {
        HesabfaLogService::writeLogStr('Sync Changes Automatically');
        update_option('ssbhesabfa_sync_changes_last_date', time()); // Update last sync time
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ssbhesabfa-webhook.php';
        new Ssbhesabfa_Webhook();
    }
//=========================================================================================================================
    public function ssbhesabfa_query_vars($query_vars)
    {
        $query_vars[] = 'ssbhesabfa_webhook';
        return $query_vars;
    }
//=========================================================================================================================
    public function custom_hesabfa_column_order_list($columns)
    {
        $reordered_columns = array();

        foreach ($columns as $key => $column) {
            $reordered_columns[$key] = $column;
            if ($key == 'order_status') {
                // Inserting after "Status" column
                $reordered_columns['hesabfa-column-invoice-number'] = __('Invoice in Hesabfa', 'ssbhesabfa');
                $reordered_columns['hesabfa-column-remove-invoice'] = __('Remove Invoice', 'ssbhesabfa');
                $reordered_columns['hesabfa-column-submit-invoice'] = __('Submit Invoice', 'ssbhesabfa');
            }
        }
        return $reordered_columns;
    }
//=========================================================================================================================
    public function custom_orders_list_column_content($column, $post_id)
    {
        global $wpdb;
        $order = wc_get_order($post_id);

        if (get_option('woocommerce_custom_orders_table_enabled') == 'yes') {
            switch ($column) {
                case 'hesabfa-column-invoice-number':
                    $table_name = $wpdb->prefix . 'ssbhesabfa';
                    $row = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT id_hesabfa FROM $table_name WHERE id_ps = %d AND obj_type = 'order' AND active = '1'",
                            $order->get_id()
                        )
                    );

                    if (!empty($row)) {
                        echo '<mark class="order-status"><span>' . esc_html($row->id_hesabfa) . '</span></mark>';
                    } else {
                        echo '<small></small>';
                    }
                    break;

                case 'hesabfa-column-submit-invoice':
                    echo '<a role="button" class="button btn-submit-invoice" ';
                    echo 'data-order-id="' . esc_attr($order->get_id()) . '">';
                    echo esc_html__('Submit Invoice', 'ssbhesabfa');
                    echo '</a>';
                    break;

                case 'hesabfa-column-remove-invoice':
                    echo '<a role="button" class="button btn-remove-invoice" ';
                    echo 'data-order-id="' . esc_attr($order->get_id()) . '">';
                    echo esc_html__('Remove Invoice', 'ssbhesabfa');
                    echo '</a>';
                    break;
            }
        } else {
            switch ($column) {
                case 'hesabfa-column-invoice-number':
                    $row = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT id_hesabfa FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE id_ps = %d AND obj_type = 'order' AND active = '1'",
                            $order->get_id()
                        )
                    );

                    if (!empty($row)) {
                        echo '<mark class="order-status"><span>' . esc_html($row->id_hesabfa) . '</span></mark>';
                    } else {
                        echo '<small></small>';
                    }
                    break;

                case 'hesabfa-column-submit-invoice':
                    echo '<a role="button" class="button btn-submit-invoice" ';
                    echo 'data-order-id="' . esc_attr($order->get_id()) . '">';
                    echo esc_html__('Submit Invoice', 'ssbhesabfa');
                    echo '</a>';
                    break;

                case 'hesabfa-column-remove-invoice':
                    echo '<a role="button" class="button btn-remove-invoice" ';
                    echo 'data-order-id="' . esc_attr($order->get_id()) . '">';
                    echo esc_html__('Remove Invoice', 'ssbhesabfa');
                    echo '</a>';
                    break;
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabfa_parse_request(&$wp)
    {
        if (array_key_exists('ssbhesabfa_webhook', $wp->query_vars)) {
            include(plugin_dir_path(__DIR__) . 'includes/ssbhesabfa-webhook.php');
            exit();
        }
    }
//=========================================================================================================================
    public function custom_orders_list_bulk_action($actions) {
        $actions['submit_invoice_in_hesabfa'] = __('Submit Invoice in Hesabfa', 'ssbhesabfa');
        return $actions;
    }
//=========================================================================================================================
    public function custom_orders_list_bulk_action_run($redirect_to, $action, $post_ids) {
        if ( $action !== 'submit_invoice_in_hesabfa' )
            return $redirect_to; // Exit

        HesabfaLogService::writeLogStr("Submit selected orders invoice");

        if(count($post_ids) > 10)
            return $redirect_to = add_query_arg( array(
                'submit_selected_orders_invoice_in_hesabfa' => '1',
                'error_msg' => 'select_max_10_items'
            ), $redirect_to );

        $success_count = 0;
        $func = new Ssbhesabfa_Admin_Functions();

        try {
            foreach ($post_ids as $orderId) {
                $result = $func->setOrder( $orderId );
                if ( $result ) {
                    $success_count ++;
                    $func->setOrderPayment( $orderId );
                }
            }
        } catch(Exception $e) {
            HesabfaLogService::log(array($e->getMessage()));
        }

        return $redirect_to = add_query_arg( array(
            'submit_selected_orders_invoice_in_hesabfa' => '1',
            'success_count' => $success_count,
            'error_msg' => '0'
        ), $redirect_to );
    }
//=========================================================================================================================
    //Hooks
    //Contact
    public function ssbhesabfa_hook_edit_user(WP_User $user)
    {
	    $deactivate_user_btn_style = "
            background: #DC3545;
            color: white;
            border: none;
            padding: 0.4rem;
            border-radius: 5px;
        ";
        $wpFaService = new HesabfaWpFaService();
        $code = isset($user) ? $wpFaService->getCustomerCodeByWpId($user->ID) : '';
        ?>
        <hr>
        <table class="form-table">
            <tr>
                <th><label for="user_hesabfa_code"
                           class="text-info"><?php echo esc_html__('Contact Code in Hesabfa', 'ssbhesabfa'); ?></label>
                </th>
                <td>
                    <input
                        type="text"
                        value="<?php if($code != null) echo esc_html($code); ?>"
                        name="user_hesabfa_code"
                        id="user_hesabfa_code"
                        class="regular-text"
                    >
                    <input
                        type="submit"
                        name="user_deactivate_btn"
                        id="user_deactivate_btn"
                        class="button"
                        style="<?php echo esc_attr($deactivate_user_btn_style); ?>"
                        value="حذف لینک شخص"
                    >
                    <br/>
                    <div class="description mt-2">
                        <?php echo esc_html__("The contact code of this user in Hesabfa, if you want to map this user "
                            . "to a contact in Hesabfa, enter the Contact code.", 'ssbhesabfa'); ?>
                    </div>
                </td>
            </tr>
        </table>
        <hr>
        <?php
    }
//=========================================================================================================================
	public static function adminDeleteContactLink($user_hesabfa_code = '')
	{
        if($user_hesabfa_code == '')
            return;
        $wpFaService = new HesabfaWpFaService();
        $wpFa = $wpFaService->getWpFaByHesabfaId('customer', $user_hesabfa_code, 1);
        if ($wpFa) {
            $wpFaService->updateActive($wpFa, 0);
            HesabfaLogService::writeLogStr("حذف ارتباط شخص. کد شخص: " . $user_hesabfa_code);
        }
	}
//=========================================================================================================================
    public function ssbhesabfa_hook_user_register($id_customer)
    {
        $user_hesabfa_code = '';
        if(isset($_REQUEST['user_hesabfa_code']))
            $user_hesabfa_code = $_REQUEST['user_hesabfa_code'];

	    if(isset($_REQUEST['user_deactivate_btn'])) {
		    $this->adminDeleteContactLink($user_hesabfa_code);
            return;
	    }

        if (isset($user_hesabfa_code) && $user_hesabfa_code !== "" && $user_hesabfa_code != null) {
            $wpFaService = new HesabfaWpFaService();
            $wpFaOld = $wpFaService->getWpFaByHesabfaId('customer', $user_hesabfa_code);
            $wpFa = $wpFaService->getWpFa('customer', $id_customer);
            HesabfaLogService::log(array("hook user register for: " . $id_customer));

            if (!$wpFaOld || !$wpFa || $wpFaOld->id !== $wpFa->id) {
                if ($wpFaOld)
                    $wpFaService->updateActive($wpFaOld, 0);
//                    $wpFaService->delete($wpFaOld);

                if ($wpFa) {
                    $wpFa->idHesabfa = $user_hesabfa_code;
                    $wpFaService->update($wpFa);
                } else {
                    $wpFa = new WpFa();
                    $wpFa->objType = 'customer';
                    $wpFa->idWp = $id_customer;
                    $wpFa->idHesabfa = intval($user_hesabfa_code);
                    $wpFaService->save($wpFa);
                }
            }
        }

        $function = new Ssbhesabfa_Admin_Functions();

        if(get_option('ssbhesabfa_contact_automatically_save_in_hesabfa') == 'yes')
            $function->setContact($id_customer);
    }
//=========================================================================================================================
    public function ssbhesabfa_hook_delete_user($id_customer)
    {
        $wpFaService = new HesabfaWpFaService();
        $id_obj = $wpFaService->getWpFaId('customer', $id_customer);
        if ($id_obj != false) {
            global $wpdb;
            $row = $wpdb->get_row("SELECT `id_hesabfa` FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id` = $id_obj AND `obj_type` = 'customer'");

            if (is_object($row)) {
                $hesabfaApi = new Ssbhesabfa_Api();
                $hesabfaApi->contactDelete($row->id_hesabfa);
            }

            global $wpdb;
            $wpdb->delete($wpdb->prefix . 'ssbhesabfa', array('id_ps' => $id_customer));


            HesabfaLogService::log(array("Customer deleted. Customer ID: $id_customer"));
        }
    }
//=========================================================================================================================
    //Invoice
    public function ssbhesabfa_hook_order_status_change($id_order, $from, $to)
    {
        HesabfaLogService::writeLogStr("Order Status Hook");
        $function = new Ssbhesabfa_Admin_Functions();

        foreach (get_option('ssbhesabfa_invoice_status') as $status) {

            HesabfaLogService::writeLogStr("status: $status");

            if ($status == $to) {
                HesabfaLogService::writeLogStr("to: $to");
                $orderResult = $function->setOrder($id_order);
                if ($orderResult) {
                    // set payment
                    foreach (get_option('ssbhesabfa_payment_status') as $statusPayment) {
                        if ($statusPayment == $to)
                            $function->setOrderPayment($id_order);
                    }
                }
            }
        }

        $values = get_option('ssbhesabfa_invoice_return_status');
        if(is_array($values) || is_object($values)) {
            foreach ($values as $status) {
                if ($status == $to)
                    $function->setOrder($id_order, 2, $function->getInvoiceCodeByOrderId($id_order));
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabfa_hook_new_order($id_order, $order)
    {
        HesabfaLogService::writeLogStr("New Order Hook");
        $function = new Ssbhesabfa_Admin_Functions();
        $orderStatus = wc_get_order($id_order)->get_status();
        $orderItems = $order->get_items();

        foreach (get_option('ssbhesabfa_invoice_status') as $status) {

            HesabfaLogService::writeLogStr("status: $status");

            if ($status == $orderStatus) {
                $orderResult = $function->setOrder($id_order, 0, null, $orderItems);
                if ($orderResult) {
                    // set payment
                    foreach (get_option('ssbhesabfa_payment_status') as $statusPayment) {
                        if ($statusPayment == $orderStatus)
                            $function->setOrderPayment($id_order);
                    }
                }
            }
        }

        HesabfaLogService::log(array($orderStatus));

        $values = get_option('ssbhesabfa_invoice_return_status');
        if(is_array($values) || is_object($values)) {
            foreach ($values as $status) {
                if ($status == $orderStatus)
                    $function->setOrder($id_order, 2, $function->getInvoiceCodeByOrderId($id_order), $orderItems);
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabfa_hook_payment_confirmation($id_order, $from, $to)
    {
        foreach (get_option('ssbhesabfa_payment_status') as $status) {
            if ($status == $to) {
                $function = new Ssbhesabfa_Admin_Functions();
                $function->setOrderPayment($id_order);
            }
        }
    }

    //Item
    private $call_time = 1;
//=========================================================================================================================
    public function ssbhesabfa_hook_new_product($id_product)
    {
//        if (get_option("ssbhesabfa_inside_product_edit", 0) === 1)
//            return;

        if ($this->call_time === 1) {
            $this->call_time++;
            return;
        } else {
            $this->call_time = 1;
        }

        if (get_option("ssbhesabfa_do_not_submit_product_automatically", "no") === "yes") return;
        $function = new Ssbhesabfa_Admin_Functions();
        $function->setItems(array($id_product));
    }
//=========================================================================================================================
    public function ssbhesabfa_hook_save_product_variation($id_attribute)
    {

        HesabfaLogService::writeLogStr("ssbhesabfa_hook_save_product_variation");

        if (get_option("ssbhesabfa_do_not_submit_product_automatically", "no") === "yes" || get_option("ssbhesabfa_do_not_submit_product_automatically", "no") == "1") {
            //change hesabfa item code
            $variable_field_id = "ssbhesabfa_hesabfa_item_code_" . $id_attribute;
            $code = sanitize_text_field($_POST[$variable_field_id]);
            $id_product = sanitize_text_field($_POST['product_id']);

            if ($code === "")
                return;

            if (isset($code)) {
                global $wpdb;
//                $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id_hesabfa` = " . $code . " AND `obj_type` = 'product'");

                $table_name = $wpdb->prefix . 'ssbhesabfa';
                $sql = $wpdb->prepare(
                    "SELECT * FROM `$table_name` WHERE `id_hesabfa` = %d AND `obj_type` = 'product'",
                    $code
                );

                $row = $wpdb->get_row($sql);

                if (is_object($row)) {
                    if ($row->id_ps == $id_product && $row->id_ps_attribute == $id_attribute) {
                        return false;
                    }

                    echo '<div class="error"><p>' . esc_html__('The new Item code already used for another Item', 'ssbhesabfa') . '</p></div>';

                    HesabfaLogService::log(array("The new Item code already used for another Item. Product ID: $id_product"));
                } else {
                    //$row2 = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id_ps` = $id_product AND `obj_type` = 'product' AND `id_ps_attribute` = $id_attribute");

                    $sql = $wpdb->prepare(
                        "SELECT * FROM `{$wpdb->prefix}ssbhesabfa` WHERE `id_ps` = %d AND `obj_type` = 'product' AND `id_ps_attribute` = %d",
                        $id_product, $id_attribute
                    );

                    $row2 = $wpdb->get_row($sql);

                    if (is_object($row2)) {
                        $wpdb->update($wpdb->prefix . 'ssbhesabfa', array(
                            'id_hesabfa' => (int)$code,
                        ), array(
                            'id_ps' => $id_product,
                            'id_ps_attribute' => $id_attribute,
                            'obj_type' => 'product',
                        ));
                    } else if ((int)$code !== 0) {
                        $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array(
                            'id_hesabfa' => (int)$code,
                            'id_ps' => (int)$id_product,
                            'id_ps_attribute' => $id_attribute,
                            'obj_type' => 'product',
                        ));
                    }
                }
            }

            //add attribute if not exists
            $func = new Ssbhesabfa_Admin_Functions();
            $wpFaService = new HesabfaWpFaService();
            $code = $wpFaService->getProductCodeByWpId($id_product, $id_attribute);
            if ($code == null) {
                $func->setItems(array($id_product));
            }
        }
    }
//=========================================================================================================================
    //ToDo: check why base product is not deleted
    public function ssbhesabfa_hook_delete_product($id_product)
    {

        HesabfaLogService::writeLogStr("Product Delete Hook");

        $func = new Ssbhesabfa_Admin_Functions();
        $wpFaService = new HesabfaWpFaService();

        $hesabfaApi = new Ssbhesabfa_Api();
        global $wpdb;

        $variations = $func->getProductVariations($id_product);
        if ($variations != false) {
            foreach ($variations as $variation) {
                $id_attribute = $variation->get_id();
                $code = $wpFaService->getProductCodeByWpId($id_product, $id_attribute);
                if ($code != false) {
                    $hesabfaApi->itemDelete($code);
                    $wpdb->delete($wpdb->prefix . 'ssbhesabfa', array('id_hesabfa' => $code, 'obj_type' => 'product'));

                    HesabfaLogService::log(array("Product variation deleted. Product ID: $id_product-$id_attribute"));
                }
            }
        }

        $code = $wpFaService->getProductCodeByWpId($id_product);

        if ($code != false) {
            $hesabfaApi->itemDelete($code);
            $wpdb->delete($wpdb->prefix . 'ssbhesabfa', array('id_hesabfa' => $code, 'obj_type' => 'product'));

            HesabfaLogService::log(array("Product deleted. Product ID: $id_product"));
        }
    }
//=========================================================================================================================
    public function ssbhesabfa_hook_delete_product_variation($id_attribute)
    {
//        $func = new Ssbhesabfa_Admin_Functions();

        $hesabfaApi = new Ssbhesabfa_Api();
        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id_ps_attribute` = $id_attribute AND `obj_type` = 'product'");

        if (is_object($row)) {
            $hesabfaApi->itemDelete($row->id_hesabfa);

            $wpdb->delete($wpdb->prefix . 'ssbhesabfa', array('id' => $row->id));

            HesabfaLogService::log(array("Product variation deleted. Product ID: $row->id_ps-$id_attribute"));
        }
    }
//=========================================================================================================================
    public function ssbhesabfa_hook_product_options_general_product_data()
    {
        $wpFaService = new HesabfaWpFaService();
        $value = isset($_GET['post']) ? $wpFaService->getProductCodeByWpId($_GET['post']) : '';
        $args = array(
            'id' => 'ssbhesabfa_hesabfa_item_code_0',
            'label' => __('Hesabfa base item code', 'ssbhesabfa'),
            'desc_tip' => true,
            'description' => __('The base Item code of this product in Hesabfa, if you want to map this product to another item in Hesabfa, enter the new Item code.', 'ssbhesabfa'),
            'value' => $value,
            'type' => 'number',
        );
        woocommerce_wp_text_input($args);
    }
//=========================================================================================================================
    public function ssbhesabfa_hook_process_product_meta($post_id)
    {
        $itemCode = isset($_POST['ssbhesabfa_hesabfa_item_code_0']) ? sanitize_text_field($_POST['ssbhesabfa_hesabfa_item_code_0']) : '';

        if ($itemCode === "")
            return;

        if (isset($itemCode)) {
            global $wpdb;
//            $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id_hesabfa` = " . $itemCode . " AND `obj_type` = 'product'");

            $table_name = $wpdb->prefix . 'ssbhesabfa';
            $sql = $wpdb->prepare(
                "SELECT * FROM `$table_name` WHERE `id_hesabfa` = %d AND `obj_type` = 'product'",
                $itemCode
            );
            $row = $wpdb->get_row($sql);

            if (is_object($row)) {
                //ToDo: show error to customer in BO
                echo '<div class="error"><p>' . esc_html__('The new Item code already used for another Item', 'ssbhesabfa') . '</p></div>';

                HesabfaLogService::log(array("The new Item code already used for another Item. Product ID: $post_id"));
            } else {
                $row2 = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id_ps` = $post_id AND `obj_type` = 'product' AND `id_ps_attribute` = 0");
                if (is_object($row2)) {
                    $wpdb->update($wpdb->prefix . 'ssbhesabfa', array(
                        'id_hesabfa' => (int)$itemCode,
                    ), array(
                        'id_ps' => $post_id,
                        'id_ps_attribute' => 0,
                        'obj_type' => 'product',
                    ));
                } else if ((int)$itemCode !== 0) {
                    $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array(
                        'id_hesabfa' => (int)$itemCode,
                        'id_ps' => (int)$post_id,
                        'id_ps_attribute' => 0,
                        'obj_type' => 'product',
                    ));
                }
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabfa_hook_product_after_variable_attributes($loop, $variation_data, $variation)
    {
        $wpFaService = new HesabfaWpFaService();
        $value = isset($_POST['product_id']) ? $wpFaService->getProductCodeByWpId($_POST['product_id'], $variation->ID) : '';
        $args = array(
            'id' => 'ssbhesabfa_hesabfa_item_code_' . $variation->ID,
            'label' => __('Hesabfa variable item code', 'ssbhesabfa'),
            'desc_tip' => true,
            'description' => __('The variable Item code of this product variable in Hesabfa, if you want to map this product to another item in Hesabfa, enter the new Item code.', 'ssbhesabfa'),
            'value' => $value,
        );
        woocommerce_wp_text_input($args);
    }
//=========================================================================================================================
    /*
    * Action - Ajax 'clean log file' from Hesabfa/Log tab
    * @since	1.0.0
    */
    public function adminCleanLogFileCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $func = new Ssbhesabfa_Admin_Functions();
            $result = $func->cleanLogFile();

            if ($result) {
                $redirect_url = admin_url('admin.php?page=ssbhesabfa-option&tab=log&cleanLogResult=true');
            } else {
                $redirect_url = admin_url('admin.php?page=ssbhesabfa-option&tab=log&cleanLogResult=false');
            }
            echo esc_html($redirect_url);

            die();
        }
    }
//=========================================================================================================================
    // custom data tab in edit product page in admin panel
    function add_hesabfa_product_data_tab($product_data_tabs)
    {
        $product_data_tabs['hesabfa'] = array(
            'label' => __('Hesabfa', 'ssbhesabfa'),
            'target' => 'panel_product_data_hesabfa',
        );
        return $product_data_tabs;
    }
//=========================================================================================================================
    function add_hesabfa_product_data_fields()
    {
        global $woocommerce, $post, $product;

        $funcs = new Ssbhesabfa_Admin_Functions();
        $items = array();
        $id_product = $post->ID;
//        $product = new WC_Product($id_product);
        $product = wc_get_product($id_product);

        if ($product->get_status() === "auto-draft") {
            ?>
            <div id="panel_product_data_hesabfa" class="panel woocommerce_options_panel"
                 data-product-id="<?php echo esc_attr($id_product) ?>">
                هنوز محصول ذخیره نشده است.
                <br>
                پس از ذخیره محصول، در این قسمت می توانید ارتباط محصول و متغیرهای آن با حسابفا
                را مدیریت کنید.
            </div>
            <?php
            return;
        }
        global $items;
        $items[] = ssbhesabfaItemService::mapProduct($product, $id_product, false);
        $items[0]["Quantity"] = $product->get_stock_quantity();
        $items[0]["Id"] = $id_product;
        $items[0]["RegularPrice"] = $product->get_regular_price();
        $i = 1;

        $variations = $funcs->getProductVariations($id_product);
        if ($variations) {
            foreach ($variations as $variation) {
                $items[] = ssbhesabfaItemService::mapProductVariation($product, $variation, $id_product, false);
                $items[$i]["Quantity"] = $variation->get_stock_quantity();
                $items[$i]["Id"] = $variation->get_id();
                $items[$i]["RegularPrice"] = $variation->get_regular_price();
                $i++;
            }
        }

        ?>
        <div id="panel_product_data_hesabfa" class="panel woocommerce_options_panel"
             data-product-id="<?php echo esc_attr($id_product) ?>">
            <table class="table table-striped">
                <tr class="small fw-bold">
                    <td>نام کالا</td>
                    <td>کد در حسابفا</td>
                    <td>ذخیره کد</td>
                    <td>حذف ارتباط</td>
                    <td>بروزرسانی قیمت و موجودی</td>
                    <td>قیمت</td>
                    <td>موجودی</td>
                </tr>
                <?php
                foreach ($items as $item) {
                    ?>
                    <tr>
                        <td><?php echo esc_html($item["Name"]); ?></td>
                        <td><input type="text" value="<?php echo esc_attr($item["Code"]); ?>"
                                   id="hesabfa-item-<?php echo esc_attr($item["Id"]); ?>" style="width: 75px;"
                                   class="hesabfa-item-code" data-id="<?php echo esc_attr($item["Id"]); ?>"></td>
                        <td><input type="button" value="ذخیره" data-id="<?php echo esc_attr($item["Id"]); ?>"
                                   class="button hesabfa-item-save"></td>
                        <td><input type="button" value="حذف ارتباط" data-id="<?php echo esc_attr($item["Id"]); ?>"
                                   class="button hesabfa-item-delete-link"></td>
                        <td><input type="button" value="بروزرسانی" data-id="<?php echo esc_attr($item["Id"]); ?>"
                                   class="button button-primary hesabfa-item-update"></td>
                        <td id="hesabfa-item-price-<?php echo esc_attr($item["Id"]) ?>"><?php if(isset($item["SellPrice"])) echo esc_html($item["SellPrice"]); else echo  esc_html($item["RegularPrice"]); ?></td>
                        <td id="hesabfa-item-quantity-<?php echo esc_attr($item["Id"]) ?>"><?php echo esc_html($item["Quantity"]); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <input type="button" value="ذخیره همه" id="hesabfa-item-save-all" class="button">
            <input type="button" value="حذف ارتباط همه" id="hesabfa-item-delete-link-all" class="button">
            <input type="button" value="بروزرسانی همه" id="hesabfa-item-update-all" class="button button-primary">

        </div>
        <?php
    }
//=========================================================================================================================
    function admin_products_hesabfaId_column( $columns ){
        echo '<style>
        #hesabfaID {
            width: 5vw;
            color: #2271b1;
        }
        </style>';
        return array_slice($columns, 0, 3, true) + array('hesabfaID' => 'کد حسابفا') + array_slice($columns, 3, count($columns) - 3, true);
    }
//======
    function admin_products_hesabfaId_column_content( $column ){
        $funcs = new Ssbhesabfa_Admin_Functions();
        $items = array();
        $id_product = get_the_ID();
//        $product = new WC_Product($id_product);
        $product = wc_get_product($id_product);

        $items[] = ssbhesabfaItemService::mapProduct($product, $id_product, false);
        $i = 1;

        $variations = $funcs->getProductVariations($id_product);
        if ($variations) {
            foreach ($variations as $variation) {
                $items[] = ssbhesabfaItemService::mapProductVariation($product, $variation, $id_product, false);
                $i++;
            }
        }

        echo '<div>';
        foreach ($items as $item) {
            if ( $column == 'hesabfaID' ) {
                $hesabfaId = $item["Code"];
                echo "<span class='button button-secondary'>" . esc_html($hesabfaId) . " " . "</span>";
            }
        }
        echo '</div>';
    }
//=========================================================================================================================
    function adminChangeProductCodeCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $productId = (int)wc_clean($_POST['productId']);
            $attributeId = (int)wc_clean($_POST['attributeId']);
            if ($productId == $attributeId) $attributeId = 0;
            $code = (int)wc_clean($_POST['code']);
            $result = array();

            if (!$code) {
                $result["error"] = true;
                $result["message"] = "کد کالا وارد نشده است.";
                echo json_encode($result);
                die();
                return;
            }

            $wpFaService = new HesabfaWpFaService();
            $wpFa = $wpFaService->getWpFaByHesabfaId('product', $code);
            if ($wpFa && $wpFa->active == 1) {
                $result["error"] = true;
                $result["message"] = "این کد به کالای دیگری متصل است. \n" . $wpFa->idWp . " - " . $wpFa->idWpAttribute;
                echo json_encode($result);
                die();
                return;
            }

            $api = new Ssbhesabfa_Api();
            $response = $api->itemGet($code);
            if (!$response->Success) {
                $result["error"] = true;
                $result["message"] = "کالایی با کد وارد شده در حسابفا پیدا نشد.";
                echo json_encode($result);
                die();
                return;
            }

            $wpFa = $wpFaService->getWpFa('product', $productId, $attributeId);
            if ($wpFa) {
                $wpFaService->updateActive($wpFa, false);
                $wpFa->idHesabfa = $code;
            } else {
                $wpFa = new WpFa();
                $wpFa->idHesabfa = $code;
                $wpFa->idWp = $productId;
                $wpFa->idWpAttribute = $attributeId;
                $wpFa->objType = 'product';
            }

            $wpFaService->save($wpFa);

            $result["error"] = false;
            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminDeleteProductLinkCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $productId = wc_clean($_POST['productId']);
            $attributeId = wc_clean($_POST['attributeId']);
            if ($productId == $attributeId) $attributeId = 0;
            $result = array();

            $wpFaService = new HesabfaWpFaService();
            $wpFa = $wpFaService->getWpFa('product', $productId, $attributeId);
            if ($wpFa) {
//                $wpFaService->delete($wpFa);
                $wpFaService->updateActive($wpFa, 0);
                HesabfaLogService::writeLogStr("حذف ارتباط کالا. کد کالا: " . $productId . " - ". "کد متغیر:". $attributeId);
            }

            $result["error"] = false;
            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminUpdateProductCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            if (get_option('ssbhesabfa_item_update_price', 'no') == 'no' &&
                get_option('ssbhesabfa_item_update_quantity', 'no') == 'no') {
                $result["error"] = true;
                $result["message"] = "خطا: در تنظیمات افزونه، گزینه های بروزرسانی قیمت و موجودی محصول بر اساس حسابفا فعال نیستند.";
                echo json_encode($result);
                die();
            }

            $productId = wc_clean($_POST['productId']);
            $attributeId = wc_clean($_POST['attributeId']);

            if (get_option('ssbhesabfa_item_update_quantity', 'no') == 'yes')
                update_post_meta($attributeId, '_manage_stock', 'yes');

            if ($productId == $attributeId) $attributeId = 0;
            $result = array();

            $wpFaService = new HesabfaWpFaService();
            $wpFa = $wpFaService->getWpFa('product', $productId, $attributeId);
            if ($wpFa) {

                $api = new Ssbhesabfa_Api();
                $warehouse = get_option('ssbhesabfa_item_update_quantity_based_on', "-1");
                if ($warehouse == "-1")
                    $response = $api->itemGet($wpFa->idHesabfa);
                else {
                    $response = $api->itemGetQuantity($warehouse, array($wpFa->idHesabfa));
                }

                if ($response->Success) {
                    $item = $warehouse == "-1" ? $response->Result : $response->Result[0];
                    $newProps = Ssbhesabfa_Admin_Functions::setItemChanges($item);
                    $result["error"] = false;
                    $result["newPrice"] = $newProps["newPrice"];
                    $result["newQuantity"] = $newProps["newQuantity"];
                } else {
                    $result["error"] = true;
                    $result["message"] = "کالا در حسابفا پیدا نشد.";
                }
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminChangeProductsCodeCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $wpFaService = new HesabfaWpFaService();

            $productId = (int)wc_clean($_POST['productId']);
            $itemsData = wc_clean($_POST['itemsData'], true);
            $result = array();
            $codes = [];

            foreach ($itemsData as $itemData) {
                $attributeId = (int)$itemData["attributeId"];
                $code = (int)$itemData["code"];
                if ($productId == $attributeId) $attributeId = 0;
                $codes[] = str_pad($code, 6, "0", STR_PAD_LEFT);

                if (!$code) {
                    $result["error"] = true;
                    $result["message"] = "کد کالا وارد نشده است.";
                    echo json_encode($result);
                    die();
                    return;
                }

                $wpFa = $wpFaService->getWpFaByHesabfaId('product', $code);
                $wpFa2 = $wpFaService->getWpFa('product', $productId, $attributeId);
                if ($wpFa && $wpFa2 && $wpFa->id != $wpFa2->id) {
                    $result["error"] = true;
                    $result["message"] = "این کد ($code) به کالای دیگری متصل است. \n" . $wpFa->idWp . " - " . $wpFa->idWpAttribute;
                    echo json_encode($result);
                    die();
                    return;
                }
            }

            $api = new Ssbhesabfa_Api();
            $filters = array(array("Property" => "Code", "Operator" => "in", "Value" => $codes));
            $response = $api->itemGetItems(array('Filters' => $filters));
            if ($response->Success) {
                $items = $response->Result->List;
                foreach ($codes as $code) {
                    $found = false;
                    foreach ($items as $item) {
                        if ($item->Code == $code)
                            $found = true;
                    }
                    if (!$found) {
                        $result["error"] = true;
                        $result["message"] = "کالایی با کد $code در حسابفا پیدا نشد.";
                        echo json_encode($result);
                        die();
                        return;
                    }
                }
            } else {
                $result["error"] = true;
                $result["message"] = "کالایی با کد وارد شده در حسابفا پیدا نشد.";
                echo json_encode($result);
                die();
                return;
            }


            foreach ($itemsData as $itemData) {
                $attributeId = (int)$itemData["attributeId"];
                $code = (int)$itemData["code"];
                if ($productId == $attributeId) $attributeId = 0;

                $wpFa = $wpFaService->getWpFa('product', $productId, $attributeId);
                if ($wpFa) {
                    //$wpFaService->updateActive($wpFa, false);
                    $wpFa->idHesabfa = $code;
                    $wpFaService->update($wpFa);
                } else {
                    $wpFa = new WpFa();
                    $wpFa->idHesabfa = $code;
                    $wpFa->idWp = $productId;
                    $wpFa->idWpAttribute = $attributeId;
                    $wpFa->objType = 'product';
                    $wpFaService->save($wpFa);
                }
            }

            $result["error"] = false;
            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminDeleteProductsLinkCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $productId = wc_clean($_POST['productId']);
            $result = array();

            $wpFaService = new HesabfaWpFaService();
//            $wpFaService->deleteAll($productId);
            $wpFaService->updateActiveAll($productId, 0);
            HesabfaLogService::writeLogStr("حذف ارتباط کالاها. کد کالا: " . $productId);

            $result["error"] = false;
            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminUpdateProductAndVariationsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            if (get_option('ssbhesabfa_item_update_price', 'no') == 'no' &&
                get_option('ssbhesabfa_item_update_quantity', 'no') == 'no') {
                $result["error"] = true;
                $result["message"] = "خطا: در تنظیمات افزونه، گزینه های بروزرسانی قیمت و موجودی محصول بر اساس حسابفا فعال نیستند.";
                echo json_encode($result);
                die();
            }

            //Call API
            $api = new Ssbhesabfa_Api();
            $wpFaService = new HesabfaWpFaService();

            $productId = wc_clean($_POST['productId']);
            $productAndCombinations = $wpFaService->getProductAndCombinations($productId);
            $result = array();
            if (count($productAndCombinations) == 0) {
                $result["error"] = true;
                $result["message"] = "هیچ ارتباطی پیدا نشد.";
                echo json_encode($result);
                die();
            }
            $codes = [];
            $ssbhesabfa_item_update_quantity = get_option('ssbhesabfa_item_update_quantity', 'no');
            foreach ($productAndCombinations as $p) {
                $codes[] = str_pad($p->idHesabfa, 6, "0", STR_PAD_LEFT);

                if ($ssbhesabfa_item_update_quantity == 'yes')
                    update_post_meta($p->idWpAttribute == 0 ? $p->idWp : $p->idWpAttribute, '_manage_stock', 'yes');
            }

            $filters = array(array("Property" => "Code", "Operator" => "in", "Value" => $codes));
            $warehouse = get_option('ssbhesabfa_item_update_quantity_based_on', "-1");
            if ($warehouse == "-1")
                $response = $api->itemGetItems(array('Filters' => $filters));
            else {
                $response = $api->itemGetQuantity($warehouse, $codes);
            }

            if ($response->Success) {
                $items = $warehouse == "-1" ? $response->Result->List : $response->Result;
                $newData = [];
                $result["error"] = false;
                foreach ($items as $item) {
                    $newProps = Ssbhesabfa_Admin_Functions::setItemChanges($item);
                    $wpFa = $wpFaService->getWpFaByHesabfaId('product', $item->Code);
                    $newData[] = array("newPrice" => $newProps["newPrice"],
                        "newQuantity" => $newProps["newQuantity"],
                        "attributeId" => $wpFa->idWpAttribute > 0 ? $wpFa->idWpAttribute : $wpFa->idWp);
                }
                $result["newData"] = $newData;
            } else {
                $result["error"] = true;
                $result["message"] = "کالایی با کد وارد شده در حسابفا پیدا نشد.";
                echo json_encode($result);
                die();
                return;
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function add_additional_fields_to_checkout( $fields ) {
        $NationalCode_isActive = get_option('ssbhesabfa_contact_NationalCode_checkbox_hesabfa');
        $EconomicCode_isActive = get_option('ssbhesabfa_contact_EconomicCode_checkbox_hesabfa');
        $RegistrationNumber_isActive = get_option('ssbhesabfa_contact_RegistrationNumber_checkbox_hesabfa');
        $Website_isActive = get_option('ssbhesabfa_contact_Website_checkbox_hesabfa');
	    $Phone_isActive = get_option('ssbhesabfa_contact_Phone_checkbox_hesabfa');

	    $NationalCode_isRequired = get_option('ssbhesabfa_contact_NationalCode_isRequired_hesabfa');
	    $EconomicCode_isRequired = get_option('ssbhesabfa_contact_EconomicCode_isRequired_hesabfa');
	    $RegistrationNumber_isRequired = get_option('ssbhesabfa_contact_RegistrationNumber_isRequired_hesabfa');
	    $Website_isRequired = get_option('ssbhesabfa_contact_Website_isRequired_hesabfa');
	    $Phone_isRequired = get_option('ssbhesabfa_contact_Phone_isRequired_hesabfa');

        //NationalCode
	    if($NationalCode_isActive == 'yes'){
		    $fields['billing']['billing_hesabfa_national_code'] = array(
               'label'     => __('National code', 'ssbhesabfa'),
               'placeholder'   => __('please enter your National code', 'ssbhesabfa'),
               'priority' => 30,
               'required'  => (bool) $NationalCode_isRequired,
               'clear'     => true,
               'maxlength' => 10,
            );
        }
        //Economic code
	    if($EconomicCode_isActive == 'yes'){
            $fields['billing']['billing_hesabfa_economic_code'] = array(
               'label'     => __('Economic code', 'ssbhesabfa'),
               'placeholder'   => __('please enter your Economic code', 'ssbhesabfa'),
               'priority' => 31,
               'required'  => (bool) $EconomicCode_isRequired,
               'clear'     => true
               );
	    }
        //Registration Number
	    if($RegistrationNumber_isActive == 'yes'){
		    $fields['billing']['billing_hesabfa_registeration_number'] = array(
               'label'     => __('Registration number', 'ssbhesabfa'),
               'placeholder'   => __('please enter your Registration number', 'ssbhesabfa'),
               'priority' => 32,
               'required'  => (bool) $RegistrationNumber_isRequired,
               'clear'     => true
               );
	    }
        //Website
	    if($Website_isActive == 'yes'){
		    $fields['billing']['billing_hesabfa_website'] = array(
               'type' => 'url',
               'label'     => __('Website', 'ssbhesabfa'),
               'placeholder'   => __('please enter your Website address', 'ssbhesabfa'),
               'priority' => 33,
               'required'  => (bool) $Website_isRequired,
               'clear'     => true,
             );
	    }
        //Phone
	    if($Phone_isActive == 'yes'){
		    $fields['billing']['billing_hesabfa_phone'] = array(
               'type' => 'url',
               'label'     => __('Phone', 'ssbhesabfa'),
               'placeholder'   => __('please enter your Phone', 'ssbhesabfa'),
               'priority' => 34,
               'required'  => (bool) $Phone_isRequired,
               'clear'     => true,
             );
	    }
        if(isset($_POST['billing_hesabfa_national_code']) || isset($_POST['billing_hesabfa_website'])) {
            $func = new Ssbhesabfa_Admin_Functions();
            $NationalCode = $_POST['billing_hesabfa_national_code'];
            $Website = $_POST['billing_hesabfa_website'];
            if($NationalCode_isRequired) {
                $func->checkNationalCode($NationalCode);
                if(get_option('ssbhesabfa_contact_check_mobile_and_national_code') == 'yes' && $Phone_isActive == 'yes' && $Phone_isRequired) {
	                $mobile = '';
                    if(get_option('ssbhesabfa_contact_add_additional_checkout_fields_hesabfa') == 1)
                        $mobile = $_POST['billing_hesabfa_phone'];
                    elseif(get_option('ssbhesabfa_contact_add_additional_checkout_fields_hesabfa') == 2)
                        $mobile = get_option('ssbhesabfa_contact_Phone_text_hesabfa');

	                $func->checkNationalCodeWithPhone($NationalCode, $mobile);
                }
            }
            if($Website_isRequired)
                $func->checkWebsite($Website);
        }
        return $fields;
    }
//=========================================================================================================================
    function show_additional_fields_in_order_detail($order) {
        $orderId = $order->get_id();
	    $NationalCode       = '_billing_hesabfa_national_code';
        $EconomicCode       = '_billing_hesabfa_economic_code';
	    $RegistrationNumber = '_billing_hesabfa_registeration_number';
	    $Website            = '_billing_hesabfa_website';
	    $Phone              = '_billing_hesabfa_phone';

	    $NationalCode_isActive = get_option('ssbhesabfa_contact_NationalCode_checkbox_hesabfa');
	    $EconomicCode_isActive = get_option('ssbhesabfa_contact_EconomicCode_checkbox_hesabfa');
	    $RegistrationNumber_isActive = get_option('ssbhesabfa_contact_RegistrationNumber_checkbox_hesabfa');
	    $Website_isActive = get_option('ssbhesabfa_contact_Website_checkbox_hesabfa');
	    $Phone_isActive = get_option('ssbhesabfa_contact_Phone_checkbox_hesabfa');

	    $add_additional_fileds = get_option('ssbhesabfa_contact_add_additional_checkout_fields_hesabfa');
	    if($add_additional_fileds == '2') {
		    $NationalCode       = get_option( 'ssbhesabfa_contact_NationalCode_text_hesabfa' );
		    $EconomicCode       = get_option( 'ssbhesabfa_contact_EconomicCode_text_hesabfa' );
		    $RegistrationNumber = get_option( 'ssbhesabfa_contact_RegistrationNumber_text_hesabfa' );
		    $Website            = get_option( 'ssbhesabfa_contact_Website_text_hesabfa' );
		    $Phone              = get_option( 'ssbhesabfa_contact_Phone_text_hesabfa' );
	    }

	    if($NationalCode_isActive == 'yes')
		    echo '<p><strong>' . esc_html__('National code', 'ssbhesabfa')  . ': </strong> ' .'<br>'. '<strong>' . esc_attr(get_post_meta( $orderId, $NationalCode, true )) . '</strong></p>';

	    if($EconomicCode_isActive == 'yes')
		    echo '<p><strong>' . esc_html__('Economic code', 'ssbhesabfa')  . ': </strong> ' .'<br>'. '<strong>' . esc_attr(get_post_meta( $orderId, $EconomicCode, true )) . '</strong></p>';

	    if($RegistrationNumber_isActive == 'yes')
		    echo '<p><strong>' . esc_html__('Registration number', 'ssbhesabfa')  . ': </strong> ' .'<br>'. '<strong>' . esc_attr(get_post_meta( $orderId, $RegistrationNumber, true )) . '</strong></p>';

	    if($Website_isActive == 'yes')
		    echo '<p><strong>' . esc_html__('Website', 'ssbhesabfa')  . ': </strong> ' .'<br>'. '<a target="_blank" href="https://'. esc_attr(get_post_meta( $orderId, $Website, true )) .'">' . esc_html(get_post_meta( $orderId, $Website, true )) . '</a></p>';

        if($Phone_isActive == 'yes')
		    echo '<p><strong>' . esc_html__('Phone', 'ssbhesabfa')  . ': </strong> ' .'<br>'. '<a target="_blank" href="https://'. esc_attr(get_post_meta( $orderId, $Phone, true )) .'">' . esc_html(get_post_meta( $orderId, $Phone, true )) . '</a></p>';
    }
//=========================================================================================================================
}
