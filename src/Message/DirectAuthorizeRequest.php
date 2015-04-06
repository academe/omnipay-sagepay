<?php

namespace Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Authorize Request
 */
class DirectAuthorizeRequest extends AbstractRequest
{
    // An authorization is a deferred transaction, to be released or
    // cancelled later.
    protected $action = 'DEFERRED';

    // The only characterset that SagePay accepts in all posts, at least for now.
    const GATEWAY_CHARSET = 'ISO-8859-1';

    protected $cardBrandMap = array(
        'mastercard' => 'mc',
        'diners_club' => 'dc'
    );

    /**
     * Convert a string to the SagePay characterset.
     * Only UTF-8 is recognised for now; if the string is a valid UTF-8
     * string, then it is converted to self::GATEWAY_CHARSET with
     * translitteration to help preserve the information it contains.
     */
    protected function convertCharset($string)
    {
    }

    /**
     * Get the base data used by all authorization and purchase requests.
     */
    protected function getBaseAuthorizeData()
    {
        $this->validate('amount', 'card', 'transactionId');
        $card = $this->getCard();

        $data = $this->getBaseData();
        $data['Description'] = $this->getDescription();
        $data['Amount'] = $this->getAmount();
        $data['Currency'] = $this->getCurrency();
        $data['VendorTxCode'] = $this->getTransactionId();
        $data['ClientIPAddress'] = $this->getClientIp();
        $data['ApplyAVSCV2'] = $this->getApplyAVSCV2() ?: 0;
        $data['Apply3DSecure'] = $this->getApply3DSecure() ?: 0;

        // billing details
        $data['BillingFirstnames'] = $card->getBillingFirstName();
        $data['BillingSurname'] = $card->getBillingLastName();
        $data['BillingAddress1'] = $card->getBillingAddress1();
        $data['BillingAddress2'] = $card->getBillingAddress2();
        $data['BillingCity'] = $card->getBillingCity();
        $data['BillingPostCode'] = $card->getBillingPostcode();
        $data['BillingState'] = $card->getBillingCountry() === 'US' ? $card->getBillingState() : '';
        $data['BillingCountry'] = $card->getBillingCountry();
        $data['BillingPhone'] = $card->getBillingPhone();

        // shipping details
        $data['DeliveryFirstnames'] = $card->getShippingFirstName();
        $data['DeliverySurname'] = $card->getShippingLastName();
        $data['DeliveryAddress1'] = $card->getShippingAddress1();
        $data['DeliveryAddress2'] = $card->getShippingAddress2();
        $data['DeliveryCity'] = $card->getShippingCity();
        $data['DeliveryPostCode'] = $card->getShippingPostcode();
        $data['DeliveryState'] = $card->getShippingCountry() === 'US' ? $card->getShippingState() : '';
        $data['DeliveryCountry'] = $card->getShippingCountry();
        $data['DeliveryPhone'] = $card->getShippingPhone();
        $data['CustomerEMail'] = $card->getEmail();

        return $data;
    }

    public function getData()
    {
        $data = $this->getBaseAuthorizeData();
        $this->getCard()->validate();

        $data['CardHolder'] = $this->getCard()->getName();
        $data['CardNumber'] = $this->getCard()->getNumber();
        $data['CV2'] = $this->getCard()->getCvv();
        $data['ExpiryDate'] = $this->getCard()->getExpiryDate('my');
        $data['CardType'] = $this->getCardBrand();

        if ($this->getCard()->getStartMonth() and $this->getCard()->getStartYear()) {
            $data['StartDate'] = $this->getCard()->getStartDate('my');
        }

        if ($this->getCard()->getIssueNumber()) {
            $data['IssueNumber'] = $this->getCard()->getIssueNumber();
        }

        return $data;
    }

    public function getService()
    {
        return 'vspdirect-register';
    }

    protected function getCardBrand()
    {
        $brand = $this->getCard()->getBrand();

        if (isset($this->cardBrandMap[$brand])) {
            return $this->cardBrandMap[$brand];
        }

        return $brand;
    }
}
