import { computed } from 'vue';

export function useFinancingCalculator(amount, tenureMonths, annualRatePercent) {
  const monthlyPayment = computed(() => {
    const a = Number(amount.value) || 0;
    const t = Number(tenureMonths.value) || 1;
    const r = Number(annualRatePercent.value) || 0;
    if (a <= 0 || t <= 0) return 0;
    const monthlyRate = (r / 100) / 12;
    const profitPerMonth = a * monthlyRate;
    const totalRepayment = a + (profitPerMonth * t);
    return totalRepayment / t;
  });

  const totalRepayment = computed(() => {
    return monthlyPayment.value * (Number(tenureMonths.value) || 1);
  });

  const totalProfit = computed(() => {
    return totalRepayment.value - (Number(amount.value) || 0);
  });

  const profitPerMonth = computed(() => {
    const a = Number(amount.value) || 0;
    const r = Number(annualRatePercent.value) || 0;
    if (a <= 0) return 0;
    return a * ((r / 100) / 12);
  });

  const hasValues = computed(() => {
    return (Number(amount.value) || 0) > 0 && (Number(tenureMonths.value) || 0) > 0;
  });

  return {
    monthlyPayment,
    totalRepayment,
    totalProfit,
    profitPerMonth,
    hasValues,
  };
}
