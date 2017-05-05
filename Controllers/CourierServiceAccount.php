<?php

/**
* Controller for Courier Service Accounts
*/
class CourierServiceAccount extends CourierService
{
    const ADMIN = 'admin';
    const ACCOUNT = 'account';

    /**
     * Function to get AWB for shipment booking
     *
     * @return string $awb AWB for the courier service
     *
     * @throws Exception if courier service does not support pre-allocation of AWB
     * @throws Exception if no AWB batches are available
     * @throws Exception if no AWBs are available in the batch
     */
    public function getAWB()
    {
        $awbBatch = $this->getAWBBatch();
        return $awbBatch->getAWB();
    }

    /**
     * Function get return the AWB Batch to be used for getting the AWB
     *
     * The function would determine if the Courier Service Account is set
     * to use global AWB Batches or account specific Batches. Based on the
     * defined batch type, oldest batch with available AWBs would be returned
     *
     * @throws Exception if there is no AWB Batch available
     * with available count > 0
     *
     * @return AWBBatch $awbBatch AWBBatch Controller object
     */
    private function getAWBBatch()
    {
        $accountId = $this->getAWBUseAccountID();
        if ($accountId == 0) {
        }
        // Get Mapped AWB Batches
    }

    /**
     * Function get determine if the Courier Service Account is set to
     * global or account specific
     *
     * @return string  $accountId Account ID from which the AWB have to be used.
     * In case of global use, account ID would be 0, else the actual account ID
     */
    private function getAWBUseAccountID()
    {
        // If the account ID for the Courier Service Account is 0 [Admin Courier Service Account], return 0
        // Else, Check if AWB use is set to account specific or global
    }

    /**
     * Function to get the Courier Service Controller for the
     * Courier Service Account
     *
     * @return CourierService $courierService Courier Service Controller
     */
    private function getCourierService()
    {
    }
}
