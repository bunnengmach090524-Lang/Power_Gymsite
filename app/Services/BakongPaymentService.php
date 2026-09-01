<?php

namespace App\Services;

use App\Models\Tenant;
use KHQR\BakongKHQR;
use KHQR\Models\IndividualInfo;
use KHQR\Helpers\KHQRData;

class BakongPaymentService
{
    public function generateQr(Tenant $tenant, float $amount, string $billNumber): array
    {
        $individualInfo = new IndividualInfo(
            bakongAccountID: $tenant->bakong_account_id,
            merchantName: $tenant->bakong_merchant_name,
            merchantCity: $tenant->bakong_merchant_city ?? 'Phnom Penh',
            currency: KHQRData::CURRENCY_USD,
            amount: $amount,
            billNumber: $billNumber,
            expirationTimestamp: (now()->addMinutes(15)->getTimestampMs()), // 👈 បន្ថែមថ្មី
        );

        $result = BakongKHQR::generateIndividual($individualInfo);

        return [
            'qr_string' => $result->data['qr'],
            'md5' => $result->data['md5'],
        ];
    }

    public function verifyTransaction(Tenant $tenant, string $md5): bool
    {
        if (empty($tenant->bakong_api_token)) {
            return false;
        }

        $bakongKHQR = new BakongKHQR($tenant->bakong_api_token);
        $response = $bakongKHQR->checkTransactionByMD5($md5);

        return $response->status['code'] === 0
            && ($response->data['responseCode'] ?? null) === 0;
    }

    public function isAutoVerifyEnabled(Tenant $tenant): bool
    {
        return ! empty($tenant->bakong_api_token);
    }
}