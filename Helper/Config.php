<?php
namespace IDangerous\PhoneOtpVerification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_ENABLED = 'phone_otp/general/enabled';
    const XML_PATH_ENABLE_REGISTRATION = 'phone_otp/general/enable_registration';
    const XML_PATH_OPTIONAL_REGISTRATION = 'phone_otp/general/optional_registration';
    const XML_PATH_OTP_MESSAGE = 'phone_otp/general/otp_message';
    const XML_PATH_ENABLE_ADDRESS_PHONE_VERIFICATION = 'phone_otp/address/enable_address_phone_verification';
    const XML_PATH_REQUIRE_UNVERIFIED_ADDRESS_VERIFICATION = 'phone_otp/address/require_unverified_address_verification';
    const XML_PATH_ADDRESS_OTP_MODAL_NOTE = 'phone_otp/address/address_otp_modal_note';
    const XML_PATH_MIN_VERSION_ADDRESS_CHECKOUT = 'phone_otp/app_version/min_version_address_checkout';
    const XML_PATH_MIN_VERSION_REGISTRATION_ACCOUNT = 'phone_otp/app_version/min_version_registration_account';

    public function isEnabled($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isEnabledForRegistration($store = null)
    {
        return $this->isEnabled($store) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_REGISTRATION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isOptionalForRegistration($store = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_OPTIONAL_REGISTRATION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getOtpMessage($store = null)
    {
        $message = $this->scopeConfig->getValue(
            self::XML_PATH_OTP_MESSAGE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        // If no custom message is set, use translated default
        return $message ?: __('Your verification code is: {otp}')->render();
    }

    public function isAddressPhoneVerificationEnabled($store = null)
    {
        return $this->isEnabled($store) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLE_ADDRESS_PHONE_VERIFICATION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function isUnverifiedAddressVerificationRequired($store = null)
    {
        return $this->isAddressPhoneVerificationEnabled($store) && $this->scopeConfig->isSetFlag(
            self::XML_PATH_REQUIRE_UNVERIFIED_ADDRESS_VERIFICATION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getAddressOtpModalNote($store = null): string
    {
        $note = (string)$this->scopeConfig->getValue(
            self::XML_PATH_ADDRESS_OTP_MODAL_NOTE,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return trim($note);
    }

    /**
     * Min required app version for address/checkout OTP. Empty = no check.
     */
    public function getMinVersionAddressCheckout($store = null): string
    {
        return trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_MIN_VERSION_ADDRESS_CHECKOUT,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * Min required app version for registration/account OTP. Empty = no check.
     */
    public function getMinVersionRegistrationAccount($store = null): string
    {
        return trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_MIN_VERSION_REGISTRATION_ACCOUNT,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }
}