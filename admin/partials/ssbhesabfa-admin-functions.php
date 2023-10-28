<?php
include_once(plugin_dir_path(__DIR__) . 'services/ssbhesabfaItemService.php');
include_once(plugin_dir_path(__DIR__) . 'services/ssbhesabfaCustomerService.php');
include_once(plugin_dir_path(__DIR__) . 'services/HesabfaLogService.php');
include_once(plugin_dir_path(__DIR__) . 'services/HesabfaWpFaService.php');

/**
 * @class      Ssbhesabfa_Admin_Functions
 * @version    2.0.80
 * @since      1.0.0
 * @package    ssbhesabfa
 * @subpackage ssbhesabfa/admin/functions
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 */
class Ssbhesabfa_Admin_Functions
{
    public static function isDateInFiscalYear($date)
    {
        $hesabfaApi = new Ssbhesabfa_Api();
        $fiscalYear = $hesabfaApi->settingGetFiscalYear();

        if (is_object($fiscalYear)) {

            if ($fiscalYear->Success) {
                $fiscalYearStartTimeStamp = strtotime($fiscalYear->Result->StartDate);
                $fiscalYearEndTimeStamp = strtotime($fiscalYear->Result->EndDate);
                $dateTimeStamp = strtotime($date);

                if ($dateTimeStamp >= $fiscalYearStartTimeStamp && $dateTimeStamp <= $fiscalYearEndTimeStamp) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                HesabfaLogService::log(array("Cannot get FiscalDate. Error Code: $fiscalYear->ErrroCode. Error Message: $fiscalYear->ErrorMessage"));
                return false;
            }
        }
        HesabfaLogService::log(array("Cannot connect to Hesabfa for get FiscalDate."));
        return false;
    }
//====================================================================================================================
    public function getProductVariations($id_product)
    {
        if (!isset($id_product)) {
            return false;
        }
        $product = wc_get_product($id_product);

        if (is_bool($product)) return false;
        if ($product->is_type('variable')) {
            $children = $product->get_children($args = '', $output = OBJECT);
            $variations = array();
            foreach ($children as $value) {
                $product_variatons = new WC_Product_Variation($value);
                if ($product_variatons->exists()) {
                    $variations[] = $product_variatons;
                }
            }
            return $variations;
        }
        return false;
    }
//========================================================================================================
    public function setItems($id_product_array)
    {
        if (!isset($id_product_array) || $id_product_array[0] == null) return false;
        if (is_array($id_product_array) && empty($id_product_array)) return true;

        $items = array();
        foreach ($id_product_array as $id_product) {
            $product = new WC_Product($id_product);
            if ($product->get_status() === "draft") continue;

            $items[] = ssbhesabfaItemService::mapProduct($product, $id_product, false);

            $variations = $this->getProductVariations($id_product);
            if ($variations)
                foreach ($variations as $variation)
                    $items[] = ssbhesabfaItemService::mapProductVariation($product, $variation, $id_product, false);
        }

        if (count($items) === 0) return false;
        if (!$this->saveItems($items)) return false;
        return true;
    }
//====================================================================================================================
    public function saveItems($items)
    {
        $hesabfa = new Ssbhesabfa_Api();
        $wpFaService = new HesabfaWpFaService();

        $response = $hesabfa->itemBatchSave($items);
        if ($response->Success) {
            foreach ($response->Result as $item)
                $wpFaService->saveProduct($item);
            return true;
        } else {
            HesabfaLogService::log(array("Cannot add/update Hesabfa items. Error Code: " . (string)$response->ErrorCode . ". Error Message: $response->ErrorMessage."));
            return false;
        }
    }
//====================================================================================================================
    public function getContactCodeByCustomerId($id_customer)
    {
        if (!isset($id_customer)) {
            return false;
        }

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id_hesabfa` FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_ps` = $id_customer AND `obj_type` = 'customer'");

        if (is_object($row)) {
            return $row->id_hesabfa;
        } else {
            return null;
        }
    }
//====================================================================================================================
    public function setContact($id_customer, $type = 'first', $id_order = '')
    {
        if (!isset($id_customer)) return false;

        $code = $this->getContactCodeByCustomerId($id_customer);

        $hesabfaCustomer = ssbhesabfaCustomerService::mapCustomer($code, $id_customer, $type, $id_order);

        $hesabfa = new Ssbhesabfa_Api();
        $response = $hesabfa->contactSave($hesabfaCustomer);

        if ($response->Success) {
            $wpFaService = new HesabfaWpFaService();
            $wpFaService->saveCustomer($response->Result);
            return $response->Result->Code;
        } else {
            HesabfaLogService::log(array("Cannot add/update customer. Error Code: " . (string)$response->ErrroCode . ". Error Message: " . (string)$response->ErrorMessage . ". Customer ID: $id_customer"));
            return false;
        }
    }
//====================================================================================================================
    public function setGuestCustomer($id_order)
    {
        if (!isset($id_order)) return false;

        $order = new WC_Order($id_order);
        $contactCode = $this->getContactCodeByPhoneOrEmail($order->get_billing_phone(), $order->get_billing_email());

        $hesabfaCustomer = ssbhesabfaCustomerService::mapGuestCustomer($contactCode, $id_order);

        $hesabfa = new Ssbhesabfa_Api();
        $response = $hesabfa->contactSave($hesabfaCustomer);

        if ($response->Success) {
            $wpFaService = new HesabfaWpFaService();
            $wpFaService->saveCustomer($response->Result);
            return (int)$response->Result->Code;
        } else {
            HesabfaLogService::log(array("Cannot add/update contact. Error Code: " . (string)$response->ErrroCode . ". Error Message: " . (string)$response->ErrorMessage . ". Customer ID: Guest Customer"));
            return false;
        }
    }
//====================================================================================================================
    public function getContactCodeByPhoneOrEmail($phone, $email)
    {
        if (!$email && !$phone) return null;

        $hesabfa = new Ssbhesabfa_Api();
        $response = $hesabfa->contactGetByPhoneOrEmail($phone, $email);

        if (is_object($response)) {
            if ($response->Success && $response->Result->TotalCount > 0) {
                $contact_obj = $response->Result->List;

                if (!$contact_obj[0]->Code || $contact_obj[0]->Code == '0' || $contact_obj[0]->Code == '000000') return null;

                foreach ($contact_obj as $contact) {
                    if (($contact->phone == $phone || $contact->mobile = $phone) && $contact->email == $email)
                        return (int)$contact->Code;
                }
                foreach ($contact_obj as $contact) {
                    if ($phone && $contact->phone == $phone || $contact->mobile = $phone)
                        return (int)$contact->Code;
                }
                foreach ($contact_obj as $contact) {
                    if ($email && $contact->email == $email)
                        return (int)$contact->Code;
                }
                return null;
            }
        } else {
            HesabfaLogService::log(array("Cannot get Contact list. Error Message: (string)$response->ErrorMessage. Error Code: (string)$response->ErrorCode."));
        }

        return null;
    }
//====================================================================================================================
    //Invoice
    public function setOrder($id_order, $orderType = 0, $reference = null)
    {
        if (!isset($id_order)) {
            return false;
        }

        $wpFaService = new HesabfaWpFaService();

        $number = $this->getInvoiceNumberByOrderId($id_order);
        if (!$number) {
            $number = null;
            if ($orderType == 2) //return if saleInvoice not set before
            {
                return false;
            }
        }

        $order = new WC_Order($id_order);

        $dokanOption = get_option("ssbhesabfa_invoice_dokan", 0);

        if ($dokanOption && is_plugin_active("dokan-lite/dokan.php")) {
            $orderCreated = $order->get_created_via();
            if ($dokanOption == 1 && $orderCreated !== 'checkout')
                return false;
            else if ($dokanOption == 2 && $orderCreated === 'checkout')
                return false;
        }

        $id_customer = $order->get_customer_id();
        if ($id_customer !== 0) {

            $contactCode = $this->setContact($id_customer, 'first', $id_order);

            if ($contactCode == null) {
                if (!$contactCode) {
                    return false;
                }
            }
            HesabfaLogService::writeLogStr("order ID " . $id_order);
            if (get_option('ssbhesabfa_contact_address_status') == 2) {
                $this->setContact($id_customer, 'billing', $id_order);
            } elseif (get_option('ssbhesabfa_contact_address_status') == 3) {
                $this->setContact($id_customer, 'shipping', $id_order);
            }
        } else {
            $contactCode = $this->setGuestCustomer($id_order);
            if (!$contactCode) {
                return false;
            }
        }

        global $notDefinedProductID;
        $notDefinedItems = array();
        $products = $order->get_items();
        foreach ($products as $product) {
            if ($product['product_id'] == 0) continue;
            $itemCode = $wpFaService->getProductCodeByWpId($product['product_id'], $product['variation_id']);
            if ($itemCode == null) {
                $notDefinedItems[] = $product['product_id'];
            }
        }

        if (!empty($notDefinedItems)) {
            if (!$this->setItems($notDefinedItems)) {
                HesabfaLogService::writeLogStr("Cannot add/update Invoice. Failed to set products. Order ID: $id_order");
                return false;
            }
        }

        $invoiceItems = array();
        $i = 0;
        $failed = false;
        foreach ($products as $key => $product) {
            $itemCode = $wpFaService->getProductCodeByWpId($product['product_id'], $product['variation_id']);

            if ($itemCode == null) {
                $pId = $product['product_id'];
                $vId = $product['variation_id'];
                HesabfaLogService::writeLogStr("Item not found. productId: $pId, variationId: $vId, Order ID: $id_order");

                $failed = true;
                break;
            }

//            $wcProduct = new WC_Product($product['product_id']);

            if($product['variation_id']) {
                $wcProduct = wc_get_product($product['variation_id']);
            } else {
                $wcProduct = wc_get_product($product['product_id']);
            }

            global $discount, $price;
            if( $wcProduct->is_on_sale() && get_option('ssbhesabfa_set_special_sale_as_discount') === 'yes' ) {
                $price = $this->getPriceInHesabfaDefaultCurrency($wcProduct->get_regular_price());
                $discount = $this->getPriceInHesabfaDefaultCurrency($wcProduct->get_regular_price() - $wcProduct->get_sale_price());
                $discount *= $product['quantity'];
            } else {
                $price = $this->getPriceInHesabfaDefaultCurrency($product['subtotal'] / $product['quantity']);
                $discount = $this->getPriceInHesabfaDefaultCurrency($product['subtotal'] - $product['total']);
            }

            $item = array(
                'RowNumber' => $i,
                'ItemCode' => $itemCode,
                'Description' => Ssbhesabfa_Validation::invoiceItemDescriptionValidation($product['name']),
                'Quantity' => (int)$product['quantity'],
                'UnitPrice' => (float)$price,
                'Discount' => (float)$discount,
                'Tax' => (float)$this->getPriceInHesabfaDefaultCurrency($product['total_tax']),
            );

            $invoiceItems[] = $item;
            $i++;
        }

        if ($failed) {
            HesabfaLogService::writeLogStr("Cannot add/update Invoice. Item code is NULL. Check your invoice products and relations with Hesabfa. Order ID: $id_order");
            return false;
        }

        if (empty($invoiceItems)) {
            HesabfaLogService::log(array("Cannot add/update Invoice. At least one item required."));
            return false;
        }

        $date_obj = $order->get_date_created();
        switch ($orderType) {
            case 0:
                $date = $date_obj->date('Y-m-d H:i:s');
                break;
            case 2:
                $date = date('Y-m-d H:i:s');
                break;
            default:
                $date = $date_obj->date('Y-m-d H:i:s');
        }

        if ($reference === null)
            $reference = $id_order;

        $order_shipping_method = "";
        foreach ($order->get_items('shipping') as $item)
            $order_shipping_method = $item->get_name();

        $note = $order->customer_note;
        if ($order_shipping_method)
            $note .= "\n" . __('Shipping method', 'ssbhesabfa') . ": " . $order_shipping_method;

        global $freightOption, $freightItemCode;
        $freightOption = get_option("ssbhesabfa_invoice_freight");

        if($freightOption == 1) {
            $freightItemCode = get_option('ssbhesabfa_invoice_freight_code');
            if(!isset($freightItemCode) || !$freightItemCode) HesabfaLogService::writeLogStr("کد هزینه حمل و نقل تعریف نشده است" . "\n" . "Freight service code is not set");

            $newNumbers = range(0, 9);
            $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;');
            $arabicDecimal = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;');
            $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
            $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

            $string =  str_replace($persianDecimal, $newNumbers, $freightItemCode);
            $string =  str_replace($arabicDecimal, $newNumbers, $string);
            $string =  str_replace($arabic, $newNumbers, $string);
            $freightItemCode = str_replace($persian, $newNumbers, $string);

            if($this->getPriceInHesabfaDefaultCurrency($order->get_shipping_total()) != 0) {
                $invoiceItem = array(
                    'RowNumber' => $i,
                    'ItemCode' => $freightItemCode,
                    'Description' => 'هزینه حمل و نقل',
                    'Quantity' => 1,
                    'UnitPrice' => (float) $this->getPriceInHesabfaDefaultCurrency($order->get_shipping_total()),
                    'Discount' => 0,
                    'Tax' => (float) $this->getPriceInHesabfaDefaultCurrency($order->get_shipping_tax())
                );
                $invoiceItems[] = $invoiceItem;
            }
        }

        $data = array(
            'Number' => $number,
            'InvoiceType' => $orderType,
            'ContactCode' => $contactCode,
            'Date' => $date,
            'DueDate' => $date,
            'Reference' => $reference,
            'Status' => 2,
            'Tag' => json_encode(array('id_order' => $id_order)),
            'InvoiceItems' => $invoiceItems,
            'Note' => $note,
            'Freight' => ''
        );

        if($freightOption == 0) {
            $freight = $this->getPriceInHesabfaDefaultCurrency($order->get_shipping_total() + $order->get_shipping_tax());
            $data['Freight'] = $freight;
        }

        $invoice_draft_save = get_option('ssbhesabfa_invoice_draft_save_in_hesabfa', 'no');
        if ($invoice_draft_save != 'no')
            $data['Status'] = 0;

        $invoice_project = get_option('ssbhesabfa_invoice_project', -1);
        $invoice_salesman = get_option('ssbhesabfa_invoice_salesman', -1);
        if ($invoice_project != -1) $data['Project'] = $invoice_project;
        if ($invoice_salesman != -1) $data['SalesmanCode'] = $invoice_salesman;

        $hesabfa = new Ssbhesabfa_Api();
        $response = $hesabfa->invoiceSave($data);

        if ($response->Success) {
            global $wpdb;

            switch ($orderType) {
                case 0:
                    $obj_type = 'order';
                    break;
                case 2:
                    $obj_type = 'returnOrder';
                    break;
            }

            if ($number === null) {
                $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array(
                    'id_hesabfa' => (int)$response->Result->Number,
                    'obj_type' => $obj_type,
                    'id_ps' => $id_order,
                ));
                HesabfaLogService::log(array("Invoice successfully added. Invoice number: " . (string)$response->Result->Number . ". Order ID: $id_order"));
            } else {
                $wpFaId = $wpFaService->getWpFaId($obj_type, $id_order);

                $wpdb->update($wpdb->prefix . 'ssbhesabfa', array(
                    'id_hesabfa' => (int)$response->Result->Number,
                    'obj_type' => $obj_type,
                    'id_ps' => $id_order,
                ), array('id' => $wpFaId));
                HesabfaLogService::log(array("Invoice successfully updated. Invoice number: " . (string)$response->Result->Number . ". Order ID: $id_order"));
            }

            $warehouse = get_option('ssbhesabfa_item_update_quantity_based_on', "-1");
            if ($warehouse != "-1" && $orderType === 0)
                $this->setWarehouseReceipt($invoiceItems, (int)$response->Result->Number, $warehouse, $date, $invoice_project);

            return true;
        } else {
            foreach ($invoiceItems as $item) {
                HesabfaLogService::log(array("Cannot add/update Invoice. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . ". Order ID: $id_order" . "\n"
              . "Hesabfa Id:" . $item['ItemCode']
            ));
            }
            return false;
        }
    }
//========================================================================================================================
    public function setWarehouseReceipt($items, $invoiceNumber, $warehouseCode, $date, $project)
    {
        $invoiceOption = get_option('ssbhesabfa_invoice_freight');
        if($invoiceOption == 1) {
            $invoiceFreightCode = get_option('ssbhesabfa_invoice_freight_code');
            for ($i = 0 ; $i < count($items) ; $i++) {
                if($items[$i]["ItemCode"] == $invoiceFreightCode) {
                    unset($items[$i]);
                }
            }
        }

        $data = array(
            'WarehouseCode' => $warehouseCode,
            'InvoiceNumber' => $invoiceNumber,
            'InvoiceType' => 0,
            'Date' => $date,
            'Items' => $items
        );

        if ($project != -1)
            $data['Project'] = $project;

        $hesabfa = new Ssbhesabfa_Api();
        $response = $hesabfa->saveWarehouseReceipt($data);

        if ($response->Success)
            HesabfaLogService::log(array("Warehouse receipt successfully saved/updated. warehouse receipt number: " . (string)$response->Result->Number . ". Invoice number: $invoiceNumber"));
        else
            HesabfaLogService::log(array("Cannot save/update Warehouse receipt. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . ". Invoice number: $invoiceNumber"));
    }
//========================================================================================================================
    public static function getPriceInHesabfaDefaultCurrency($price)
    {
        if (!isset($price)) return false;

        $woocommerce_currency = get_woocommerce_currency();
        $hesabfa_currency = get_option('ssbhesabfa_hesabfa_default_currency');

        if (!is_numeric($price))
            $price = intval($price);

        if ($hesabfa_currency == 'IRR' && $woocommerce_currency == 'IRT')
            $price *= 10;

        if ($hesabfa_currency == 'IRT' && $woocommerce_currency == 'IRR')
            $price /= 10;

        return $price;
    }
//========================================================================================================================
    public static function getPriceInWooCommerceDefaultCurrency($price)
    {
        if (!isset($price)) return false;

        $woocommerce_currency = get_woocommerce_currency();
        $hesabfa_currency = get_option('ssbhesabfa_hesabfa_default_currency');

        if (!is_numeric($price))
            $price = intval($price);

        if ($hesabfa_currency == 'IRR' && $woocommerce_currency == 'IRT')
            $price /= 10;

        if ($hesabfa_currency == 'IRT' && $woocommerce_currency == 'IRR')
            $price *= 10;

        return $price;
    }
//========================================================================================================================
    public function setOrderPayment($id_order)
    {
        if (!isset($id_order)) {
            return false;
        }

        $hesabfa = new Ssbhesabfa_Api();
        $number = $this->getInvoiceCodeByOrderId($id_order);
        if (!$number) {
            return false;
        }

        $order = new WC_Order($id_order);

        if ($order->get_total() <= 0) {
            return true;
        }
        $bank_code = $this->getBankCodeByPaymentMethod($order->get_payment_method());

        if ($bank_code == -1) {
            return true;
        } elseif ($bank_code != false) {
            $transaction_id = $order->get_transaction_id();
            //transaction id cannot be null or empty
            if ($transaction_id == '') {
                $transaction_id = '-';
            }

            $payTempValue = substr($bank_code, 0, 4);
            global $financialData;
            if(get_option('ssbhesabfa_payment_option') == 'no') {
                switch($payTempValue) {
                    case 'bank':
                        $payTempValue = substr($bank_code, 4);
                        $financialData = array('bankCode' => $payTempValue);break;
                    case 'cash':
                        $payTempValue = substr($bank_code, 4);
                        $financialData = array('cashCode' => $payTempValue);break;
                }
            } elseif (get_option('ssbhesabfa_payment_option') == 'yes') {
                $defaultBankCode = get_option('ssbhesabfa_default_payment_method_code');

                $newNumbers = range(0, 9);
                $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;');
                $arabicDecimal = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;');
                $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
                $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

                $string =  str_replace($persianDecimal, $newNumbers, $defaultBankCode);
                $string =  str_replace($arabicDecimal, $newNumbers, $string);
                $string =  str_replace($arabic, $newNumbers, $string);
                $defaultBankCode = str_replace($persian, $newNumbers, $string);

                $financialData = array('bankCode' => $defaultBankCode);
            }

            $date_obj = $order->get_date_paid();
            if ($date_obj == null) {
                $date_obj = $order->get_date_modified();
            }

            $response = $hesabfa->invoiceGet($number);
            if ($response->Success) {
                if ($response->Result->Paid > 0) {
                    // payment submited before
                } else {
                    $response = $hesabfa->invoiceSavePayment($number, $financialData, $date_obj->date('Y-m-d H:i:s'), $this->getPriceInHesabfaDefaultCurrency($order->get_total()), $transaction_id);

                    if ($response->Success) {
                        HesabfaLogService::log(array("Hesabfa invoice payment added. Order ID: $id_order"));
                        return true;
                    } else {
                        HesabfaLogService::log(array("Cannot add Hesabfa Invoice payment. Order ID: $id_order. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . "."));
                        return false;
                    }
                }
                return true;
            } else {
                HesabfaLogService::log(array("Error while trying to get invoice. Invoice Number: $number. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . "."));
                return false;
            }
        } else {
            HesabfaLogService::log(array("Cannot add Hesabfa Invoice payment - Bank Code not defined. Order ID: $id_order"));
            return false;
        }
    }
//========================================================================================================================
    public function getInvoiceNumberByOrderId($id_order)
    {
        if (!isset($id_order)) return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id_hesabfa` FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_ps` = $id_order AND `obj_type` = 'order'");

        if (is_object($row)) {
            return $row->id_hesabfa;
        } else {
            return false;
        }
    }
//========================================================================================================================
    public function getBankCodeByPaymentMethod($payment_method)
    {
        $code = get_option('ssbhesabfa_payment_method_' . $payment_method);

        if (isset($code))
            return $code;
        else
            return false;
    }
//========================================================================================================================
    public function getInvoiceCodeByOrderId($id_order)
    {
        if (!isset($id_order)) return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id_hesabfa` FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_ps` = $id_order AND `obj_type` = 'order'");

        if (is_object($row)) {
            return $row->id_hesabfa;
        } else {
            return false;
        }
    }
//========================================================================================================================
    public function exportProducts($batch, $totalBatch, $total, $updateCount)
    {
        HesabfaLogService::writeLogStr("===== Export Products =====");
        $wpFaService = new HesabfaWpFaService();
        $extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_export_products");
        if($extraSettingRPP != '-1') $rpp=$extraSettingRPP; else $rpp=500;

        $result = array();
        $result["error"] = false;
        global $wpdb;

        if ($batch == 1) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`                                                                
                                WHERE post_type = 'product' AND post_status IN('publish','private')");
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
        $products = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`                                                                
                                WHERE post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC LIMIT $offset,$rpp");

        $items = array();

        foreach ($products as $item) {
            $id_product = $item->ID;
            $product = new WC_Product($id_product);

            $id_obj = $wpFaService->getWpFaId('product', $id_product, 0);

            if (!$id_obj) {
                $hesabfaItem = ssbhesabfaItemService::mapProduct($product, $id_product);
                array_push($items, $hesabfaItem);
                $updateCount++;
            }

            $variations = $this->getProductVariations($id_product);
            if ($variations) {
                foreach ($variations as $variation) {
                    $id_attribute = $variation->get_id();
                    $id_obj = $wpFaService->getWpFaId('product', $id_product, $id_attribute);

                    if (!$id_obj) {
                        $hesabfaItem = ssbhesabfaItemService::mapProductVariation($product, $variation, $id_product);
                        array_push($items, $hesabfaItem);
                        $updateCount++;
                    }
                }
            }
        }

        if (!empty($items)) {
            $count = 0;
            $hesabfa = new Ssbhesabfa_Api();
            $response = $hesabfa->itemBatchSave($items);
            if ($response->Success) {
                foreach ($response->Result as $item) {
                    $json = json_decode($item->Tag);

                    global $wpdb;
                    $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array(
                        'id_hesabfa' => (int)$item->Code,
                        'obj_type' => 'product',
                        'id_ps' => (int)$json->id_product,
                        'id_ps_attribute' => (int)$json->id_attribute,
                    ));
                    HesabfaLogService::log(array("Item successfully added. Item Code: " . (string)$item->Code . ". Product ID: $json->id_product - $json->id_attribute"));
                }
                $count += count($response->Result);
            } else {
                HesabfaLogService::log(array("Cannot add bulk item. Error Message: " . (string)$response->ErrorMessage . ". Error Code: " . (string)$response->ErrorCode . "."));
            }
            sleep(2);
        }

        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        $result["updateCount"] = $updateCount;
        return $result;
    }
//========================================================================================================================
    public function importProducts($batch, $totalBatch, $total, $updateCount)
    {
        HesabfaLogService::writeLogStr("===== Import Products =====");
        $wpFaService = new HesabfaWpFaService();
        $extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_import_products");
        if($extraSettingRPP != '-1') $rpp=$extraSettingRPP; else $rpp=100;

        $result = array();
        $result["error"] = false;
        global $wpdb;
        $hesabfa = new Ssbhesabfa_Api();
        $filters = array(array("Property" => "ItemType", "Operator" => "=", "Value" => 0));

        if ($batch == 1) {
            $total = 0;
            $response = $hesabfa->itemGetItems(array('Take' => 1, 'Filters' => $filters));
            if ($response->Success) {
                $total = $response->Result->FilteredCount;
                $totalBatch = ceil($total / $rpp);
            } else {
                HesabfaLogService::log(array("Error while trying to get products for import. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode."));
                $result["error"] = true;
                return $result;
            };
        }

        $id_product_array = array();
        $offset = ($batch - 1) * $rpp;

        $response = $hesabfa->itemGetItems(array('Skip' => $offset, 'Take' => $rpp, 'SortBy' => 'Id', 'Filters' => $filters));
        if ($response->Success) {
            $items = $response->Result->List;
            $from = $response->Result->From;
            $to = $response->Result->To;

            foreach ($items as $item) {
                $wpFa = $wpFaService->getWpFaByHesabfaId('product', $item->Code);
                if ($wpFa) continue;

                $clearedName = preg_replace("/\s+|\/|\\\|\(|\)/", '-', trim($item->Name));
                $clearedName = preg_replace("/\-+/", '-', $clearedName);
                $clearedName = trim($clearedName, '-');
                $clearedName = preg_replace(["/۰/", "/۱/", "/۲/", "/۳/", "/۴/", "/۵/", "/۶/", "/۷/", "/۸/", "/۹/"],
                    ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"], $clearedName);

                // add product to database
                $wpdb->insert($wpdb->prefix . 'posts', array(
                    'post_author' => get_current_user_id(),
                    'post_date' => date("Y-m-d H:i:s"),
                    'post_date_gmt' => date("Y-m-d H:i:s"),
                    'post_content' => '',
                    'post_title' => $item->Name,
                    'post_excerpt' => '',
                    'post_status' => 'private',
                    'comment_status' => 'open',
                    'ping_status' => 'closed',
                    'post_password' => '',
                    'post_name' => $clearedName,
                    'to_ping' => '',
                    'pinged' => '',
                    'post_modified' => date("Y-m-d H:i:s"),
                    'post_modified_gmt' => date("Y-m-d H:i:s"),
                    'post_content_filtered' => '',
                    'post_parent' => 0,
                    'guid' => get_site_url() . '/product/' . $clearedName . '/',
                    'menu_order' => 0,
                    'post_type' => 'product',
                    'post_mime_type' => '',
                    'comment_count' => 0,
                ));

                $postId = $wpdb->insert_id;
                $id_product_array[] = $postId;
                $price = self::getPriceInWooCommerceDefaultCurrency($item->SellPrice);

                // add product link to hesabfa
                $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array(
                    'obj_type' => 'product',
                    'id_hesabfa' => (int)$item->Code,
                    'id_ps' => $postId,
                    'id_ps_attribute' => 0,
                ));

                update_post_meta($postId, '_manage_stock', 'yes');
                update_post_meta($postId, '_sku', $item->Barcode);
                update_post_meta($postId, '_regular_price', $price);
                update_post_meta($postId, '_price', $price);
                update_post_meta($postId, '_stock', $item->Stock);

                $new_stock_status = ($item->Stock > 0) ? "instock" : "outofstock";
                wc_update_product_stock_status($postId, $new_stock_status);
                $updateCount++;
            }

        } else {
            HesabfaLogService::log(array("Error while trying to get products for import. Error Message: (string)$response->ErrorMessage. Error Code: (string)$response->ErrorCode."));
            $result["error"] = true;
            return $result;
        }
        sleep(2);

        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        $result["updateCount"] = $updateCount;
        return $result;
    }
//========================================================================================================================
    public function exportOpeningQuantity($batch, $totalBatch, $total)
    {
        $wpFaService = new HesabfaWpFaService();

        $result = array();
        $result["error"] = false;
        $extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_export_opening_products");
        if($extraSettingRPP != '-1') $rpp = $extraSettingRPP; else $rpp = 500;

        global $wpdb;

        if ($batch == 1) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`                                                                
                                WHERE post_type = 'product' AND post_status IN('publish','private')");
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;

        $products = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`                                                                
                                WHERE post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC
                                LIMIT $offset,$rpp");

        $items = array();

        foreach ($products as $item) {
            $variations = $this->getProductVariations($item->ID);
            if (!$variations) {
                $id_obj = $wpFaService->getWpFaId('product', $item->ID, 0);

                if ($id_obj != false) {
                    $product = new WC_Product($item->ID);
                    $quantity = $product->get_stock_quantity();
                    $price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();

                    $row = $wpdb->get_row("SELECT `id_hesabfa` FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id` = " . $id_obj . " AND `obj_type` = 'product'");

                    if (is_object($product) && is_object($row) && $quantity > 0 && $price > 0) {
                        array_push($items, array(
                            'Code' => $row->id_hesabfa,
                            'Quantity' => $quantity,
                            'UnitPrice' => $this->getPriceInHesabfaDefaultCurrency($price),
                        ));
                    }
                }
            } else {
                foreach ($variations as $variation) {
                    $id_attribute = $variation->get_id();
                    $id_obj = $wpFaService->getWpFaId('product', $item->ID, $id_attribute);
                    if ($id_obj != false) {
                        $quantity = $variation->get_stock_quantity();
                        $price = $variation->get_regular_price() ? $variation->get_regular_price() : $variation->get_price();

                        $row = $wpdb->get_row("SELECT `id_hesabfa` FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id` = " . $id_obj . " AND `obj_type` = 'product'");

                        if (is_object($variation) && is_object($row) && $quantity > 0 && $price > 0) {
                            array_push($items, array(
                                'Code' => $row->id_hesabfa,
                                'Quantity' => $quantity,
                                'UnitPrice' => $this->getPriceInHesabfaDefaultCurrency($price),
                            ));
                        }
                    }
                }
            }
        }

        if (!empty($items)) {
            $hesabfa = new Ssbhesabfa_Api();
            $response = $hesabfa->itemUpdateOpeningQuantity($items);
            if ($response->Success) {
                // continue batch loop
            } else {
                HesabfaLogService::log(array("ssbhesabfa - Cannot set Opening quantity. Error Code: ' . $response->ErrorCode . '. Error Message: ' . $response->ErrorMessage"));
                $result['error'] = true;
                if ($response->ErrorCode = 199 && $response->ErrorMessage == 'No-Shareholders-Exist') {
                    $result['errorType'] = 'shareholderError';
                    return $result;
                }
                return $result;
            }
        }
        sleep(2);
        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        $result["done"] = $batch == $totalBatch;
        return $result;
    }
//========================================================================================================================
    public function exportCustomers($batch, $totalBatch, $total, $updateCount)
    {
        HesabfaLogService::writeLogStr("==== Export Customers ====");
        $wpFaService = new HesabfaWpFaService();

        $result = array();
        $result["error"] = false;
        $rpp = 500;
        global $wpdb;

        if ($batch == 1) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "users`");
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
        $customers = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "users` ORDER BY ID ASC LIMIT $offset,$rpp");

        $items = array();
        foreach ($customers as $item) {
            $id_customer = $item->ID;
            $id_obj = $wpFaService->getWpFaId('customer', $id_customer);
            if (!$id_obj) {
                $hesabfaCustomer = ssbhesabfaCustomerService::mapCustomer(null, $id_customer);
                array_push($items, $hesabfaCustomer);
                $updateCount++;
            }
        }

        if (!empty($items)) {
            $hesabfa = new Ssbhesabfa_Api();
            $response = $hesabfa->contactBatchSave($items);
            if ($response->Success) {
                foreach ($response->Result as $item) {
                    $json = json_decode($item->Tag);

                    $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array(
                        'id_hesabfa' => (int)$item->Code,
                        'obj_type' => 'customer',
                        'id_ps' => (int)$json->id_customer,
                    ));

                    HesabfaLogService::log(array("Contact successfully added. Contact Code: " . $item->Code . ". Customer ID: " . (int)$json->id_customer));
                }
            } else {
                HesabfaLogService::log(array("Cannot add bulk contacts. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode."));
            }
        }

        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        $result["updateCount"] = $updateCount;

        return $result;
    }
//========================================================================================================================
    public function syncOrders($from_date, $batch, $totalBatch, $total, $updateCount)
    {

        HesabfaLogService::writeLogStr("===== Sync Orders =====");
        $wpFaService = new HesabfaWpFaService();

        $result = array();
        $result["error"] = false;
        $rpp = 10;
        global $wpdb;

        if (!isset($from_date) || empty($from_date)) {
            $result['error'] = 'inputDateError';
            return $result;
        }

        if (!$this->isDateInFiscalYear($from_date)) {
            $result['error'] = 'fiscalYearError';
            return $result;
        }

        if ($batch == 1) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`
                                WHERE post_type = 'shop_order' AND post_date >= '" . $from_date . "'");
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
        $orders = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`
                                WHERE post_type = 'shop_order' AND post_date >= '" . $from_date . "'
                                ORDER BY ID ASC LIMIT $offset,$rpp");
        HesabfaLogService::writeLogStr("Orders count: " . count($orders));

        $statusesToSubmitInvoice = get_option('ssbhesabfa_invoice_status');
        $statusesToSubmitInvoice = implode(',', $statusesToSubmitInvoice);
        $statusesToSubmitReturnInvoice = get_option('ssbhesabfa_invoice_return_status');
        $statusesToSubmitReturnInvoice = implode(',', $statusesToSubmitReturnInvoice);
        $statusesToSubmitPayment = get_option('ssbhesabfa_payment_status');
        $statusesToSubmitPayment = implode(',', $statusesToSubmitPayment);

        $id_orders = array();
        foreach ($orders as $order) {
            $order = new WC_Order($order->ID);

            $id_order = $order->get_id();
            $id_obj = $wpFaService->getWpFaId('order', $id_order);
            $current_status = $order->get_status();

            if (!$id_obj) {
                if (strpos($statusesToSubmitInvoice, $current_status) !== false) {
                    if ($this->setOrder($id_order)) {
                        array_push($id_orders, $id_order);
                        $updateCount++;

                        if (strpos($statusesToSubmitPayment, $current_status) !== false)
                            $this->setOrderPayment($id_order);

                        // set return invoice
                        if (strpos($statusesToSubmitReturnInvoice, $current_status) !== false) {
                            $this->setOrder($id_order, 2, $this->getInvoiceCodeByOrderId($id_order));
                        }
                    }
                }
            }

        }

        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        $result["updateCount"] = $updateCount;
        return $result;
    }
//========================================================================================================================
    public function syncProducts($batch, $totalBatch, $total)
    {
        try {
            HesabfaLogService::writeLogStr("===== Sync products price and quantity from hesabfa to store: part $batch =====");
            $result = array();
            $result["error"] = false;
            $extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_sync_products_into_woocommerce");
            if($extraSettingRPP != '-1') $rpp=$extraSettingRPP; else $rpp=200;

            $hesabfa = new Ssbhesabfa_Api();
            $filters = array(array("Property" => "ItemType", "Operator" => "=", "Value" => 0));

            if ($batch == 1) {
                $response = $hesabfa->itemGetItems(array('Take' => 1, 'Filters' => $filters));
                if ($response->Success) {
                    $total = $response->Result->FilteredCount;
                    $totalBatch = ceil($total / $rpp);
                } else {
                    HesabfaLogService::log(array("Error while trying to get products for sync. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode."));
                    $result["error"] = true;
                    return $result;
                }
            }

            $offset = ($batch - 1) * $rpp;
            $response = $hesabfa->itemGetItems(array('Skip' => $offset, 'Take' => $rpp, 'SortBy' => 'Id', 'Filters' => $filters));

            $warehouse = get_option('ssbhesabfa_item_update_quantity_based_on', "-1");

            if ($warehouse != "-1") {
                $products = $response->Result->List;
                $codes = [];
                foreach ($products as $product)
                    $codes[] = $product->Code;
                $response = $hesabfa->itemGetQuantity($warehouse, $codes);
            }

            if ($response->Success) {
                $products = $warehouse == "-1" ? $response->Result->List : $response->Result;
                foreach ($products as $product) {
                    self::setItemChanges($product);
                }
            } else {
                HesabfaLogService::log(array("Error while trying to get products for sync. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode."));
                $result["error"] = true;
                return $result;
            }

            $result["batch"] = $batch;
            $result["totalBatch"] = $totalBatch;
            $result["total"] = $total;
            return $result;
        } catch (Error $error) {
            HesabfaLogService::writeLogStr("Error in sync products: " . $error->getMessage());
        }
    }
//========================================================================================================================
    public function syncProductsManually($data)
    {
        HesabfaLogService::writeLogStr('===== Sync Products Manually =====');

        $hesabfa_item_codes = array();
        foreach ($data as $d) {
            if ($d["hesabfa_id"]) {
                $hesabfa_item_codes[] = str_pad($d["hesabfa_id"], 6, "0", STR_PAD_LEFT);
            }
        }

        $hesabfa = new Ssbhesabfa_Api();

        $filters = array(array("Property" => "Code", "Operator" => "in", "Value" => $hesabfa_item_codes));
        $response = $hesabfa->itemGetItems(array('Take' => 100, 'Filters' => $filters));

        if ($response->Success) {
            $products = $response->Result->List;
            $products_codes = array();
            foreach ($products as $product)
                $products_codes[] = $product->Code;
            $diff = array_diff($hesabfa_item_codes, $products_codes);
            if (is_array($diff) && count($diff) > 0) {
                return array("result" => false, "data" => $diff);
            }
        }

        $id_product_array = array();
        global $wpdb;

        foreach ($data as $d) {
            $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id_ps_attribute` = " . $d["id"] . " AND `obj_type` = 'product'");

            if (!is_object($row)) {
                $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id_ps` = " . $d["id"] . " AND `obj_type` = 'product'");
            }
            if (is_object($row)) {
                if (!$d["hesabfa_id"])
                    $wpdb->delete($wpdb->prefix . 'ssbhesabfa', array('id' => $row->id));
                else
                    $wpdb->update($wpdb->prefix . 'ssbhesabfa', array('id_hesabfa' => $d["hesabfa_id"]), array('id' => $row->id));
            } else {
                if (!$d["hesabfa_id"])
                    continue;
                if ($d["parent_id"])
                    $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array('obj_type' => 'product', 'id_hesabfa' => $d["hesabfa_id"], 'id_ps' => $d["parent_id"], 'id_ps_attribute' => $d["id"]));
                else
                    $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array('obj_type' => 'product', 'id_hesabfa' => $d["hesabfa_id"], 'id_ps' => $d["id"], 'id_ps_attribute' => '0'));
            }

            if ($d["hesabfa_id"]) {
                if ($d["parent_id"]) {
                    if (!in_array($d["parent_id"], $id_product_array))
                        $id_product_array[] = $d["parent_id"];
                } else {
                    if (!in_array($d["id"], $id_product_array))
                        $id_product_array[] = $d["id"];
                }
            }
        }

        $this->setItems($id_product_array);
        return array("result" => true, "data" => null);
    }
//========================================================================================================================
    public function updateProductsInHesabfaBasedOnStore($batch, $totalBatch, $total)
    {
        HesabfaLogService::writeLogStr("===== Update Products In Hesabfa Based On Store =====");
        $result = array();
        $result["error"] = false;
        $extraSettingRPP = get_option('ssbhesabfa_set_rpp_for_sync_products_into_hesabfa');
        if($extraSettingRPP != '-1') $rpp = get_option('ssbhesabfa_set_rpp_for_sync_products_into_hesabfa'); else $rpp = 500;
        global $wpdb;

        if ($batch == 1) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`                                                                
                                WHERE post_type = 'product' AND post_status IN('publish','private')");
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
        $products = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`                                                                
                                WHERE post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC LIMIT $offset,$rpp");

        $products_id_array = array();
        foreach ($products as $product)
            $products_id_array[] = $product->ID;
        $this->setItems($products_id_array);
        sleep(2);

        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        return $result;
    }
//========================================================================================================================
    public static function updateProductsInHesabfaBasedOnStoreWithFilter($offset=0, $rpp=0)
    {
        HesabfaLogService::writeLogStr("===== Update Products With Filter In Hesabfa Based On Store =====");
        $result = array();
        $result["error"] = false;

        global $wpdb;
        if($offset != 0 && $rpp != 0) {
            if(abs($rpp - $offset) <= 200) {
                if($rpp > $offset) {
                    $products = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "posts`                                                                
                                            WHERE ID BETWEEN $offset AND $rpp AND post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC");

                    $products_id_array = array();
                    foreach ($products as $product)
                        $products_id_array[] = $product->ID;
                    $response = (new Ssbhesabfa_Admin_Functions)->setItems($products_id_array);
                    if(!$response) $result['error'] = true;
                } else {
                    $products = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "posts`                                                                
                                            WHERE ID BETWEEN $rpp AND $offset AND post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC");

                    $products_id_array = array();
                    foreach ($products as $product)
                        $products_id_array[] = $product->ID;
                    $response = (new Ssbhesabfa_Admin_Functions)->setItems($products_id_array);
                    if(!$response) $result['error'] = true;
                }
            } else {
                $result['error'] = true;
                echo '<script>alert("بازه ID نباید بیشتر از 200 عدد باشد")</script>';
            }
        } else {
            echo '<script>alert("کد کالای معتبر وارد نمایید")</script>';
        }

        return $result;
    }
//========================================================================================================================
    public function cleanLogFile()
    {
        HesabfaLogService::clearLog();
        return true;
    }
//========================================================================================================================
    public static function setItemChanges($item)
    {
        if (!is_object($item)) return false;

        if ($item->Quantity || !$item->Stock)
            $item->Stock = $item->Quantity;

        $wpFaService = new HesabfaWpFaService();
        global $wpdb;

        $wpFa = $wpFaService->getWpFaByHesabfaId('product', $item->Code);
        if (!$wpFa) return false;

        $id_product = $wpFa->idWp;
        $id_attribute = $wpFa->idWpAttribute;

        if ($id_product == 0) {
            HesabfaLogService::log(array("Item with code: $item->Code is not defined in Online store"));
            return false;
        }

        $found = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts` WHERE ID = $id_product");

        if (!$found) {
            HesabfaLogService::writeLogStr("product not found in woocommerce.code: $item->Code, product id: $id_product, variation id: $id_attribute");
            return false;
        }

        $product = wc_get_product($id_product);
        $variation = $id_attribute != 0 ? wc_get_product($id_attribute) : null;

        $result = array();
        $result["newPrice"] = null;
        $result["newQuantity"] = null;

        $p = $variation ? $variation : $product;

        if (get_option('ssbhesabfa_item_update_price') == 'yes')
            $result = self::setItemNewPrice($p, $item, $id_attribute, $id_product, $result);

        if (get_option('ssbhesabfa_item_update_quantity') == 'yes')
            $result = self::setItemNewQuantity($p, $item, $id_product, $id_attribute, $result);

        return $result;
    }
//========================================================================================================================
    private static function setItemNewPrice($product, $item, $id_attribute, $id_product, array $result): array
    {
        $option_sale_price = get_option('ssbhesabfa_item_update_sale_price', 0);
        $woocommerce_currency = get_woocommerce_currency();
        $hesabfa_currency = get_option('ssbhesabfa_hesabfa_default_currency');

        $old_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
        $old_price = Ssbhesabfa_Admin_Functions::getPriceInHesabfaDefaultCurrency($old_price);

        $post_id = $id_attribute && $id_attribute > 0 ? $id_attribute : $id_product;

        if ($item->SellPrice != $old_price) {
            $new_price = Ssbhesabfa_Admin_Functions::getPriceInWooCommerceDefaultCurrency($item->SellPrice);
            update_post_meta($post_id, '_regular_price', $new_price);
            update_post_meta($post_id, '_price', $new_price);


            $sale_price = $product->get_sale_price();
            if ($sale_price && is_numeric($sale_price)) {
                $sale_price = Ssbhesabfa_Admin_Functions::getPriceInHesabfaDefaultCurrency($sale_price);
                if (+$option_sale_price === 1) {
                    update_post_meta($post_id, '_sale_price', null);
                } elseif (+$option_sale_price === 2) {
                    update_post_meta($post_id, '_sale_price', round(($sale_price * $new_price) / $old_price));
                    update_post_meta($post_id, '_price', round(($sale_price * $new_price) / $old_price));
                } else {
                    if($woocommerce_currency == 'IRT' && $hesabfa_currency == 'IRR') update_post_meta($post_id, '_price', ($sale_price/10));
                    elseif($woocommerce_currency == 'IRR' && $hesabfa_currency == 'IRT') update_post_meta($post_id, '_price', ($sale_price*10));
                    elseif($woocommerce_currency == 'IRR' && $hesabfa_currency == 'IRR') update_post_meta($post_id, '_price', $sale_price);
                    elseif($woocommerce_currency == 'IRT' && $hesabfa_currency == 'IRT') update_post_meta($post_id, '_price', $sale_price);
                }
            }

            HesabfaLogService::log(array("product ID $id_product-$id_attribute Price changed. Old Price: $old_price. New Price: $new_price"));
            $result["newPrice"] = $new_price;
        }

        return $result;
    }
//========================================================================================================================
    private static function setItemNewQuantity($product, $item, $id_product, $id_attribute, array $result): array
    {
        $old_quantity = $product->get_stock_quantity();
        if ($item->Stock != $old_quantity) {
            $new_quantity = $item->Stock;
            if (!$new_quantity) $new_quantity = 0;

            $new_stock_status = ($new_quantity > 0) ? "instock" : "outofstock";

            $post_id = ($id_attribute && $id_attribute > 0) ? $id_attribute : $id_product;

            update_post_meta($post_id, '_stock', $new_quantity);
            wc_update_product_stock_status($post_id, $new_stock_status);

            HesabfaLogService::log(array("product ID $id_product-$id_attribute quantity changed. Old quantity: $old_quantity. New quantity: $new_quantity"));
            $result["newQuantity"] = $new_quantity;
        }

        return $result;
    }
//=========================================================================================================================
    function CheckNationalCode($NationalCode): void
    {
        $identicalDigits = ['1111111111', '2222222222', '3333333333', '4444444444', '5555555555', '6666666666', '7777777777', '8888888888', '9999999999'];

        if(strlen($NationalCode) === 10) {
            $summation = 0;
            $j = 10;
            for($i = 0 ; $i < 9 ; $i++) {
                $digit = substr($NationalCode, $i, 1);
                $temp = $digit * $j;
                $j -= 1;
                $summation += $temp;
            }
            $controlDigit = substr($NationalCode, 9, 1);
            $retrieve = $summation % 11;

            if(in_array($NationalCode, $identicalDigits) === false) {
                if($retrieve < 2) {
                    if($controlDigit != $retrieve) {
                        wc_add_notice(__('please enter a valid national code', 'ssbhesabfa'), 'error');
                    }
                } else {
                    if($controlDigit != (11 - $retrieve)) {
                        wc_add_notice(__('please enter a valid national code', 'ssbhesabfa'), 'error');
                    }
                }
            }
        } else {
            wc_add_notice(__('please enter a valid national code', 'ssbhesabfa'), 'error');
        }
    }
//=========================================================================================================================
    function CheckWebsite($Website): void
    {
        if (filter_var($Website, FILTER_VALIDATE_URL)) {
            //
        } else {
            wc_add_notice(__('please enter a valid Website URL', 'ssbhesabfa'), 'error');
        }
    }
//==============================================================================================
    public static function enableDebugMode() {
        update_option('ssbhesabfa_debug_mode', 1);
    }

    public static function disableDebugMode() {
        update_option('ssbhesabfa_debug_mode', 0);
    }
//==============================================================================================
}