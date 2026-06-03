<?php

namespace App\Services\Financing;

use App\Models\FinancingProduct;

class FinancingCalculatorService
{
    public function calculate(float $amount, int $tenureMonths, float $annualRatePercent): array
    {
        if ($amount <= 0 || $tenureMonths <= 0) {
            return [
                'monthly_payment' => 0,
                'total_repayment' => 0,
                'total_profit' => 0,
                'profit_per_month' => 0,
            ];
        }

        $monthlyRate = ($annualRatePercent / 100) / 12;
        $profitPerMonth = $amount * $monthlyRate;
        $totalProfit = $profitPerMonth * $tenureMonths;
        $totalRepayment = $amount + $totalProfit;
        $monthlyPayment = $totalRepayment / $tenureMonths;

        return [
            'monthly_payment' => round($monthlyPayment, 2),
            'total_repayment' => round($totalRepayment, 2),
            'total_profit' => round($totalProfit, 2),
            'profit_per_month' => round($profitPerMonth, 2),
        ];
    }

    public function calculateFromProduct(float $amount, int $tenureMonths, FinancingProduct $product): array
    {
        $rate = $product->resolveRate($tenureMonths) ?? 0;
        return $this->calculate($amount, $tenureMonths, $rate);
    }
}
