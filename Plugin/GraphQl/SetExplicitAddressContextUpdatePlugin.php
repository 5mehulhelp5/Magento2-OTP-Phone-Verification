<?php
declare(strict_types=1);

namespace IDangerous\PhoneOtpVerification\Plugin\GraphQl;

use Magento\CustomerGraphQl\Model\Customer\Address\UpdateCustomerAddress;
use Magento\Customer\Api\Data\AddressInterface;
use IDangerous\PhoneOtpVerification\Helper\AddressSaveContext;

/**
 * Sets explicit address save context before GraphQL updateCustomerAddress,
 * so AddressRepositoryPlugin runs OTP verification.
 */
class SetExplicitAddressContextUpdatePlugin
{
    private AddressSaveContext $addressSaveContext;

    public function __construct(AddressSaveContext $addressSaveContext)
    {
        $this->addressSaveContext = $addressSaveContext;
    }

    /**
     * @param UpdateCustomerAddress $subject
     * @param AddressInterface $address
     * @param array $data
     * @return array
     */
    public function beforeExecute(UpdateCustomerAddress $subject, AddressInterface $address, array $data): array
    {
        $this->addressSaveContext->setExplicitAddressSave();
        return [$address, $data];
    }
}
