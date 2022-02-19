<?php


class ssbhesabfaCustomerService
{
    public static $countries;
    public static $states;

    public static function mapCustomer($code, $id_customer, $type = 'first'): array
    {
        self::getCountriesAndStates();

        $customer = new WC_Customer($id_customer);
        $firstName = $customer->get_first_name() ? $customer->get_first_name() : $customer->get_billing_first_name();
        $lastName = $customer->get_last_name() ? $customer->get_last_name() : $customer->get_billing_last_name();
        $name = $firstName . ' ' . $lastName;

        if (empty($name) || $name === ' ')
            $name = __('Not Defined', 'ssbhesabfa');

        $hesabfaCustomer = array();

        switch ($type) {
            case 'first':
            case 'billing':
                $country_name = self::$countries[$customer->get_billing_country()];
                $state_name = self::$states[$customer->get_billing_country()][$customer->get_billing_state()];

                $hesabfaCustomer = array(
                    'Code' => $code,
                    'Name' => $name,
                    'FirstName' => Ssbhesabfa_Validation::contactFirstNameValidation($firstName),
                    'LastName' => Ssbhesabfa_Validation::contactLastNameValidation($lastName),
                    'ContactType' => 1,
                    'NodeFamily' => 'اشخاص :' . get_option('ssbhesabfa_contact_node_family'),
                    'Address' => Ssbhesabfa_Validation::contactAddressValidation($customer->get_billing_address()),
                    'City' => Ssbhesabfa_Validation::contactCityValidation($customer->get_billing_city()),
                    'State' => Ssbhesabfa_Validation::contactStateValidation($state_name),
                    'Country' => Ssbhesabfa_Validation::contactCountryValidation($country_name),
                    'PostalCode' => Ssbhesabfa_Validation::contactPostalCodeValidation($customer->get_billing_postcode()),
                    'Phone' => Ssbhesabfa_Validation::contactPhoneValidation($customer->get_billing_phone()),
                    'Email' => Ssbhesabfa_Validation::contactEmailValidation($customer->get_email()),
                    'Tag' => json_encode(array('id_customer' => $id_customer)),
                    'Note' => __('Customer ID in OnlineStore: ', 'ssbhesabfa') . $id_customer,
                );
                break;
            case 'shipping':
                $country_name = self::$countries[$customer->get_shipping_country()];
                $state_name = self::$states[$customer->get_shipping_country()][$customer->get_shipping_state()];

                $hesabfaCustomer = array(
                    'Code' => $code,
                    'Name' => $name,
                    'FirstName' => Ssbhesabfa_Validation::contactFirstNameValidation($firstName),
                    'LastName' => Ssbhesabfa_Validation::contactLastNameValidation($lastName),
                    'ContactType' => 1,
                    'NodeFamily' => 'اشخاص :' . get_option('ssbhesabfa_contact_node_family'),
                    'Address' => Ssbhesabfa_Validation::contactAddressValidation($customer->get_shipping_address()),
                    'City' => Ssbhesabfa_Validation::contactCityValidation($customer->get_shipping_city()),
                    'State' => Ssbhesabfa_Validation::contactStateValidation($state_name),
                    'Country' => Ssbhesabfa_Validation::contactCountryValidation($country_name),
                    'PostalCode' => Ssbhesabfa_Validation::contactPostalCodeValidation($customer->get_shipping_postcode()),
                    'Phone' => Ssbhesabfa_Validation::contactPhoneValidation($customer->get_billing_phone()),
                    'Email' => Ssbhesabfa_Validation::contactEmailValidation($customer->get_email()),
                    'Tag' => json_encode(array('id_customer' => $id_customer)),
                    'Note' => __('Customer ID in OnlineStore: ', 'ssbhesabfa') . $id_customer,
                );
                break;
        }

        if($hesabfaCustomer["Phone"] == '')
            unset($hesabfaCustomer["Phone"]);
        if($hesabfaCustomer["Email"] == '')
            unset($hesabfaCustomer["Email"]);
        if($hesabfaCustomer["Address"] == '')
            unset($hesabfaCustomer["Address"]);
        if($hesabfaCustomer["PostalCode"] == '')
            unset($hesabfaCustomer["PostalCode"]);
        if($hesabfaCustomer["City"] == '')
            unset($hesabfaCustomer["City"]);
        if($hesabfaCustomer["State"] == '')
            unset($hesabfaCustomer["State"]);
        if($hesabfaCustomer["Country"] == '')
            unset($hesabfaCustomer["Country"]);

        return $hesabfaCustomer;
    }

    public static function mapGuestCustomer($code, $id_order): array
    {
        $order = new WC_Order($id_order);

        $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        if (empty($order->get_billing_first_name()) && empty($order->get_billing_last_name())) {
            $name = __('Guest Customer', 'ssbhesabfa');
        }

        $country_name = self::$countries[$order->get_billing_country()];
        $state_name = self::$states[$order->get_billing_country()][$order->get_billing_state()];

        return array(
            'Code' => $code,
            'Name' => $name,
            'FirstName' => Ssbhesabfa_Validation::contactFirstNameValidation($order->get_billing_first_name()),
            'LastName' => Ssbhesabfa_Validation::contactLastNameValidation($order->get_billing_last_name()),
            'ContactType' => 1,
            'NodeFamily' => 'اشخاص :' . get_option('ssbhesabfa_contact_node_family'),
            'Address' => Ssbhesabfa_Validation::contactAddressValidation($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()),
            'City' => Ssbhesabfa_Validation::contactCityValidation($order->get_billing_city()),
            'State' => Ssbhesabfa_Validation::contactStateValidation($state_name),
            'Country' => Ssbhesabfa_Validation::contactCountryValidation($country_name),
            'PostalCode' => Ssbhesabfa_Validation::contactPostalCodeValidation($order->get_billing_postcode()),
            'Phone' => Ssbhesabfa_Validation::contactPhoneValidation($order->get_billing_phone()),
            'Email' => Ssbhesabfa_Validation::contactEmailValidation($order->get_billing_email()),
            'Tag' => json_encode(array('id_customer' => 0)),
            'Note' => __('Customer registered as a GuestCustomer.', 'ssbhesabfa'),
        );
    }

    private static function getCountriesAndStates()
    {
        if (!isset(self::$countries)) {
            $countries_obj = new WC_Countries();
            self::$countries = $countries_obj->get_countries();
            self::$states = $countries_obj->get_states();
        }
    }
}