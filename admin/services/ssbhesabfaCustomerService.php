<?php


class ssbhesabfaCustomerService
{
    public static function mapCustomer($customer, $id_customer, $countries, $states) {
        $firstName = $customer->get_first_name() ? $customer->get_first_name() : $customer->get_billing_first_name();
        $lastName = $customer->get_last_name() ? $customer->get_last_name() : $customer->get_billing_last_name();
        $name = $firstName . ' ' . $lastName;

        if (empty($name) || $name === ' ')
                $name = __('Not Define', 'ssbhesabfa');

        $country_name = $countries[$customer->get_billing_country()];
        $state_name = $states[$customer->get_billing_country()][$customer->get_billing_state()];

        $hesabfaCustomer = array(
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

        return $hesabfaCustomer;
    }

}