<?php

include_once('HesabfaLogService.php');

class WpFa
{
    public $id;
    public $objType;
    public $idHesabfa;
    public $idWp;
    public $idWpAttribute;
    public $active;

    public function __construct() {}

    public static function newWpFa($id, $type, $idHesabfa, $idWp, $idWpAttribute, $active): WpFa
    {
        $instance = new self();

        $instance->id = $id;
        $instance->objType = $type;
        $instance->idHesabfa = $idHesabfa;
        $instance->idWp = $idWp;
        $instance->idWpAttribute = $idWpAttribute;
        $instance->active = $active;

        return $instance;
    }
}

class HesabfaWpFaService
{
    public function __construct() {}

    public function getWpFa($objType, $idWp, $idWpAttribute = 0, $active = 1)
    {
        if (!isset($objType) || !isset($idWp)) return false;

        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                    FROM {$wpdb->prefix}ssbhesabfa
                    WHERE `id_ps` = %d
                    AND `id_ps_attribute` = %d
                    AND `obj_type` = %s
                    AND `active` = %d",
                $idWp,
                $idWpAttribute,
                $objType,
	            $active
            )
        );

        if (isset($row)) return $this->mapWpFa($row);

        return null;
    }
//=========================================================================================================
    public function getWpFaSearch($woocommerce_search_code = '', $woocommerce_attribute_search_code = '', $hesabfa_search_code = '', $obj_type_search = '')
    {
        global $wpdb;

        $conditions = [];
        $params = [];

        if ($woocommerce_search_code !== '') {
            $conditions[] = "id_ps = %s";
            $params[] = $woocommerce_search_code;
        }

        if ($woocommerce_attribute_search_code !== '' || $woocommerce_attribute_search_code === '0') {
            $conditions[] = "id_ps_attribute = %s";
            $params[] = $woocommerce_attribute_search_code;
        }

        if ($hesabfa_search_code !== '') {
            $conditions[] = "id_hesabfa = %s";
            $params[] = $hesabfa_search_code;
        }

        if ($obj_type_search !== '' && $obj_type_search != '0') {
            $conditions[] = "obj_type = %s";
            $params[] = $obj_type_search;
        }

        $sql = "SELECT * FROM {$wpdb->prefix}ssbhesabfa";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $prepared_sql = $wpdb->prepare($sql, ...$params);
        $result = $wpdb->get_results($prepared_sql);

        $wpFaObjects = array();
        if (isset($result) && is_array($result) && count($result) > 0) {
            foreach ($result as $item) {
                $wpFaObjects[] = $this->mapWpFa($item);
            }
        }

        return $wpFaObjects;
    }

//=========================================================================================================
    public function getWpFaByHesabfaId($objType, $hesabfaId, $active = 1)
    {
        if (!isset($objType) || !isset($hesabfaId)) return false;

        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                FROM {$wpdb->prefix}ssbhesabfa
                WHERE `id_hesabfa` = %d
                AND `obj_type` = %s
                AND `active` = %d",
                $hesabfaId,
                $objType,
                $active
            )
        );

        if (isset($row))
            return $this->mapWpFa($row);
        return null;
    }
//=========================================================================================================
    public function getWpFaId($objType, $idWp, $idWpAttribute = 0, $active = 1)
    {
        if (!isset($objType) || !isset($idWp))
            return false;

        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT `id`
                FROM {$wpdb->prefix}ssbhesabfa
                WHERE `id_ps` = %d
                AND `id_ps_attribute` = %d
                AND `obj_type` = %s
                AND `active` = %d",
                $idWp,
                $idWpAttribute,
                $objType,
	            $active
            )
        );

        if (is_object($row))
            return (int)$row->id;
        else
            return false;
    }
//=========================================================================================================
    public function getWpFaIdByHesabfaId($objType, $hesabfaId)
    {
        if (!isset($objType) || !isset($hesabfaId))
            return false;

        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT `id`
                FROM {$wpdb->prefix}ssbhesabfa
                WHERE `id_hesabfa` = %d
                AND `obj_type` = %s",
                $hesabfaId,
                $objType
            )
        );


        if (isset($row))
            return (int)$row->id;
        return null;
    }
//=========================================================================================================
    public function getProductCodeByWpId($id_product, $id_attribute = 0)
    {
        $obj = $this->getWpFa('product', $id_product, $id_attribute);
        if ($obj != null) return $obj->idHesabfa;

        return null;
    }
//=========================================================================================================
    public function getCustomerCodeByWpId($id_customer)
    {
        $obj = $this->getWpFa('customer', $id_customer, 0, 1);

        if ($obj != null) return $obj->idHesabfa;

        return null;
    }
//=========================================================================================================
    public function getInvoiceCodeByWpId($id_order)
    {
        $obj = $this->getWpFa('order', $id_order);

        if ($obj != null) return $obj->idHesabfa;

        return null;
    }
//=========================================================================================================
    public function getProductAndCombinations($idWp)
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT *
            FROM {$wpdb->prefix}ssbhesabfa
            WHERE `obj_type` = 'product'
            AND `id_ps` = %d",
            $idWp
        );

        $result = $wpdb->get_results($sql);


        $wpFaObjects = array();
        if (isset($result) && is_array($result) && count($result) > 0) {
            foreach ($result as $item)
                $wpFaObjects[] = $this->mapWpFa($item);
            return $wpFaObjects;
        }
        return null;
    }
//=========================================================================================================
    public function mapWpFa($sqlObj): WpFa
    {
        $wpFa = new WpFa();

        $wpFa->id = $sqlObj->id;
        $wpFa->idHesabfa = $sqlObj->id_hesabfa;
        $wpFa->idWp = $sqlObj->id_ps;
        $wpFa->idWpAttribute = $sqlObj->id_ps_attribute;
        $wpFa->objType = $sqlObj->obj_type;
        $wpFa->active = $sqlObj->active;

        return $wpFa;
    }
//=========================================================================================================
    public function saveProduct($item): bool
    {
        $json = json_decode($item->Tag);
        $wpFaService = new HesabfaWpFaService();
        $wpFa = $wpFaService->getWpFaByHesabfaId('product', $item->Code, 1);

        if (!$wpFa) {
            $wpFa = WpFa::newWpFa(0, 'product', (int)$item->Code, (int)$json->id_product, (int)$json->id_attribute, 1);
            HesabfaLogService::log(array("Item successfully added. Item code: " . (string)$item->Code . ". Product ID: $json->id_product-$json->id_attribute"));
            $wpFaService->save($wpFa);
        } else {
            $wpFaService->updateActive($wpFa, false);
            $wpFa->idHesabfa = (int)$item->Code;
            HesabfaLogService::log(array("Item successfully updated. Item code: " . (string)$item->Code . ". Product ID: $json->id_product-$json->id_attribute"));
            $wpFaService->update($wpFa);
        }

        return true;
    }
//=========================================================================================================
    public function saveCustomer($customer): bool
    {
        $json = json_decode($customer->Tag);
        if ((int)$json->id_customer == 0) return true;

        $id = $this->getWpFaId('customer', (int)$json->id_customer);
        global $wpdb;

        if (!$id) {
            $wpdb->insert(
                $wpdb->prefix . 'ssbhesabfa',
                array(
                    'id_hesabfa' => (int)$customer->Code,
                    'obj_type' => 'customer',
                    'id_ps' => (int)$json->id_customer
                ),
                array(
                    '%d',
                    '%s',
                    '%d'
                )
            );



            HesabfaLogService::writeLogStr("Customer successfully added. Customer code: " . (string)$customer->Code . ". Customer ID: $json->id_customer");
        } else {

            $wpdb->update(
                $wpdb->prefix . 'ssbhesabfa',
                array(
                    'id_hesabfa' => (int)$customer->Code,
                    'obj_type' => 'customer',
                    'id_ps' => (int)$json->id_customer,
                ),
                array('id' => $id),
                array(
                    '%d',
                    '%s',
                    '%d'
                ),
                array('%d')
            );

            HesabfaLogService::writeLogStr("Customer successfully updated. Customer code: " . (string)$customer->Code . ". Customer ID: $json->id_customer");
        }
        return true;
    }
//=========================================================================================================
    public function save(WpFa $wpFa)
    {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'ssbhesabfa',
            array(
                'id_hesabfa' => $wpFa->idHesabfa,
                'obj_type' => $wpFa->objType,
                'id_ps' => (int)$wpFa->idWp,
                'id_ps_attribute' => (int)$wpFa->idWpAttribute,
                'active' => 1,
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%d'
            )
        );
    }
//=========================================================================================================
    public function update(WpFa $wpFa)
    {
        global $wpdb;

        $idHesabfa = isset($wpFa->idHesabfa) ? sanitize_text_field($wpFa->idHesabfa) : '';
        $objType = isset($wpFa->objType) ? sanitize_text_field($wpFa->objType) : '';
        $idWp = isset($wpFa->idWp) ? (int)$wpFa->idWp : 0;
        $idWpAttribute = isset($wpFa->idWpAttribute) ? (int)$wpFa->idWpAttribute : 0;

        $wpdb->update(
            $wpdb->prefix . 'ssbhesabfa',
            array(
                'id_hesabfa' => $idHesabfa,
                'obj_type' => $objType,
                'id_ps' => $idWp,
                'id_ps_attribute' => $idWpAttribute,
                'active' => 1
            ),
            array('id' => $wpFa->id),
            array(
                '%s',
                '%s',
                '%d',
                '%d'
            ),
            array('%d')
        );
    }
//=========================================================================================================
    public function updateActive(WpFa $wpFa, $active)
    {
        global $wpdb;

        $id = isset($wpFa->id) ? (int)$wpFa->id : 0;

        $wpdb->update(
            $wpdb->prefix . 'ssbhesabfa',
            array(
                'active' => $active,
            ),
            array('id' => $id),
            array(
                '%d'
            ),
            array('%d')
        );
    }
//=========================================================================================================
    public function updateActiveAll($productId, $active)
    {
        global $wpdb;

        $productId = isset($productId) ? (int)$productId : 0;

        if ($productId > 0) {
            $wpdb->update(
                $wpdb->prefix . 'ssbhesabfa',
                array(
                    'active' => $active,
                ),
                array(
                    'id_ps' => $productId,
                ),
                array(
                    '%d',
                ),
                array(
                    '%d',
                )
            );
        }
    }
//=========================================================================================================
	public function getAllLinkedProducts() {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT *
            FROM {$wpdb->prefix}ssbhesabfa
            WHERE `obj_type` = 'product'
            AND `active` = '1'"
		);

		$result = $wpdb->get_results($sql);

		$wpFaObjects = array();
		if (isset($result) && is_array($result) && count($result) > 0) {
			foreach ($result as $item)
				$wpFaObjects[] = $this->mapWpFa($item);
			return $wpFaObjects;
		}
		return null;
	}
//=========================================================================================================
	public function deactivateWithIdHesabfaList($idArray): bool {
		if(empty($idArray))
			return false;
		global $wpdb;

		$table_name = $wpdb->prefix . 'ssbhesabfa';
		$placeholders = implode(',', array_fill(0, count($idArray), '%d'));
		$sql = "UPDATE $table_name SET active = 0 WHERE id_hesabfa IN ($placeholders)";
		$wpdb->query($wpdb->prepare($sql, ...$idArray));

		return true;
	}

//=========================================================================================================
}