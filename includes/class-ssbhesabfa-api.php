<?php

include_once(plugin_dir_path(__DIR__) . 'admin/services/HesabfaLogService.php');

/**
 * @class      Ssbhesabfa_Api
 * @version    2.2.3
 * @since      1.0.0
 * @package    ssbhesabfa
 * @subpackage ssbhesabfa/api
 * @author     Sepehr Najafi <sepehrnm78@yahoo.com>
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 */

class Ssbhesabfa_Api
{
//================================================================================================
    public function apiRequest($method, $data = array())
    {
        if ($method == null) return false;

        $endpoint = 'https://api.hesabfa.com/v1/' . $method;

        $apiAddress = get_option('ssbhesabfa_api_address', 0);

        if($apiAddress == 1) $endpoint = 'http://api.hesabfa.ir/v1/' . $method;

        $body = array_merge(array(
            'apiKey' => get_option('ssbhesabfa_account_api'),
            'userId' => get_option('ssbhesabfa_account_username'),
            'password' => get_option('ssbhesabfa_account_password'),
            'loginToken' => get_option('ssbhesabfa_account_login_token') ? get_option('ssbhesabfa_account_login_token') : '',
        ), $data);

        //Debug mode
        if (get_option('ssbhesabfa_debug_mode')) {
            HesabfaLogService::log(array("Debug Mode - Data: " . print_r($data, true)));
        }

        $options = array(
            'body' => wp_json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 60,
            'redirection' => 5,
            'blocking' => true,
            'httpversion' => '1.0',
            'sslverify' => false,
            'data_format' => 'body',
        );

        $wp_remote_post = wp_remote_post($endpoint, $options);
        $result = json_decode(wp_remote_retrieve_body($wp_remote_post));

        //Debug mode
        if (get_option('ssbhesabfa_debug_mode')) {
            HesabfaLogService::log(array("Debug Mode - Result: " . print_r($result, true)));
        }

        //fix API limit request - Maximum request per minutes is 60 times,
        sleep(1);

        if ($result == null) {
            return 'No response from Hesabfa';
        } else {
            if (!isset($result->Success)) {
                switch ($result->ErrorCode) {
                    case '100':
                        return 'InternalServerError';
                    case '101':
                        return 'TooManyRequests';
                    case '103':
                        return 'MissingData';
                    case '104':
                        return 'MissingParameter' . '. ErrorMessage: ' . $result->ErrorMessage;
                    case '105':
                        return 'ApiDisabled';
                    case '106':
                        return 'UserIsNotOwner';
                    case '107':
                        return 'BusinessNotFound';
                    case '108':
                        return 'BusinessExpired';
                    case '110':
                        return 'IdMustBeZero';
                    case '111':
                        return 'IdMustNotBeZero';
                    case '112':
                        return 'ObjectNotFound' . '. ErrorMessage: ' . $result->ErrorMessage;
                    case '113':
                        return 'MissingApiKey';
                    case '114':
                        return 'ParameterIsOutOfRange' . '. ErrorMessage: ' . $result->ErrorMessage;
                    case '190':
                        return 'ApplicationError' . '. ErrorMessage: ' . $result->ErrorMessage;
                }
            } else {
                return $result;
            }
        }
        return false;
    }
//================================================================================================
    //Contact functions
    public function contactGet($code)
    {
        $method = 'contact/get';
        $data = array(
            'code' => $code,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactGetById($idList)
    {
        $method = 'contact/getById';
        $data = array(
            'idList' => $idList,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactGetContacts($queryInfo)
    {
        $method = 'contact/getcontacts';
        $data = array(
            'queryInfo' => $queryInfo,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactSave($contact)
    {
        $method = 'contact/save';
        $data = array(
            'contact' => $contact,
        );
        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactBatchSave($contacts)
    {
        $method = 'contact/batchsave';
        $data = array(
            'contacts' => $contacts,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactDelete($code)
    {
        $method = 'contact/delete';
        $data = array(
            'code' => $code,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactGetByPhoneOrEmail($phone, $email) {
        $method = 'contact/findByPhoneOrEmail';
        if($phone == '') {
            $data = array(
                'email' => $email,
            );
        } else if($email == '') {
            $data = array(
                'mobile' => $phone,
                'phone' => $phone,
            );
        } else {
            $data = array(
                'mobile' => $phone,
                'email' => $email,
                'phone' => $phone,
            );
        }

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    //Items functions
    public function itemGet($code)
    {
        $method = 'item/get';
        $data = array(
            'code' => $code,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemGetByBarcode($barcode)
    {
        $method = 'item/getByBarcode';
        $data = array(
            'barcode' => $barcode,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemGetById($idList)
    {
        $method = 'item/getById';
        $data = array(
            'idList' => $idList,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemGetItems($queryInfo = null)
    {
        $method = 'item/getitems';
        $data = array(
            'queryInfo' => $queryInfo,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemSave($item)
    {
        $method = 'item/save';
        $data = array(
            'item' => $item,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemBatchSave($items)
    {
        $response = (object) [
            "Success" => "",
            "ErrorCode" => "",
            "ErrorMessage" => ""
        ];

        if(get_option("ssbhesabfa_do_not_submit_product_automatically") == "yes" ||
           get_option("ssbhesabfa_do_not_submit_product_automatically") == 1
        ) {
            $response->Success = false;
            $response->ErrorCode = 100;
            $response->ErrorMessage = "Saving Items Option is off  ------  ذخیره اتوماتیک محصولات غیرفعال است";
            return $response;
        }

        $method = 'item/batchsave';
        $data = array(
            'items' => $items,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemDelete($code)
    {
        $method = 'item/delete';
        $data = array(
            'code' => $code,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemGetQuantity($warehouseCode, $codes)
    {
        $method = 'item/GetQuantity';
        if($warehouseCode != "-1") {
            $data = array(
                'warehouseCode' => $warehouseCode,
                'codes' => $codes,
            );
        } else {
            $data = array(
                'codes' => $codes,
            );
        }

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    //Invoice functions
    public function invoiceGet($number, $type = 0)
    {
        $method = 'invoice/get';
        $data = array(
            'number' => $number,
            'type' => $type,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceGetById($id)
    {
        $method = 'invoice/getById';
        $data = array(
            'id' => $id,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceGetByIdList($idList)
    {
        $method = 'invoice/getById';
        $data = array(
            'idList' => $idList,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceGetInvoices($queryinfo, $type = 0)
    {
        $method = 'invoice/getinvoices';
        $data = array(
            'type' => $type,
            'queryInfo' => $queryinfo,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceSave($invoice, $GUID='')
    {
        $response = [];
        $method = 'invoice/save';
        //check if invoice with this reference exists

        $invoices = $this->invoiceGetInvoices(
            array(
                "SortBy" => "Date",
                "SortDesc" => true,
                "Take" => 1,
                "Skip" => 0,
                "Filters" =>
                array(
                    array(
                        "Property" => "Reference",
                        "Operator" => "=",
                        "Value" => $invoice["Reference"]
                    ),

                    array(
                        "Property" => "Status",
                        "Operator" => "=",
                        "Value" => "1"
                    )
                )
            )
        );

        if(!$invoices->Success) {
            HesabfaLogService::writeLogStr("Cannot Get Invoice Reference");
            $response["Success"] = false;
            return $response;
        }

        if(count($invoices->Result->List) == 1)
            $invoice["Number"] = $invoices->Result->List[0]->Number;

		$data = array(
			'invoice' => $invoice,
		);

		if (!empty($GUID))
			$data['requestUniqueId'] = $GUID;

		if (isset($data['requestUniqueId'])) {
			$this->saveStatistics();
			return $this->apiRequest($method, $data);
		}

		$response["Success"] = false;
		return $response;
    }
//================================================================================================
    public function invoiceDelete($number, $type = 0)
    {
        $method = 'invoice/delete';
        $data = array(
            'code' => $number,
            'type' => $type,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceSavePayment($number, $financialData, $accountPath, $date, $amount, $transactionNumber = null, $description = null, $transactionFee = 0)
    {
        $method = 'invoice/savepayment';
        $data = array(
            'number' => (int)$number,
            'date' => $date,
            'amount' => $amount,
            'transactionNumber' => $transactionNumber,
            'description' => $description,
            'transactionFee' => $transactionFee,
        );

	    if($financialData != null) $data = array_merge($data, $financialData);
        if($accountPath != []) $data = array_merge($data, $accountPath);

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceGetOnlineInvoiceURL($number, $type = 0)
    {
        $method = 'invoice/getonlineinvoiceurl';
        $data = array(
            'number' => $number,
            'type' => $type,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemUpdateOpeningQuantity($items)
    {
        $method = 'item/UpdateOpeningQuantity';
        $data = array(
            'items' => $items,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function saveWarehouseReceipt($receipt) {
        $method = 'invoice/SaveWarehouseReceipt';
        $data = array(
            'deleteOldReceipts' => true,
            'receipt' => $receipt,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function getReceipts($number) {
        $method = 'receipt/getReceipts';
        $data = array(
            'type' => 1,
            'queryInfo' => array('filters' => array(array('Property' => 'Invoice.Number', 'Operator' => '=', 'Value' => (int)$number))),
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function deleteReceipt($number, $type = 1) {
        $method = 'receipt/delete';
        $data = array(
            'type' => $type,
            'number' => $number,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function warehouseReceiptGetByIdList($idList)
    {
        $method = 'invoice/getWarehouseReceipt';
        $data = array(
            'idList' => $idList,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function getWarehouseReceipt($objectId) {
        $method = 'warehouse/GetById';
        $data = array(
            'id' => $objectId,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    //Settings functions
    public function settingSetChangeHook($url, $hookPassword)
    {
        $method = 'setting/SetChangeHook';
        $data = array(
            'url' => $url,
            'hookPassword' => $hookPassword,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function settingGetChanges($start = 0)
    {
        $method = 'setting/GetChanges';
        $data = array(
            'start' => $start,
        );
        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function settingGetAccounts()
    {
        $method = 'setting/GetAccounts';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetBanks()
    {
        $method = 'setting/getBanks';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetCashes()
    {
        $method = 'setting/GetCashes';
        return $this->apiRequest($method);
    }
//================================================================================================
	public function settingGetProjects()
	{
		$method = 'setting/getProjects';
		return $this->apiRequest($method);
	}
//================================================================================================
	public function settingGetSalesmen()
	{
		$method = 'setting/getSalesmen';
		return $this->apiRequest($method);
	}
//================================================================================================
	public function settingGetCurrency()
    {
        $method = 'setting/getCurrency';

        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetFiscalYear()
    {
        $method = 'setting/GetFiscalYear';

        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetWarehouses()
    {
        $method = 'setting/GetWarehouses';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function fixClearTags()
    {
        $method = 'fix/clearTag';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetSubscriptionInfo()
    {
        $method = 'setting/getBusinessInfo';
        return $this->apiRequest($method);
    }
//=========================================================================================================================
    public function getLastChangeId($start = 1000000000) {
        $method = 'setting/GetChanges';
        $data = array(
            'start' => $start,
        );
        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function saveStatistics() {
        $plugin_version = constant('SSBHESABFA_VERSION');

        $endpoint = "https://hesabfa.com/statistics/save";
        $body = array(
            "Platform" => "Woocommerce/" . $plugin_version,
            "Website" => get_site_url(),
            'APIKEY' => get_option('ssbhesabfa_account_api'),
            "IP" => $_SERVER['REMOTE_ADDR']
        );

        $options = array(
            'body' => wp_json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 60,
            'redirection' => 5,
            'blocking' => true,
            'httpversion' => '1.0',
            'sslverify' => false,
            'data_format' => 'body',
        );

        $wp_remote_post = wp_remote_post($endpoint, $options);
        $result = json_decode(wp_remote_retrieve_body($wp_remote_post));
    }
//================================================================================================
    public function checkMobileAndNationalCode($nationalCode, $billingPhone) {
        $method = 'inquiry/checkMobileAndNationalCode';
        $data = array(
            'nationalCode' => $nationalCode,
            'mobile' => $billingPhone,
        );
        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function credit() {
        $method = 'inquiry/credit';
        return $this->apiRequest($method);
    }
//================================================================================================
}