<?php

include_once('HesabfaLogService.php');

class WpFa
{
    public $id;
    public $objType;
    public $idHesabfa;
    public $idWp;
    public $idWpAttribute;

    public function __construct()
    {
    }
}

class HesabfaWpFaService
{
    public function __construct()
    {
    }

    public function getWpFa($objType, $idWp, $idWpAttribute = 0) {
        if (!isset($objType) || !isset($idWp)) {
            return false;
        }

        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_ps` = $idWp AND `id_ps_attribute` = $idWpAttribute AND `obj_type` = '$objType'");

        if(isset($row))
            return $this->mapWpFa($row);
        return null;
    }

    public function getWpFaByHesabfaId($objType, $hesabfaId) {
        if (!isset($objType) || !isset($hesabfaId)) {
            return false;
        }

        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_hesabfa` = $hesabfaId AND `obj_type` = '$objType'");

        if(isset($row))
            return $this->mapWpFa($row);
        return null;
    }

    public function getWpFaId($objType, $idWp, $idWpAttribute = 0) {
        if (!isset($objType) || !isset($idWp)) {
            return false;
        }

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id` FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_ps` = $idWp AND `id_ps_attribute` = $idWpAttribute AND `obj_type` = '$objType'");

        if (is_object($row)) {
            return (int)$row->id;
        } else {
            return false;
        }
    }

    public function getWpFaIdByHesabfaId($objType, $hesabfaId) {
        if (!isset($objType) || !isset($hesabfaId)) {
            return false;
        }

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id` FROM " . $wpdb->prefix . "ssbhesabfa WHERE `id_hesabfa` = $hesabfaId AND `obj_type` = '$objType'");

        if(isset($row))
            return (int)$row->id;
        return null;
    }

    public function getProductCodeByWpId($id_product, $id_attribute = 0)
    {
        $obj = $this->getWpFa('product', $id_product, $id_attribute);
        if($obj != null)
            return $obj->idHesabfa;
        return null;
    }

    public function getCustomerCodeByWpId($id_customer)
    {
        $obj = $this->getWpFa('customer', $id_customer);
        if($obj != null)
            return $obj->idHesabfa;
        return null;
    }

    public function getInvoiceCodeByWpId($id_order)
    {
        $obj = $this->getWpFa('order', $id_order);
        if($obj != null)
            return $obj->idHesabfa;
        return null;
    }


    public function getProductAndCombinations($idWp) {
        $sql = "SELECT * FROM `" . _DB_PREFIX_  . "ps_hesabfa` WHERE `obj_type` = 'product' AND `id_ps` = '$idWp'";
        $result = Db::getInstance()->executeS($sql);

        $psFaObjects = array();
        if(isset($result) && is_array($result) && count($result) > 0)
        {
            foreach ($result as $item) {
                $psFaObjects[] = $this->mapPsFa($item);
            }
            return $psFaObjects;
        }
        return null;
    }

    public function mapWpFa($sqlObj) {
        $wpFa = new WpFa();
        $wpFa->id = $sqlObj->id;
        $wpFa->idHesabfa = $sqlObj->id_hesabfa;
        $wpFa->idWp = $sqlObj->id_ps;
        $wpFa->idWpAttribute = $sqlObj->id_ps_attribute;
        $wpFa->objType = $sqlObj->obj_type;
        return $wpFa;
    }

    public function saveProduct($item) {
        $json = json_decode($item->Tag);
        $id = $this->getPsFaId('product', (int)$json->id_product, (int)$json->id_attribute);

        if ($id == false) {
            Db::getInstance()->insert('ps_hesabfa', array(
                'id_hesabfa' => (int)$item->Code,
                'obj_type' => 'product',
                'id_ps' => (int)$json->id_product,
                'id_ps_attribute' => (int)$json->id_attribute,
            ));
            LogService::writeLogStr("Item successfully added. Item code: " . (string)$item->Code . ". Product ID: $json->id_product-$json->id_attribute");
        } else {
            Db::getInstance()->update('ps_hesabfa', array(
                'id_hesabfa' => (int)$item->Code,
                'obj_type' => 'product',
                'id_ps' => (int)$json->id_product,
                'id_ps_attribute' => (int)$json->id_attribute,
            ), array('id' => $id),0,true,true);
            LogService::writeLogStr("Item successfully updated. Item code: " . (string)$item->Code . ". Product ID: $json->id_product-$json->id_attribute");
        }

        return true;
    }

    public function saveCustomer($customer) {
        $json = json_decode($customer->Tag);
        $id = $this->getPsFaId('customer', (int)$json->id_customer);

        if ($id == false) {
            Db::getInstance()->insert('ps_hesabfa', array(
                'id_hesabfa' => (int)$customer->Code,
                'obj_type' => 'customer',
                'id_ps' => (int)$json->id_customer,
            ));
            LogService::writeLogStr("Customer successfully added. Customer code: " . (string)$customer->Code . ". Customer ID: $json->id_customer");
        } else {
            Db::getInstance()->update('ps_hesabfa', array(
                'id_hesabfa' => (int)$customer->Code,
                'obj_type' => 'customer',
                'id_ps' => (int)$json->id_customer,
            ), array('id' => $id),0,true,true);
            LogService::writeLogStr("Customer successfully updated. Customer code: " . (string)$customer->Code . ". Customer ID: $json->id_customer");
        }

        return true;
    }

    public function saveInvoice($invoice, $orderType) {
        $json = json_decode($invoice->Tag);
        $id = $this->getPsFaId('order', (int)$json->id_order);

        $invoiceNumber = (int)$invoice->Number;
        $objType = $orderType == 0 ? 'order' : 'returnOrder';

        if ($id == false) {
            Db::getInstance()->insert('ps_hesabfa', array(
                'id_hesabfa' => $invoiceNumber,
                'obj_type' => $objType,
                'id_ps' => (int)$json->id_order,
            ));
            if($objType == 'order')
                LogService::writeLogStr("Invoice successfully added. invoice number: " . (string)$invoice->Number . ", order id: " . $json->id_order);
            else
                LogService::writeLogStr("Return Invoice successfully added. Customer code: " . (string)$invoice->Number . ", order id: " . $json->id_order);
        } else {
            Db::getInstance()->update('ps_hesabfa', array(
                'id_hesabfa' => $invoiceNumber,
                'obj_type' => $objType,
                'id_ps' => (int)$json->id_order,
            ), array('id' => $id),0,true,true);
            if($objType == 'order')
                LogService::writeLogStr("Invoice successfully updated. invoice number: " . (string)$invoice->Number . ", order id: " . $json->id_order);
            else
                LogService::writeLogStr("Return Invoice successfully updated. Customer code: " . (string)$invoice->Number . ", order id: " . $json->id_order);
        }

        return true;
    }

    public function save(WpFa $wpFa) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'ssbhesabfa', array(
            'id_hesabfa' => $wpFa->idHesabfa,
            'obj_type' => $wpFa->objType,
            'id_ps' => $wpFa->idWp,
            'id_ps_attribute' => $wpFa->idWpAttribute,
        ));
    }

    public function update(WpFa $wpFa) {
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'ssbhesabfa', array(
            'id_hesabfa' => $wpFa->idHesabfa,
            'obj_type' => $wpFa->objType,
            'id_ps' => (int)$wpFa->idWp,
            'id_ps_attribute' => (int)$wpFa->idWpAttribute,
        ), array('id' => $wpFa->id));
    }

    public function delete($wpFa) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'ssbhesabfa', array('id' => $wpFa->id));
    }
}