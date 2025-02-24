<?php

namespace Paynl\Payment\Model\Paymentmethod;

class Ideal extends PaymentMethod
{
    public const BANKSDISPLAYTYPE_DROPDOWN = 1;
    public const BANKSDISPLAYTYPE_LIST = 2;
    protected $_code = 'paynl_payment_ideal';

    /**
     * @return integer
     */
    protected function getDefaultPaymentOptionId()
    {
        return 10;
    }

    /**
     * @param \Magento\Framework\DataObject $data
     * @return object
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('payment_option', $data['payment_option']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additional_data = $data->getAdditionalData();
            if (isset($additional_data['payment_option'])) {
                $paymentOption = $additional_data['payment_option'];
                $this->getInfoInstance()->setAdditionalInformation('payment_option', $paymentOption);
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getPaymentOptions()
    {
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();
        $cacheName = 'paynl_banks_' . $this->getPaymentOptionId() . '_' . $storeId;

        $banksJson = $this->cache->load($cacheName);
        if ($banksJson) {
            $banks = json_decode($banksJson, true);
        } else {
            $this->paynlConfig->setStore($store);
            $this->paynlConfig->configureSDK();

            try {
                $banks = \Paynl\Paymentmethods::getBanks($this->getPaymentOptionId());
                $this->cache->save(json_encode($banks), $cacheName);
            } catch (\Exception $e) {
                $banks = [];
                $this->payHelper->logDebug('Bank retrieval error ' . $e->getMessage());
            }
        }

        foreach ($banks as $key => $bank) {
            $banks[$key]['logo'] = $this->paynlConfig->getIconUrlIssuer($bank['id']);
        }

        return $banks;
    }
}
