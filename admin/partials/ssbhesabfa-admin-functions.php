<?php
include_once(plugin_dir_path(__DIR__) . 'services/ssbhesabfaItemService.php');
include_once(plugin_dir_path(__DIR__) . 'services/ssbhesabfaCustomerService.php');
include_once(plugin_dir_path(__DIR__) . 'services/HesabfaLogService.php');
include_once(plugin_dir_path(__DIR__) . 'services/HesabfaWpFaService.php');

/**
 * @class      Ssbhesabfa_Admin_Functions
 * @version    2.2.3
 * @since      1.0.0
 * @package    ssbhesabfa
 * @subpackage ssbhesabfa/admin/functions
 * @author     Sepehr Najafi <sepehrnm78@yahoo.com>
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
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
        //$row = $wpdb->get_row("SELECT `id_hesabfa` FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_ps` = $id_customer AND `obj_type` = 'customer'");

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT `id_hesabfa` FROM {$wpdb->prefix}ssbhesabfa 
                WHERE `id_ps` = %d AND `obj_type` = 'customer'",
                $id_customer
            )
        );

        if (is_object($row)) {
            return $row->id_hesabfa;
        } else {
            return null;
        }
    }
//====================================================================================================================
    public function setContact($id_customer, $type = 'first', $id_order = '', $additionalFields = array())
    {
        if (!isset($id_customer)) return false;

        $code = $this->getContactCodeByCustomerId($id_customer);

        $hesabfaCustomer = ssbhesabfaCustomerService::mapCustomer($code, $id_customer, $type, $id_order, $additionalFields);

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
    public function setGuestCustomer($id_order, $additionalFields = array())
    {
        if (!isset($id_order)) return false;

        //$order = new WC_Order($id_order);
        $order = wc_get_order($id_order);

        $contactCode = $this->getContactCodeByPhoneOrEmail($order->get_billing_phone(), $order->get_billing_email());

        $hesabfaCustomer = ssbhesabfaCustomerService::mapGuestCustomer($contactCode, $id_order, $additionalFields);

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
        if($phone != '')
            $phone = $this->normalizePhoneNumber($phone);
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
    public function normalizePhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Handle country code '98'
        if (substr($phone, 0, 2) === '98') { // Replace str_starts_with
            $phone = substr($phone, 2); // Remove '98'
        } elseif (substr($phone, 0, 4) === '0098') { // Replace str_starts_with
            $phone = substr($phone, 4); // Remove '0098' for international format
        }

        // Ensure the number starts with '0'
        if (substr($phone, 0, 1) !== '0') { // Replace str_starts_with
            $phone = '0' . $phone;
        }

        // Truncate to the standard 11 digits for Iranian phone numbers
        if (strlen($phone) > 11) {
            $phone = substr($phone, -11);
        }

        return $phone;
    }
//====================================================================================================================
	public function deleteInvoiceLink($orderId): bool {
		$wpFaService = new HesabfaWpFaService();
		$wpFa = $wpFaService->getWpFa('order', $orderId);
		if ($wpFa) {
			$wpFaService->updateActive($wpFa, false);

			HesabfaLogService::log(array("Invoice Link Deactivated Manually. Order Id: " . $orderId));
			return true;
		}

		return false;
	}
//====================================================================================================================
    //Invoice
    public function setOrder($id_order, $orderType = 0, $reference = null, $orderItems = array()) {
	    if (!isset($id_order)) {
		    return false;
	    }

        try {
            $wpFaService = new HesabfaWpFaService();

            $number = $this->getInvoiceNumberByOrderId($id_order);
            HesabfaLogService::log(array("--------------------------- id_order: $id_order - invoice_number: $number ---------------------------"));
            if (!$number) {
                $number = null;
                if ($orderType == 2) //return if saleInvoice not set before
                {
                    return false;
                }
            }

//        $order = new WC_Order($id_order);
            $order = wc_get_order($id_order);
            if(get_option("ssbhesabfa_save_order_option") == 1)
                $orderItems = $order->get_items();

            $additionalFields = array();
            //save additional fields meta
            if(
                get_option('ssbhesabfa_contact_NationalCode_checkbox_hesabfa') == 'yes' ||
                get_option('ssbhesabfa_contact_EconomicCode_checkbox_hesabfa') == 'yes' ||
                get_option('ssbhesabfa_contact_RegistrationNumber_checkbox_hesabfa') == 'yes' ||
                get_option('ssbhesabfa_contact_Website_checkbox_hesabfa') == 'yes' ||
                get_option('ssbhesabfa_contact_Phone_checkbox_hesabfa') == 'yes'
            ) {
                if(get_option('ssbhesabfa_contact_add_additional_checkout_fields_hesabfa') == 1) {
                    $additionalFields = array(
                        "nationalCode" => $order->get_meta('_billing_hesabfa_national_code'),
                        "economicCode" => $order->get_meta('_billing_hesabfa_economic_code'),
                        "registrationNumber" => $order->get_meta('_billing_hesabfa_registeration_number'),
                        "website" => $order->get_meta('_billing_hesabfa_website'),
                        "phone" => $order->get_meta('_billing_hesabfa_phone')
                    );
                    $this->SaveAdditionalFieldsMeta($id_order, $additionalFields);
                } elseif(get_option('ssbhesabfa_contact_add_additional_checkout_fields_hesabfa') == '2') {
                    $additionalFields = array(
                        "nationalCode" => get_option('ssbhesabfa_contact_NationalCode_text_hesabfa'),
                        "economicCode" => get_option('ssbhesabfa_contact_EconomicCode_text_hesabfa'),
                        "registrationNumber" => get_option('ssbhesabfa_contact_RegistrationNumber_text_hesabfa'),
                        "website" => get_option('ssbhesabfa_contact_Website_text_hesabfa'),
                        "phone" => get_option('ssbhesabfa_contact_Phone_text_hesabfa')
                    );
                    $this->SaveAdditionalFieldsMeta($id_order, $additionalFields);
                }
            }
            $dokanOption = get_option("ssbhesabfa_invoice_dokan", 0);

            if ($dokanOption && is_plugin_active("dokan-lite/dokan.php")) {
                $orderCreated = $order->get_created_via();
                if ($dokanOption == 1 && $orderCreated !== 'checkout')
                    return false;
                else if ($dokanOption == 2 && $orderCreated === 'checkout')
                    return false;
            }

            if(get_option("ssbhesabfa_invoice_save_for_one_person_in_hesabfa") == 1 ||
                get_option("ssbhesabfa_invoice_save_for_one_person_in_hesabfa") == "yes"
            ) {
                $contactCode = get_option("ssbhesabfa_invoice_save_for_one_person_in_hesabfa_code");
                if(!$contactCode)
                    HesabfaLogService::writeLogStr("Invalid Contact Code");
                HesabfaLogService::log(array("Fixed Contact Code for All Invoices. Code: " . $contactCode));
                $isContactSaved = true;
            } else {
                try {
                    $isContactSaved = false;
                    $id_customer = $order->get_customer_id();
                    if ($id_customer !== 0) {

                        $contactCode = $this->setContact($id_customer, 'first', $id_order, $additionalFields);

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
                        $contactCode = $this->setGuestCustomer($id_order, $additionalFields);
                        if (!$contactCode) {
                            return false;
                        }
                    }
                    $isContactSaved = true;
                } catch (Exception $ex) {
                    HesabfaLogService::log(array("Error in saving contact. Error: " . $ex->getMessage()));
                }
            }

            if(!$isContactSaved)
                return false;

            global $notDefinedProductID;
            $notDefinedItems = array();
            $products = $order->get_items();
            if(get_option("ssbhesabfa_save_order_option") == 1) {
                $products = $orderItems;
            }
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
                    $price = $this->getPriceInHesabfaDefaultCurrency((int)$product['subtotal'] / $product['quantity']);
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

            if(is_plugin_active("persian-woocommerce/woocommerce-persian.php")) {
                if ($this->checkDateFormat($date) === "Jalali") {
                    $date = $this->jalali_to_gregorian($date);
                }
            }

            if ($reference === null)
                $reference = $id_order;

            $order_shipping_method = "";
            foreach ($order->get_items('shipping') as $item)
                $order_shipping_method = $item->get_name();

            //direct access
//	    $note = $order->customer_note;
            $note = $order->get_customer_note();
            if ($order_shipping_method)
                $note .= "\n" . __('Shipping method', 'ssbhesabfa') . ": " . $order_shipping_method;

            global $freightOption, $freightItemCode;
            $freightOption = get_option("ssbhesabfa_invoice_freight");

            if($freightOption == 1) {
                $freightItemCode = get_option('ssbhesabfa_invoice_freight_code');
                if(!isset($freightItemCode) || !$freightItemCode) HesabfaLogService::writeLogStr("کد هزینه حمل و نقل تعریف نشده است" . "\n" . "Freight service code is not set");

                $freightItemCode = $this->convertPersianDigitsToEnglish($freightItemCode);

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
            $hesabfa = new Ssbhesabfa_Api();

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
                'Freight' => 0
            );

            if(isset($contactCode)) {
                $contactInfo = $hesabfa->contactGet($contactCode);
                if($contactInfo->Success) {
                    $data["ContactTitle"] = $contactInfo->Result->Name;
                }
            }

            if($freightOption == 0) {
                $freight = $this->getPriceInHesabfaDefaultCurrency($order->get_shipping_total() + $order->get_shipping_tax());
                $data['Freight'] = $freight;
            }

            if($freightOption == 2) {
                $api = new Ssbhesabfa_Api();
                $freightContactCode = get_option('ssbhesabfa_invoice_freight_contact_code');
                if($freightContactCode == '')
                    HesabfaLogService::writeLogStr("کد شخص را برای حمل و نقل وارد نمایید");

                $freightContactCode = $this->convertPersianDigitsToEnglish($freightContactCode);
                $freightContact = $api->contactGet($freightContactCode);

                if($freightContact->Status === false)
                    HesabfaLogService::log(array("شخص " . $freightContactCode . " در حسابفا یافت نشد."));
                else {
                    $data['FreightPersonCode'] = $freightContact->Result->Code;
                    $freight = $this->getPriceInHesabfaDefaultCurrency($order->get_shipping_total() + $order->get_shipping_tax());
                    $data['Freight'] = $freight;
                }
            }

            $invoice_draft_save = get_option('ssbhesabfa_invoice_draft_save_in_hesabfa', 'no');
            if ($invoice_draft_save != 'no')
                $data['Status'] = 0;

            $invoice_project = get_option('ssbhesabfa_invoice_project', -1);
            $invoice_salesman = get_option('ssbhesabfa_invoice_salesman', -1);
            $invoice_salesman_percentage = get_option('ssbhesabfa_invoice_salesman_percentage', 0);
            if ($invoice_project != -1) $data['Project'] = $invoice_project;
            if ($invoice_salesman != -1) $data['SalesmanCode'] = $invoice_salesman;
            if($invoice_salesman_percentage) if($invoice_salesman_percentage != 0) $data['SalesmanPercent'] = $this->convertPersianDigitsToEnglish($invoice_salesman_percentage);

            $GUID = $this->getGUID($id_order);

            $response = $hesabfa->invoiceSave($data, $GUID);

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
                    $result = $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array(
                        'id_hesabfa' => (int)$response->Result->Number,
                        'obj_type' => $obj_type,
                        'id_ps' => $id_order,
                    ));
                    if(!$result && gettype($result) == "boolean")
                        HesabfaLogService::log(array("Error in saving invoice in database. Invoice number: " . (string)$response->Result->Number . ". Order ID: $id_order"));
                    HesabfaLogService::log(array("Invoice successfully added. Invoice number: " . (string)$response->Result->Number . ". Order ID: $id_order"));
                } else {
                    $wpFaId = $wpFaService->getWpFaId($obj_type, $id_order);

                    $result = $wpdb->update($wpdb->prefix . 'ssbhesabfa', array(
                        'id_hesabfa' => (int)$response->Result->Number,
                        'obj_type' => $obj_type,
                        'id_ps' => $id_order,
                    ), array('id' => $wpFaId));

                    if(!$result && gettype($result) == "boolean")
                        HesabfaLogService::log(array("Error in updating row in database. Invoice number: " . (string)$response->Result->Number . ". Order ID: $id_order"));
                    HesabfaLogService::log(array("Invoice successfully updated. Invoice number: " . (string)$response->Result->Number . ". Order ID: $id_order"));
                }

                $warehouse = get_option('ssbhesabfa_item_update_quantity_based_on', "-1");
                if ($warehouse != "-1" && $orderType === 0)
                    $this->setWarehouseReceipt($invoiceItems, (int)$response->Result->Number, $warehouse, $date, $invoice_project);

                if($warehouse != "-1" && $orderType == 2)
                    $this->setReceivingWarehouseReceipt($invoiceItems, (int)$response->Result->Number, $warehouse, $date, $invoice_project);

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
        catch (Exception $e) {
            HesabfaLogService::log(array("Error in setting order. Error: " . $e->getMessage()));
        }
        return false;
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
    public function setReceivingWarehouseReceipt($items, $invoiceNumber, $warehouseCode, $date, $project)
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
            'InvoiceType' => 2,
            'Date' => $date,
            'Items' => $items,
	        'Receiving' => true
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
        try {
            if (!isset($id_order)) {
                return false;
            }

            $hesabfa = new Ssbhesabfa_Api();
            $number = $this->getInvoiceCodeByOrderId($id_order);
            if (!$number)
                return false;

            //$order = new WC_Order($id_order);
            $order = wc_get_order($id_order);

            if ($order->get_total() <= 0)
                return true;

            $transaction_id = $order->get_transaction_id();
            //transaction id cannot be null or empty
            if ($transaction_id == '') {
                $transaction_id = '-';
            }

            global $financialData;
            if(get_option('ssbhesabfa_payment_option') == 'no') {
                $bank_code = $this->getBankCodeByPaymentMethod($order->get_payment_method());
                $isPosPluginActive = get_option("ssbhesabfa_woocommerce_point_of_sale_active");
                if($order->get_payment_method() == '' && $isPosPluginActive == '1')
                    $bank_code = $this->getBankCodeByPaymentMethod('pos');

                if ($bank_code != false) {
                    $payTempValue = substr($bank_code, 0, 4);

                    switch($payTempValue) {
                        case 'bank':
                            $payTempValue = substr($bank_code, 4);
                            $financialData = array('bankCode' => $payTempValue);break;
                        case 'cash':
                            $payTempValue = substr($bank_code, 4);
                            $financialData = array('cashCode' => $payTempValue);break;
                    }
                } else {
                    HesabfaLogService::log(array("Cannot add Hesabfa Invoice payment - Bank Code not defined. Order ID: $id_order"));
                    return false;
                }
            } elseif (get_option('ssbhesabfa_payment_option') == 'yes') {
                $defaultBankCode = $this->convertPersianDigitsToEnglish(get_option('ssbhesabfa_default_payment_method_code'));
                if($defaultBankCode != false) {
                    $financialData = array('bankCode' => $defaultBankCode);
                } else {
                    HesabfaLogService::writeLogStr("Default Bank Code is not Defined");
                    return false;
                }
            }

            $date_obj = $order->get_date_paid();
            if ($date_obj == null) {
                $date_obj = $order->get_date_modified();
            }

            global $accountPath;

            if(get_option("ssbhesabfa_cash_in_transit") == "1" || get_option("ssbhesabfa_cash_in_transit") == "yes") {
                $func = new Ssbhesabfa_Admin_Functions();
                $cashInTransitFullPath = $func->getCashInTransitFullPath();
                if(!$cashInTransitFullPath) {
                    HesabfaLogService::writeLogStr("Cash in Transit is not Defined in Hesabfa ---- وجوه در راه در حسابفا یافت نشد");
                    return false;
                } else {
                    $accountPath = array("accountPath" => $cashInTransitFullPath);
                }
            }

            $response = $hesabfa->invoiceGet($number);

            if ($response->Success) {
                $orderPaymentsNotToSubmit = ["cod", "cheque"];
                if(in_array($order->get_payment_method(), $orderPaymentsNotToSubmit)) {
                    if(get_option("ssbhesabfa_submit_receipt_card") == "yes") {
                        $this->submitPayment($response, $order, $number, $financialData, $accountPath, $date_obj, $id_order, $transaction_id);
                    }
                } else {
                    $this->submitPayment($response, $order, $number, $financialData, $accountPath, $date_obj, $id_order, $transaction_id);
                }
                return true;
            } else {
                HesabfaLogService::log(array("Error while trying to get invoice. Invoice Number: $number. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . "."));
                return false;
            }
        } catch(Exception $e) {
            HesabfaLogService::log(array("Error Occurred. Error: " . $e->getMessage()));
            return false;
        }
    }
//========================================================================================================================
	public function submitPayment($response, $order, $number, $financialData, $accountPath, $date_obj, $id_order, $transaction_id): void {
        try {
            $hesabfa = new Ssbhesabfa_Api();
            if ($response->Result->Paid > 0) {
                // payment submited before
                if(get_option('ssbhesabfa_delete_old_receipt') == 'yes') {
                    $receipt = $hesabfa->getReceipts($response->Result->Number);
                    if($receipt->Success) {
                        if($receipt->Result->FilteredCount > 0) {
                            $isDeleted = $hesabfa->deleteReceipt($receipt->Result->List[0]->Number);
                            if($isDeleted->Success) {
                                HesabfaLogService::log(array("Receipt Deleted. Receipt Number: " . $receipt->Result->List[0]->Number));
                                $this->savePayment($order, $number, $financialData, $accountPath, $date_obj, $id_order, $transaction_id);
                            }
                            else HesabfaLogService::log(array("Can't Delete Receipt. Receipt Number: " . $receipt->Result->List[0]->Number));
                        }
                    } else {
                        HesabfaLogService::log(array('Error getting invoice receipts. Error Message: ' . $response->ErrorMessage . ', Error code: ' . $response->ErrorCode . ', invoice number: ' . $response->Result->Number));
                    }
                }
            } else {
                $this->savePayment($order, $number, $financialData, $accountPath, $date_obj, $id_order, $transaction_id);
            }
        } catch (Exception $e) {
            HesabfaLogService::log(array("Error Occurred. Error: " . $e->getMessage()));
        }
	}
//========================================================================================================================
    public function savePayment($order, $number, $financialData, $accountPath, $date_obj, $id_order, $transaction_id): bool {
        try {
            $hesabfa = new Ssbhesabfa_Api();
            $paymentMethod = $order->get_payment_method();
            $transactionFee = 0;
            if(isset($paymentMethod)) {
                if(get_option("ssbhesabfa_payment_transaction_fee_$paymentMethod") > 0) $transactionFee = $this->formatTransactionFee(get_option("ssbhesabfa_payment_transaction_fee_$paymentMethod"), $this->getPriceInHesabfaDefaultCurrency($order->get_total()));
                else $transactionFee = $this->formatTransactionFee(get_option("ssbhesabfa_invoice_transaction_fee"), $this->getPriceInHesabfaDefaultCurrency($order->get_total()));
            }

            if(isset($transactionFee) && $transactionFee != null) $response = $hesabfa->invoiceSavePayment($number, $financialData, $accountPath, $date_obj->date('Y-m-d H:i:s'), $this->getPriceInHesabfaDefaultCurrency($order->get_total()), $transaction_id,'', $transactionFee);
            else $response = $hesabfa->invoiceSavePayment($number, $financialData, $accountPath, $date_obj->date('Y-m-d H:i:s'), $this->getPriceInHesabfaDefaultCurrency($order->get_total()), $transaction_id,'', 0);

            if ($response->Success) {
                HesabfaLogService::log(array("Hesabfa invoice payment added. Order ID: $id_order"));
                return true;
            } else {
                HesabfaLogService::log(array("Cannot add Hesabfa Invoice payment. Order ID: $id_order. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . "."));
                return false;
            }
        } catch (Exception $e) {
            HesabfaLogService::log(array("Error Occurred. Error: " . $e->getMessage()));
            return false;
        }
    }
//========================================================================================================================
    public function getCashInTransitFullPath() {
        $api = new Ssbhesabfa_Api();
        $accounts = $api->settingGetAccounts();
        foreach ($accounts->Result as $account) {
            if($account->Name == "وجوه در راه") {
                return $account->FullPath;
            }
        }
        return false;
    }
//========================================================================================================================
    public function getInvoiceNumberByOrderId($id_order)
    {
        if (!isset($id_order)) return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id_hesabfa` FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_ps` = $id_order AND `obj_type` = 'order' AND active = '1'");

        if (is_object($row)) {
            return $row->id_hesabfa;
        } else {
            return false;
        }
    }
//========================================================================================================================
    public function getBankCodeByPaymentMethod($payment_method)
    {
        if($payment_method == "pos_chip_and_pin")
            $payment_method = "pos";
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
        $row = $wpdb->get_row("SELECT `id_hesabfa` FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_ps` = $id_order AND `obj_type` = 'order' AND active = '1'");

        if (is_object($row)) {
            return $row->id_hesabfa;
        } else {
            return false;
        }
    }
//========================================================================================================================
    public function exportProducts($batch, $totalBatch, $total, $updateCount)
    {
        HesabfaLogService::writeLogStr("Exporting Products");
        try {
            $wpFaService = new HesabfaWpFaService();
            $extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_export_products");
            $rpp=500;
            if($extraSettingRPP) {
                if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                    $rpp=$extraSettingRPP;
                }
            }

            $result = array();
            $result["error"] = false;
            global $wpdb;

            if ($batch == 1) {
	            $total = $wpdb->get_var(
		            $wpdb->prepare(
			            "SELECT COUNT(*) FROM {$wpdb->posts}
						        WHERE post_type = 'product' AND post_status IN ('publish', 'private')"
		            )
	            );

                $totalBatch = ceil($total / $rpp);
            }

            $offset = ($batch - 1) * $rpp;

	        $products = $wpdb->get_results(
		        $wpdb->prepare(
			        "SELECT ID FROM {$wpdb->posts}
				        WHERE post_type = 'product' AND post_status IN ('publish', 'private')
				        ORDER BY ID ASC
				        LIMIT %d, %d",
				        $offset,
				        $rpp
		        )
	        );

            $items = array();

            foreach ($products as $item) {
                $id_product = $item->ID;
                $product = new WC_Product($id_product);

                $id_obj = $wpFaService->getWpFaId('product', $id_product, 0, 1);

                if (!$id_obj) {
                    $hesabfaItem = ssbhesabfaItemService::mapProduct($product, $id_product);
					if(!$hesabfaItem["SellPrice"])
						$hesabfaItem["SellPrice"] = 0;
	                array_push($items, $hesabfaItem);
                    $updateCount++;
                }

                $variations = $this->getProductVariations($id_product);
                if ($variations) {
                    foreach ($variations as $variation) {
                        $id_attribute = $variation->get_id();
                        $id_obj = $wpFaService->getWpFaId('product', $id_product, $id_attribute, 1);

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
                    HesabfaLogService::log(array("Cannot add bulk item. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . "."));
                }
                sleep(2);
            }

            $result["batch"] = $batch;
            $result["totalBatch"] = $totalBatch;
            $result["total"] = $total;
            $result["updateCount"] = $updateCount;
            return $result;
        } catch(Error $error) {
            HesabfaLogService::writeLogStr("Error in export products: " . $error->getMessage());
        }
    }
//========================================================================================================================
    public function importProducts($batch, $totalBatch, $total, $updateCount)
    {
        HesabfaLogService::writeLogStr("Import Products");
        try {
            $wpFaService = new HesabfaWpFaService();
            $extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_import_products");

            $rpp=100;
            if($extraSettingRPP) {
                if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                    $rpp=$extraSettingRPP;
                }
            }

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
                if(!is_object($response)) {
                    HesabfaLogService::writeLogStr("Couldn't get response from hesabfa");
                }
                $items = $response->Result->List;
                $from = $response->Result->From;
                $to = $response->Result->To;

                foreach ($items as $item) {
                    if (!is_object($item)) {
                        HesabfaLogService::log("Error: Invalid item object.");
                        continue;
                    }

                    $wpFa = $wpFaService->getWpFaByHesabfaId('product', $item->Code);
                    if ($wpFa) continue;

                    $clearedName = preg_replace("/\s+|\/|\\\|\(|\)/", '-', trim($item->Name));
                    $clearedName = preg_replace("/\-+/", '-', $clearedName);
                    $clearedName = trim($clearedName, '-');
                    $clearedName = preg_replace(["/۰/", "/۱/", "/۲/", "/۳/", "/۴/", "/۵/", "/۶/", "/۷/", "/۸/", "/۹/"],
                        ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"], $clearedName);

                    // add product to database
	                $wpdb->insert($wpdb->posts, array(
		                'post_author'           => get_current_user_id(),
		                'post_date'             => current_time('mysql'),
		                'post_date_gmt'         => current_time('mysql', 1),
		                'post_content'          => '',
		                'post_title'            => $item->Name,
		                'post_excerpt'          => '',
		                'post_status'           => 'private',
		                'comment_status'        => 'open',
		                'ping_status'           => 'closed',
		                'post_password'         => '',
		                'post_name'             => $clearedName,
		                'to_ping'               => '',
		                'pinged'                => '',
		                'post_modified'         => current_time('mysql'),
		                'post_modified_gmt'     => current_time('mysql', 1),
		                'post_content_filtered' => '',
		                'post_parent'           => 0,
		                'guid'                  => home_url('/product/' . $clearedName . '/'),
		                'menu_order'            => 0,
		                'post_type'             => 'product',
		                'post_mime_type'        => '',
		                'comment_count'         => 0,
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
                        'active' => 1
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
        } catch(Error $error) {
            HesabfaLogService::writeLogStr("Error in importing products" . $error->getMessage() . "\n" . $error);
        }
    }
//========================================================================================================================
//    public function exportOpeningQuantity($batch, $totalBatch, $total)
//    {
//        try {
//            $wpFaService = new HesabfaWpFaService();
//
//            $result = array();
//            $result["error"] = false;
//            $extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_export_opening_products");
//
//            $rpp=500;
//            if($extraSettingRPP) {
//                if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
//                    $rpp=$extraSettingRPP;
//                }
//            }
//
//            global $wpdb;
//
//	        if ($batch == 1) {
//		        $total = $wpdb->get_var(
//			        $wpdb->prepare(
//				        "SELECT COUNT(*) FROM {$wpdb->posts}
//            					WHERE post_type = 'product' AND post_status IN ('publish', 'private')"
//			        )
//		        );
//		        $totalBatch = ceil($total / $rpp);
//	        }
//
//            $offset = ($batch - 1) * $rpp;
//
//	        $products = $wpdb->get_results(
//		        $wpdb->prepare(
//			        "SELECT ID FROM {$wpdb->posts}
//					        WHERE post_type = 'product' AND post_status IN ('publish', 'private')
//					        ORDER BY ID ASC
//					        LIMIT %d, %d",
//			        $offset,
//			        $rpp
//		        )
//	        );
//
//            $items = array();
//
//            foreach ($products as $item) {
//                $variations = $this->getProductVariations($item->ID);
//                if (!$variations) {
//                    $id_obj = $wpFaService->getWpFaId('product', $item->ID, 0);
//
//                    if ($id_obj != false) {
//                        $product = new WC_Product($item->ID);
//                        $quantity = $product->get_stock_quantity();
//                        $price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
//
//                        $row = $wpdb->get_row("SELECT `id_hesabfa` FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id` = " . $id_obj . " AND `obj_type` = 'product'");
//
//                        if (is_object($product) && is_object($row) && $quantity > 0 && $price > 0) {
//                            array_push($items, array(
//                                'Code' => $row->id_hesabfa,
//                                'Quantity' => $quantity,
//                                'UnitPrice' => $this->getPriceInHesabfaDefaultCurrency($price),
//                            ));
//                        }
//                    }
//                } else {
//                    foreach ($variations as $variation) {
//                        $id_attribute = $variation->get_id();
//                        $id_obj = $wpFaService->getWpFaId('product', $item->ID, $id_attribute);
//                        if ($id_obj != false) {
//                            $quantity = $variation->get_stock_quantity();
//                            $price = $variation->get_regular_price() ? $variation->get_regular_price() : $variation->get_price();
//
//                            $row = $wpdb->get_row("SELECT `id_hesabfa` FROM `" . $wpdb->prefix . "ssbhesabfa` WHERE `id` = " . $id_obj . " AND `obj_type` = 'product'");
//
//                            if (is_object($variation) && is_object($row) && $quantity > 0 && $price > 0) {
//                                array_push($items, array(
//                                    'Code' => $row->id_hesabfa,
//                                    'Quantity' => $quantity,
//                                    'UnitPrice' => $this->getPriceInHesabfaDefaultCurrency($price),
//                                ));
//                            }
//                        }
//                    }
//                }
//            }
//
//            if (!empty($items)) {
//                $hesabfa = new Ssbhesabfa_Api();
//                $response = $hesabfa->itemUpdateOpeningQuantity($items);
//                if ($response->Success) {
//                    // continue batch loop
//                } else {
//                    HesabfaLogService::log(array("ssbhesabfa - Cannot set Opening quantity. Error Code: ' . $response->ErrorCode . '. Error Message: ' . $response->ErrorMessage"));
//                    $result['error'] = true;
//                    if ($response->ErrorCode = 199 && $response->ErrorMessage == 'No-Shareholders-Exist') {
//                        $result['errorType'] = 'shareholderError';
//                        return $result;
//                    }
//                    return $result;
//                }
//            }
//            sleep(2);
//            $result["batch"] = $batch;
//            $result["totalBatch"] = $totalBatch;
//            $result["total"] = $total;
//            $result["done"] = $batch == $totalBatch;
//            return $result;
//        } catch(Error $error) {
//            HesabfaLogService::log(array("Error in Exporting Opening Quantity" . $error->getMessage()));
//        }
//    }
	public function exportOpeningQuantity()
	{
		try {
			$wpFaService = new HesabfaWpFaService();
			$hesabfa = new Ssbhesabfa_Api();
			global $wpdb;

			$result = array();
			$result["error"] = false;

			$extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_export_opening_products");
			$rpp = ($extraSettingRPP && $extraSettingRPP != '-1' && $extraSettingRPP != '0') ? $extraSettingRPP : 500;

			$total = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'product' AND post_status IN ('publish', 'private')"
			);
			$totalBatch = ceil($total / $rpp);

			$allItems = array();

			for ($batch = 1; $batch <= $totalBatch; $batch++) {
				$offset = ($batch - 1) * $rpp;

				$products = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
                     WHERE post_type = 'product' AND post_status IN ('publish', 'private')
                     ORDER BY ID ASC
                     LIMIT %d, %d",
						$offset,
						$rpp
					)
				);

				foreach ($products as $item) {
					$variations = $this->getProductVariations($item->ID);

					if (!$variations) {
						$id_obj = $wpFaService->getWpFaId('product', $item->ID, 0);

						if ($id_obj != false) {
							$product = new WC_Product($item->ID);
							$quantity = $product->get_stock_quantity();
							$price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();

							$row = $wpdb->get_row("SELECT `id_hesabfa` FROM `{$wpdb->prefix}ssbhesabfa` WHERE `id` = {$id_obj} AND `obj_type` = 'product'");

							if (is_object($product) && is_object($row) && $quantity > 0 && $price > 0) {
								$allItems[] = array(
									'Code' => $row->id_hesabfa,
									'Quantity' => $quantity,
									'UnitPrice' => $this->getPriceInHesabfaDefaultCurrency($price),
								);
							}
						}
					} else {
						foreach ($variations as $variation) {
							$id_attribute = $variation->get_id();
							$id_obj = $wpFaService->getWpFaId('product', $item->ID, $id_attribute);

							if ($id_obj != false) {
								$quantity = $variation->get_stock_quantity();
								$price = $variation->get_regular_price() ? $variation->get_regular_price() : $variation->get_price();

								$row = $wpdb->get_row("SELECT `id_hesabfa` FROM `{$wpdb->prefix}ssbhesabfa` WHERE `id` = {$id_obj} AND `obj_type` = 'product'");

								if (is_object($variation) && is_object($row) && $quantity > 0 && $price > 0) {
									$allItems[] = array(
										'Code' => $row->id_hesabfa,
										'Quantity' => $quantity,
										'UnitPrice' => $this->getPriceInHesabfaDefaultCurrency($price),
									);
								}
							}
						}
					}
				}
				sleep(1);
			}

			if (!empty($allItems)) {
				$response = $hesabfa->itemUpdateOpeningQuantity($allItems);

				if (!$response->Success) {
					HesabfaLogService::log(array("ssbhesabfa - Cannot set Opening quantity. Error Code: " . $response->ErrorCode . ". Error Message: " . $response->ErrorMessage));
					$result['error'] = true;
					if ($response->ErrorCode == 199 && $response->ErrorMessage == 'No-Shareholders-Exist') {
						$result['errorType'] = 'shareholderError';
					}
				}
			}

			$result["total"] = $total;
			$result["totalBatch"] = $totalBatch;
			$result["done"] = true;
			return $result;

		} catch (Error $error) {
			HesabfaLogService::log(array("Error in Exporting Opening Quantity: " . $error->getMessage()));
		}
	}

//========================================================================================================================
    public function exportCustomers($batch, $totalBatch, $total, $updateCount)
    {
        HesabfaLogService::writeLogStr("Export Customers");
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
    public function syncOrders($from_date, $end_date, $batch, $totalBatch, $total, $updateCount)
    {
        HesabfaLogService::writeLogStr("Sync Orders");
        $wpFaService = new HesabfaWpFaService();

        $result = array();
        $result["error"] = false;
        $rpp = 10;
        global $wpdb;

        if (!isset($from_date) || empty($from_date)) {
            $result['error'] = 'inputDateError';
            return $result;
        }

        if (!isset($end_date) || empty($end_date)) {
            $result['error'] = 'inputDateError';
            return $result;
        }

        if (!$this->isDateInFiscalYear($from_date)) {
            $result['error'] = 'fiscalYearError';
            return $result;
        }

        if (!$this->isDateInFiscalYear($end_date)) {
            $result['error'] = 'fiscalYearError';
            return $result;
        }

        if ($batch == 1) {
            if (get_option('woocommerce_custom_orders_table_enabled') === 'yes') {
                $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "wc_orders`
                                   WHERE type = 'shop_order' AND date_created_gmt >= '" . $from_date . "' AND date_created_gmt <= '". $end_date ."'");
            } else {
                $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`
                                WHERE post_type = 'shop_order' AND post_date >= '" . $from_date . "' AND post_date <= '". $end_date ."'");
            }
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;

        if (get_option('woocommerce_custom_orders_table_enabled') === 'yes') {
          $orders = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "wc_orders`
            WHERE type = 'shop_order' AND date_created_gmt >= '" . $from_date . "'
            AND date_created_gmt <= '". $end_date ."'
            ORDER BY ID ASC LIMIT $offset,$rpp");
        } else {
            $orders = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`
                WHERE post_type = 'shop_order' AND post_date >= '" . $from_date . "'
                AND post_date <= '". $end_date ."'
                ORDER BY ID ASC LIMIT $offset,$rpp");
        }

        HesabfaLogService::writeLogStr("Orders count: " . count($orders));

        $statusesToSubmitInvoice = get_option('ssbhesabfa_invoice_status');
        $statusesToSubmitInvoice = implode(',', $statusesToSubmitInvoice);
        $statusesToSubmitReturnInvoice = get_option('ssbhesabfa_invoice_return_status');
        $statusesToSubmitReturnInvoice = implode(',', $statusesToSubmitReturnInvoice);
        $statusesToSubmitPayment = get_option('ssbhesabfa_payment_status');
        $statusesToSubmitPayment = implode(',', $statusesToSubmitPayment);

        $id_orders = array();
        foreach ($orders as $order) {
            //$order = new WC_Order($order->ID);

            //direct access
            $order = wc_get_order($order->ID);
//            $order = wc_get_order($order->get_id());

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
            else {
                if (strpos($statusesToSubmitInvoice, $current_status) !== false) {
                    if ($this->setOrder($id_order)) {
                        array_push($id_orders, $id_order);
                        $updateCount++;
                    }
                }

                if (strpos($statusesToSubmitPayment, $current_status) !== false)
                    $this->setOrderPayment($id_order);
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
            HesabfaLogService::writeLogStr("Sync products price and quantity from hesabfa to store: part $batch");
            $result = array();
            $result["error"] = false;
            $extraSettingRPP = get_option("ssbhesabfa_set_rpp_for_sync_products_into_woocommerce");

            $rpp=200;
            if($extraSettingRPP) {
                if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                    $rpp=$extraSettingRPP;
                }
            }

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
        HesabfaLogService::writeLogStr('Sync Products Manually');

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
        HesabfaLogService::writeLogStr("Update Products In Hesabfa Based On Store");
        $result = array();
        $result["error"] = false;
        $extraSettingRPP = get_option('ssbhesabfa_set_rpp_for_sync_products_into_hesabfa');

        $rpp=500;
        if($extraSettingRPP) {
            if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                $rpp=$extraSettingRPP;
            }
        }

        global $wpdb;

        if ($batch == 1) {
            $total = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts}
                    WHERE post_type = 'product' AND post_status IN ('publish', 'private')"
                )
            );
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'product' AND post_status IN ('publish', 'private')
                ORDER BY ID ASC
                LIMIT %d, %d",
                $offset,
                $rpp
            )
        );

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
        HesabfaLogService::writeLogStr("Update Products With Filter In Hesabfa Based On Store");
        $result = array();
        $result["error"] = false;

        global $wpdb;
        if($offset != 0 && $rpp != 0) {
            if(abs($rpp - $offset) <= 200) {
                if($rpp > $offset) {
                    $products = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->posts}
                            WHERE ID BETWEEN %d AND %d
                            AND post_type = 'product'
                            AND post_status IN ('publish', 'private')
                            ORDER BY ID ASC",
                            $offset,
                            $rpp
                        )
                    );

                    $products_id_array = array();
                    foreach ($products as $product)
                        $products_id_array[] = $product->ID;
                    $response = (new Ssbhesabfa_Admin_Functions)->setItems($products_id_array);
                    if(!$response) $result['error'] = true;
                } else {
                    $products = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->posts}
                            WHERE ID BETWEEN %d AND %d
                            AND post_type = 'product'
                            AND post_status IN ('publish', 'private')
                            ORDER BY ID ASC",
                            $rpp,
                            $offset
                        )
                    );

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

//        if ($item->Quantity || !$item->Stock)
//            $item->Stock = $item->Quantity;

        if (isset($item->Quantity) && (!isset($item->Stock) || !$item->Stock)) {
            $item->Stock = $item->Quantity;
        }

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

        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                WHERE ID = %d",
                $id_product
            )
        );


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
    private static function setItemNewPrice($product, $item, $id_attribute, $id_product, array $result)
    {
        try {
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
        } catch (Error $error) {
            HesabfaLogService::writeLogStr("Error in Set Item New Price -> $error");
        }
    }
//========================================================================================================================
    private static function setItemNewQuantity($product, $item, $id_product, $id_attribute, array $result)
    {
        try {
            $old_quantity = $product->get_stock_quantity();
            if ($item->Stock != $old_quantity) {
                $new_quantity = $item->Stock;
                if (!$new_quantity) $new_quantity = 0;

                $new_stock_status = ($new_quantity > 0) ? "instock" : "outofstock";

                $post_id = ($id_attribute && $id_attribute > 0) ? $id_attribute : $id_product;

                $product = wc_get_product( $post_id );
                if ( $product ) {

                    $product->set_stock_quantity( $new_quantity );
                    $product->set_stock_status( $new_stock_status );
                    $product->save();
                    HesabfaLogService::log(array("product ID $id_product-$id_attribute quantity changed. Old quantity: $old_quantity. New quantity: $new_quantity"));
                    $result["newQuantity"] = $new_quantity;
                }
            }

            return $result;
        } catch (Error $error) {
            HesabfaLogService::writeLogStr("Error in Set Item New Quantity -> $error");
        }
    }
//=========================================================================================================================
    public static function syncLastChangeID(): bool {
        try {
            HesabfaLogService::writeLogStr("Sync Last Change ID");
            $hesabfaApi = new Ssbhesabfa_Api();
            $lastChange = $hesabfaApi->getLastChangeId();

            if ($lastChange && isset($lastChange->LastId)) {
                update_option('ssbhesabfa_last_log_check_id', $lastChange->LastId - 1);
                return true;
            }
        } catch (Exception $error) {
            HesabfaLogService::writeLogStr("Error in syncing last change id -> " . $error->getMessage());
        }

        return false;
    }
//=========================================================================================================================
    public static function deleteInvoicesOptions(): bool {
        try {
            HesabfaLogService::writeLogStr("Delete Invoices Options");
            $wpFa = new HesabfaWpFaService();
            return $wpFa->deleteInvoicesOptions();
        } catch (Exception $error) {
            HesabfaLogService::writeLogStr("Error in deleting invoices options -> " . $error->getMessage());
        }

        return false;
    }
//=========================================================================================================================
    public static function SaveProductManuallyToHesabfa($woocommerceCode, $attributeId, $hesabfaCode): bool {
        //check no record exist in hesabfa
        $isProductExistInHesabfa = self::CheckExistenceOfTheProductInHesabfa($hesabfaCode);
        if(!$isProductExistInHesabfa) {
            $isProductValidInWoocommerce = self::CheckValidityOfTheProductInWoocommerce($woocommerceCode, $attributeId, $hesabfaCode);
            if($isProductValidInWoocommerce) {
                //get product
                $product = wc_get_product($woocommerceCode);
                if($attributeId != 0) $variation = wc_get_product($attributeId);

                if($attributeId == 0) {
                    $hesabfaItem = ssbhesabfaItemService::mapProduct($product, $woocommerceCode);
                } else {
                    $hesabfaItem = ssbhesabfaItemService::mapProductVariation($product, $variation, $woocommerceCode);
                }

                //save product to hesabfa and make a new link
                $api = new Ssbhesabfa_Api();
                $hesabfaItem["Code"] = $hesabfaCode;
                $response = $api->itemSave($hesabfaItem);
                if($response->Success) {
                    if($attributeId == 0) $productCode = $woocommerceCode; else $productCode = $attributeId;
                    HesabfaLogService::log(array("Item successfully added to Hesabfa. Hesabfa code: " . $hesabfaCode . " - Product code: " . $productCode));

                    $wpFaService = new HesabfaWpFaService();
                    $wpFa = $wpFaService->getWpFa('product', $woocommerceCode, $attributeId);
                    if (!$wpFa) {
                        $wpFa = new WpFa();
                        $wpFa->idHesabfa = $hesabfaCode;
                        $wpFa->idWp = $woocommerceCode;
                        $wpFa->idWpAttribute = $attributeId;
                        $wpFa->objType = 'product';
                        $wpFaService->save($wpFa);
                        HesabfaLogService::log(array("Item successfully added. Hesabfa code: " . (string)$hesabfaCode . ". Product ID: $woocommerceCode - $attributeId"));
                        return true;
                    }
                } else {
                    HesabfaLogService::log(array("Error in saving product to hesabfa. Hesabfa given code: " . $hesabfaCode));
                    return false;
                }
            }
        }

        return false;
    }
//=========================================================================================================================
    public static function CheckExistenceOfTheProductInHesabfa($hesabfaCode): bool {
        $api = new Ssbhesabfa_Api();
        $response = $api->itemGet($hesabfaCode);
        if($response->Success) {
            HesabfaLogService::writeLogStr("کالا با کد(" .  $hesabfaCode . ") در حسابفا موجود است.");
            return true;
        } else if($response->ErrorCode == "112") {
            return false;
        } else {
            HesabfaLogService::writeLogStr("Error in getting the existence of the product");
            return true;
        }
    }
//=========================================================================================================================
    public static function CheckValidityOfTheProductInWoocommerce($woocommerceCode, $attributeId, $hesabfaCode): bool {
        //check not exist in link table
        $wpFaService = new HesabfaWpFaService();
        $code = $wpFaService->getProductCodeByWpId($woocommerceCode, $attributeId);
        if ($code) {
            HesabfaLogService::writeLogStr("این کد حسابفای وارد شده به کالای دیگری متصل است." . $code . " - " . $woocommerceCode . " - " . $attributeId);
            return false;
        }

        //check woocommerce code exists
        global $wpdb;

        if($attributeId != 0) $productId = $attributeId;
        else $productId = $woocommerceCode;

        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                WHERE ID = %d",
                $productId
            )
        );

        if($found) {
            //product is valid
            return true;
        } else {
            HesabfaLogService::writeLogStr("product not found in woocommerce. Given product code: " . $woocommerceCode . "-" . $attributeId );
            return false;
        }
    }
//=========================================================================================================================
	function SaveAdditionalFieldsMeta($id_order, $additionalFields) {
		update_post_meta($id_order, '_billing_hesabfa_national_code', $additionalFields['nationalCode']);
		update_post_meta($id_order, '_billing_hesabfa_economic_code', $additionalFields['economicCode']);
		update_post_meta($id_order, '_billing_hesabfa_registeration_number', $additionalFields['registrationNumber']);
		update_post_meta($id_order, '_billing_hesabfa_website', $additionalFields['website']);
		update_post_meta($id_order, '_billing_hesabfa_phone', $additionalFields['phone']);
	}
//=========================================================================================================================
    function checkNationalCode($NationalCode): void
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
    public function checkNationalCodeWithPhone($nationalCode, $billingMobile = ''): void {
		if($billingMobile == '')
			wc_add_notice(__('please enter a valid mobile', 'ssbhesabfa'), 'error');

        $api = new Ssbhesabfa_Api();

		$res = $api->credit();
		if($res->Success) {
			if($res->Result >= 2) {
		        $formattedMobile = $this->convertPersianDigitsToEnglish($billingMobile);
		        $formattedMobile = $this->formatPhoneNumber($formattedMobile);
		
		        $response = $api->checkMobileAndNationalCode($nationalCode, $formattedMobile);
		        if($response->Success) {
		            if($response->Result->Status == 1) {
		                if($response->Result->Data->Matched != 1)
			                wc_add_notice(__("mobile and national code don't match", 'ssbhesabfa'), 'error');
		            }
		        } else {
		            HesabfaLogService::writeLogStr('Error Occurred in Checking Mobile and NationalCode. ErrorCode: ' . $response->ErrorCode . " - ErrorMessage: " . $response->ErrorMessage);
		        }
			} else {
				HesabfaLogService::writeLogStr('Not Enough Tokens For Inquiry - تعداد توکن برای سامانه استعلام ناکافی است');
			}
		}
    }
//=========================================================================================================================
    function checkWebsite($Website): void
    {
        if (filter_var($Website, FILTER_VALIDATE_URL)) {
            //
        } else {
            wc_add_notice(__('please enter a valid Website URL', 'ssbhesabfa'), 'error');
        }
    }
//=========================================================================================================================
    public static function enableDebugMode(): void {
        update_option('ssbhesabfa_debug_mode', 1);
    }

    public static function disableDebugMode(): void {
        update_option('ssbhesabfa_debug_mode', 0);
    }
//=========================================================================================================================
    function formatPhoneNumber($phoneNumber) {
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        if (substr($phoneNumber, 0, 2) == '98') {
            $phoneNumber = substr($phoneNumber, 2);
        }

        if (substr($phoneNumber, 0, 1) == '9' && strlen($phoneNumber) == 10) {
            $phoneNumber = '0' . $phoneNumber;
        }

        if (strlen($phoneNumber) == 10 && substr($phoneNumber, 0, 1) == '9') {
            $phoneNumber = '0' . $phoneNumber;
        }

        return $phoneNumber;
    }
//=========================================================================================================================
    public function convertPersianDigitsToEnglish($inputString) {
        $newNumbers = range(0, 9);
        $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;');
        $arabicDecimal  = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;');
        $arabic  = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

        $string =  str_replace($persianDecimal, $newNumbers, $inputString);
        $string =  str_replace($arabicDecimal, $newNumbers, $string);
        $string =  str_replace($persian, $newNumbers, $string);

        return str_replace($arabic, $newNumbers, $string);
    }
//=========================================================================================================================
    function generateGUID() : string {
        $characters = '0123456789ABCDEF';
        $guid = '';

        for ($i = 0; $i < 32; $i++) {
            $guid .= $characters[mt_rand(0, 15)];
            if ($i == 7 || $i == 11 || $i == 15 || $i == 19) {
                $guid .= '-';
            }
        }

        return $guid;
    }
//=========================================================================================================================
    public function getGUID($id_order): string {
        $option = get_option($id_order);

        if ($option === false || $option == 0) {
            $GUID = $this->generateGUID();
            $expirationDateTime = new DateTime('now', new DateTimeZone('UTC'));
            add_option($id_order, $expirationDateTime->format('Y-m-d H:i:s') . $GUID . '-ssbhesabfa', '', 'no');
        } else {
            $expirationDateTime = new DateTime(substr($option, 0, 19), new DateTimeZone('UTC'));
            $currentDateTime = new DateTime('now', new DateTimeZone('UTC'));

            $diff = $currentDateTime->diff($expirationDateTime);

            if ($diff->days < 1) {
                // GUID is still valid, continue processing
            } else {
                // GUID expired, reset the option to allow saving a new invoice
                $GUID = $this->generateGUID();
                $expirationDateTime = new DateTime('now', new DateTimeZone('UTC'));
                update_option($id_order, $expirationDateTime->format('Y-m-d H:i:s') . $GUID . '-ssbhesabfa', '', 'no');
            }
        }

        return substr(get_option($id_order), 20);
    }
//=========================================================================================================================
    public function formatTransactionFee($transactionFee, $amount) {
        if($transactionFee && $transactionFee > 0) {
            $func = new Ssbhesabfa_Admin_Functions();
            $transactionFee = $func->convertPersianDigitsToEnglish($transactionFee);

            if($transactionFee<100 && $transactionFee>0) $transactionFee /= 100;
            $transactionFee *= $amount;
            if($transactionFee < 1) $transactionFee = 0;
        }
        return $transactionFee;
    }
//=========================================================================================================================
    public function jalali_to_gregorian($jalali_datetime) {
        list($jalali_date, $time) = explode(' ', $jalali_datetime);
        list($jy, $jm, $jd) = explode('-', $jalali_date);

        $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

        $jy -= 979;
        $jm -= 1;
        $jd -= 1;

        $j_day_no = 365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4);

        for ($i = 0; $i < $jm; ++$i) {
            $j_day_no += $j_days_in_month[$i];
        }

        $j_day_no += $jd;

        $g_day_no = $j_day_no + 79;

        $gy = 1600 + 400 * floor($g_day_no / 146097);
        $g_day_no = $g_day_no % 146097;

        $leap = true;

        if ($g_day_no >= 36525)
        {
            $g_day_no--;
            $gy += 100 * floor($g_day_no / 36524);
            $g_day_no = $g_day_no % 36524;

            if ($g_day_no >= 365) {
                $g_day_no++;
            } else {
                $leap = false;
            }
        }

        $gy += 4 * floor($g_day_no / 1461);
        $g_day_no %= 1461;

        if ($g_day_no >= 366) {
            $leap = false;
            $g_day_no--;
            $gy += floor($g_day_no / 365);
            $g_day_no = $g_day_no % 365;
        }

        for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++) {
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
        }

        $gm = $i + 1;
        $gd = $g_day_no + 1;

        $gregorian_datetime = sprintf('%04d-%02d-%02d %s', $gy, $gm, $gd, $time);

        return $gregorian_datetime;
    }
//=========================================================================================================================
    function checkDateFormat($dateStr) {
        $gregorianRegex = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])\s([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/';
        $jalaliRegex = '/^(13|14)\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])\s([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/';

        if (preg_match($jalaliRegex, $dateStr)) {
            return "Jalali";
        }
        elseif (preg_match($gregorianRegex, $dateStr)) {
            return "Gregorian";
        }
        else {
            return "Invalid format";
        }
    }
//=========================================================================================================================
    function get_state_city_term_name( $term_id ) {
        $term_id = (int) $term_id;

        if ( $term_id <= 0 )
            return null;

        $term = get_term( $term_id, 'state_city' );

        if ( is_wp_error( $term ) || ! $term )
            return null;

        return $term->name;
    }
}