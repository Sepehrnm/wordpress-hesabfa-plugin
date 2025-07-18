<?php

/**
 * @class      Ssbhesabfa_Admin_Display
 * @version    2.2.3
 * @since      1.0.0
 * @package    ssbhesabfa
 * @subpackage ssbhesabfa/admin/display
 * @author     Sepehr Najafi <sepehrnm78@yahoo.com>
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 */

class Ssbhesabfa_Admin_Display
{
    /**
     * Ssbhesabfa_Admin_Display constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array(__CLASS__, 'hesabfa_add_menu'));
    }

    /**
     * Hook in methods
     * @since    1.0.0
     * @access   static
     */

    /**
     * @since    1.0.0
     * @access   public
     */

    static function hesabfa_add_menu()
    {
        $iconUrl = plugins_url('/hesabfa-accounting/admin/img/menu-icon.png');
        add_menu_page("حسابفا", "حسابفا", "manage_options", "ssbhesabfa-option", array(__CLASS__, 'hesabfa_plugin_page'), $iconUrl, null);
        add_submenu_page("ssbhesabfa-option", "تنظیمات حسابفا", "تنظیمات حسابفا", "manage_options", 'ssbhesabfa-option', array(__CLASS__, 'hesabfa_plugin_page'));
    }

    function hesabfa_plugin_sync_products_manually()
    {
        $page = $_GET["p"];
        $rpp = $_GET["rpp"];
        if (isset($_GET['data'])) {
            $data = $_GET["data"];
            $codesNotFoundInHesabfa = explode(",", $data);
        }
        //set default values to page and rpp
        if (!$page) $page = 1;
        if (!$rpp) $rpp = 10;

        $result = self::getProductsAndRelations($page, $rpp);
        $pageCount = ceil($result["totalCount"] / $rpp);
        $i = ($page - 1) * $rpp;
        $rpp_options = [10, 15, 20, 30, 50];

        $showTips = true;
        if (!isset($_COOKIE['syncProductsManuallyHelp'])) {
            setcookie('syncProductsManuallyHelp', 'ture');
        } else {
            $showTips = false;
        }

        self::hesabfa_plugin_header();
        ?>
        <div class="hesabfa-f">
            <p class="p mt-4">
            <h5 class="h5 hesabfa-tab-page-title">
                همسان سازی دستی کالاهای فروشگاه با حسابفا
                <span class="badge bg-warning text-dark hand <?= $showTips ? 'd-none' : 'd-inline-block' ?>"
                      id="show-tips-btn">مشاهده نکات مهم</span>
            </h5>

            <div class="alert alert-danger alert-dismissible fade show <?= isset($codesNotFoundInHesabfa) ? 'd-block' : 'd-none' ?>"
                 role="alert">
                <strong>خطا</strong><br> کدهای زیر در حسابفا پیدا نشد:
                <br>
                <?php foreach ($codesNotFoundInHesabfa as $code): ?>
                    <span class="badge bg-secondary"><?= esc_html($code) ?></span>&nbsp;
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div id="tips-alert"
                 class="alert alert-warning alert-dismissible fade show <?= $showTips ? 'd-block' : 'd-none' ?>"
                 role="alert">
                <strong>توجه!</strong>
                <ul style="list-style-type:square">
                    <li>تغییرات هر صفحه را ذخیره کنید و سپس به صفحه بعد بروید.</li>
                    <li>کد حسابفا همان کد 6 رقمی (کد حسابداری کالا) است.</li>
                    <li>از وجود تعریف کالا در حسابفا اطمینان حاصل کنید.</li>
                    <li>این صفحه برای زمانی است که شما از قبل یک کالا را هم در فروشگاه و هم در حسابفا
                        تعریف کرده اید اما اتصالی بین آنها وجود ندارد.
                        به کمک این صفحه می توانید این اتصال را بصورت دستی برقرار کنید.
                    </li>
                    <li>
                        برای راحتی کار، این جدول بر اساس نام محصول مرتب سازی شده است،
                        بنابراین در حسابفا نیز لیست کالاها را بر اساس نام مرتب سازی کرده و از روی آن شروع به وارد کردن
                        کدهای
                        متناظر در این جدول نمایید.
                    </li>
                </ul>
                <button type="button" class="btn-close" id="hide-tips-btn"></button>
            </div>

            </p>
            <form id="ssbhesabfa_sync_products_manually" autocomplete="off"
                  action="<?php echo esc_attr(admin_url('admin.php?page=hesabfa-sync-products-manually&p=1')); ?>"
                  method="post">

                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">ID</th>
                        <th scope="col">نام کالا</th>
                        <th scope="col">شناسه محصول</th>
                        <th scope="col">کد حسابفا</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($result["data"] as $p):
                        $i++; ?>
                        <tr class="<?= $p->id_hesabfa ? 'table-success' : 'table-danger'; ?>">
                            <th scope="row"><?= esc_html($i); ?></th>
                            <td><?= esc_html($p->ID); ?></td>
                            <td><?= esc_html($p->post_title); ?></td>
                            <td><?= esc_html($p->sku); ?></td>
                            <td>
                                <input type="text" class="form-control code-input" id="<?= esc_attr($p->ID); ?>"
                                       data-parent-id="<?= esc_attr($p->post_parent); ?>" value="<?= esc_attr($p->id_hesabfa); ?>"
                                       style="width: 100px">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <label><?= esc_html($result["totalCount"]) ?> رکورد </label> |
                <label><?= esc_html($pageCount) ?> صفحه </label> |
                <label>صفحه جاری: </label>
                <input id="pageNumber" class="form-control form-control-sm d-inline" type="text" value="<?= esc_attr($page) ?>"
                       style="width: 80px">
                <a id="goToPage" class="btn btn-outline-secondary btn-sm" data-rpp="<?= esc_attr($rpp) ?>"
                   href="javascript:void(0)">برو</a>

                <div class="dropdown d-inline">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                            id="dropdownMenuButton1"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <?= esc_html($rpp) . ' ' ?>ردیف در هر صفحه
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <?php foreach ($rpp_options as $option): ?>
                            <li><a class="dropdown-item"
                                   href="?page=hesabfa-sync-products-manually&p=<?= esc_attr($page) ?>&rpp=<?= esc_attr($option) ?>"><?= esc_html($option) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <a class="btn btn-outline-secondary btn-sm <?= $page == 1 ? 'disabled' : '' ?>"
                   href="?page=hesabfa-sync-products-manually&p=<?= esc_attr($page) - 1 ?>&rpp=<?= esc_attr($rpp) ?>">< صفحه قبل</a>
                <a class="btn btn-outline-secondary btn-sm <?= $page == $pageCount ? 'disabled' : '' ?>"
                   href="?page=hesabfa-sync-products-manually&p=<?= esc_attr($page) + 1 ?>&rpp=<?= esc_attr($rpp) ?>">صفحه بعد ></a>

                <div class="mt-3">
                    <button class="btn btn-success" id="ssbhesabfa_sync_products_manually-submit"
                            name="ssbhesabfa_sync_products_manually-submit"><?php echo esc_html__('Save changes', 'ssbhesabfa'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }
//========================================================================================================================================
    function hesabfa_plugin_repeated_products()
    {
        global $wpdb;
        //$rows = $wpdb->get_results("SELECT id_hesabfa FROM " . $wpdb->prefix . "ssbhesabfa WHERE obj_type = 'product' GROUP BY id_hesabfa HAVING COUNT(id_hesabfa) > 1;");

        $rows = $wpdb->get_results(
            "SELECT id_hesabfa
            FROM {$wpdb->prefix}ssbhesabfa
            WHERE obj_type = 'product'
            GROUP BY id_hesabfa
            HAVING COUNT(id_hesabfa) > 1"
        );

        $ids = array();

        foreach ($rows as $row)
            $ids[] = $row->id_hesabfa;

        $idsStr = implode(',', $ids);
        //$rows = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "ssbhesabfa WHERE obj_type = 'product' AND id_hesabfa IN ($idsStr) ORDER BY id_hesabfa");

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$wpdb->prefix}ssbhesabfa
                WHERE obj_type = 'product'
                AND id_hesabfa IN ($idsStr)
                ORDER BY id_hesabfa"
            )
        );

        $i = 0;

        self::hesabfa_plugin_header();
        ?>
        <div class="hesabfa-f mt-4">
            <h5 class="h5 hesabfa-tab-page-title">
                کد محصولات تکراری
            </h5>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">کد حسابفا</th>
                    <th scope="col">شناسه محصول</th>
                    <th scope="col">شناسه متغیر</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $p):
                    $i++; ?>
                    <tr>
                        <th scope="row"><?= esc_html($i); ?></th>
                        <td><?= esc_html($p->id_hesabfa); ?></td>
                        <td><?= esc_html($p->id_ps); ?></td>
                        <td><?= esc_html($p->id_ps_attribute); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
//========================================================================================================================================
    function hesabfa_plugin_tools() {
        self::hesabfa_plugin_header();
        ?>
        <div class="hesabfa-f mt-4">
            <h5 class="h5 hesabfa-tab-page-title">
                ابزارهای افزونه حسابفا
            </h5>

            <a href="javascript:void(0);" class="btn btn-danger mt-2" id="hesabfa-clear-plugin-data" >حذف دیتای افزونه</a>
            <br>
            <a href="javascript:void(0);" class="btn btn-success mt-2" id="hesabfa-install-plugin-data">نصب دیتای افزونه</a>
        </div>
        <?php
    }
//========================================================================================================================================
    public static function getProductsAndRelations($page, $rpp)
    {
        $offset = ($page - 1) * $rpp;

        global $wpdb;
//        $rows = $wpdb->get_results("SELECT post.ID,post.post_title,post.post_parent,post_excerpt,wc.sku FROM `" . $wpdb->prefix . "posts` as post
//                                LEFT OUTER JOIN `" . $wpdb->prefix . "wc_product_meta_lookup` as wc
//                                ON post.id =  wc.product_id
//                                WHERE post.post_type IN('product','product_variation') AND post.post_status IN('publish','private')
//                                ORDER BY post.post_title ASC LIMIT $offset,$rpp");

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post.ID, post.post_title, post.post_parent, post.post_excerpt, wc.sku
                FROM {$wpdb->posts} AS post
                LEFT OUTER JOIN {$wpdb->prefix}wc_product_meta_lookup AS wc
                ON post.ID = wc.product_id
                WHERE post.post_type IN ('product', 'product_variation')
                AND post.post_status IN ('publish', 'private')
                ORDER BY post.post_title ASC
                LIMIT %d, %d",
                $offset,
                $rpp
            )
        );



//        $totalCount = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts` as post
//                                LEFT OUTER JOIN `" . $wpdb->prefix . "wc_product_meta_lookup` as wc
//                                ON post.id =  wc.product_id
//                                WHERE post.post_type IN('product','product_variation') AND post.post_status IN('publish','private')");

        $totalCount = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM {$wpdb->posts} AS post
            LEFT OUTER JOIN {$wpdb->prefix}wc_product_meta_lookup AS wc
            ON post.ID = wc.product_id
            WHERE post.post_type IN ('product', 'product_variation')
            AND post.post_status IN ('publish', 'private')"
        );


//        $links = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "ssbhesabfa`
//                                WHERE obj_type ='product'");

        $links = $wpdb->get_results(
            "SELECT *
            FROM {$wpdb->prefix}ssbhesabfa
            WHERE obj_type = 'product'"
        );

        foreach ($rows as $r) {
            if ($r->post_excerpt)
                $r->post_title = $r->post_title . ' [' . $r->post_excerpt . ']';
        }

        foreach ($links as $link) {
            foreach ($rows as $r) {
                if ($r->ID == $link->id_ps && $link->id_ps_attribute == 0) {
                    $r->id_hesabfa = $link->id_hesabfa;
                } else if ($r->ID == $link->id_ps_attribute) {
                    $r->id_hesabfa = $link->id_hesabfa;
                }
            }
        }

        return array("data" => $rows, "totalCount" => $totalCount);
    }
//========================================================================================================================================
    /**
     * @since    1.0.0
     * @access   public
     */
    public static function hesabfa_plugin_page()
    {
        $iconsArray = ['home', 'cog', 'box-open', 'users', 'file-invoice-dollar', 'money-check-alt', 'file-export', 'sync-alt', 'file-alt', 'cog'];
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $setting_tabs = apply_filters('ssbhesabfa_setting_tab', array(
                'home' => __('Home', 'ssbhesabfa'),
                'api' => __('API', 'ssbhesabfa'),
                'catalog' => __('Catalog', 'ssbhesabfa'),
                'customers' => __('Customers', 'ssbhesabfa'),
                'invoice' => __('Invoice', 'ssbhesabfa'),
                'payment' => __('Payment Methods', 'ssbhesabfa'),
                'export' => __('Import and export data', 'ssbhesabfa'),
                'sync' => __('Sync', 'ssbhesabfa'),
                'log' => __('Log', 'ssbhesabfa'),
                'extra' => __('Extra Settings', 'ssbhesabfa')
            ));
            $current_tab = (isset($_GET['tab'])) ? wc_clean($_GET['tab']) : 'home';
            self::hesabfa_plugin_header();
            ?>
            <h2 class="nav-tab-wrapper mt-2">
                <?php
                $i = 0;
                foreach ($setting_tabs as $name => $label) {
                    $iconUrl = plugins_url("/hesabfa-accounting/admin/img/icons/$iconsArray[$i].svg");
                    $i++;
	                echo '
                    <a 
                        href="' . esc_attr(admin_url('admin.php?page=ssbhesabfa-option&tab=' . $name)) . '" 
                        class="nav-tab ' . ($current_tab == $name ? 'nav-tab-active' : '') . '"
                    >
                        <svg width="16" height="16" class="hesabfa-tab-icon">
                            <image href="' . esc_attr($iconUrl) . '" width="16" height="16" />
                        </svg>' . esc_html($label) . '
                    </a>';
                }
                ?>
            </h2>
            <?php
            foreach ($setting_tabs as $setting_tabkey => $setting_tabvalue) {
                switch ($setting_tabkey) {
                    case $current_tab:
                        do_action('ssbhesabfa_' . $setting_tabkey . '_setting_save_field');
                        do_action('ssbhesabfa_' . $setting_tabkey . '_setting');
                        break;
                }
            }
        } else {
            echo '<div class="wrap">' . esc_html__('Hesabfa Plugin requires the WooCommerce to work!, Please install/activate woocommerce and try again', 'ssbhesabfa') . '</div>';
        }
    }
//========================================================================================================================================
    public static function hesabfa_plugin_header()
    {
        $logoUrl = plugins_url('/hesabfa-accounting/admin/img/hesabfa-logo.fa.png');
        ?>
        <div class="hesabfa-header">
            <div class="row">
                <div class="col-auto">
                    <img src="<?php echo esc_attr($logoUrl) ?>" alt="حسابفا">
                </div>
                <div class="col"></div>
                <div class="col-auto">
                    <a class="btn btn-sm btn-success" href="https://app.hesabfa.com/u/login" target="_blank">ورود به
                        حسابفا</a>
                    <a class="btn btn-sm btn-warning"
                       href="https://www.hesabfa.com/help/topics/%D8%A7%D9%81%D8%B2%D9%88%D9%86%D9%87/%D9%88%D9%88%DA%A9%D8%A7%D9%85%D8%B1%D8%B3"
                       target="_blank">راهنمای افزونه</a>
                </div>
            </div>
        </div>
        <?php
    }
//========================================================================================================================================
}

new Ssbhesabfa_Admin_Display();
