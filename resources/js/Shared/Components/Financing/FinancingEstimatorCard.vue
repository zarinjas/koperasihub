<script setup>
import { computed } from 'vue';
import { Calculator, HandCoins } from 'lucide-vue-next';
import { useFinancingCalculator } from '@/Shared/Composables/useFinancingCalculator';

const props = defineProps({
  amount: { type: [Number, String], default: 0 },
  tenureMonths: { type: [Number, String], default: 0 },
  annualRatePercent: { type: [Number, String], default: 0 },
  compact: { type: Boolean, default: false },
});

const amountRef = computed(() => props.amount);
const tenureRef = computed(() => props.tenureMonths);
const rateRef = computed(() => props.annualRatePercent);

const { monthlyPayment, totalRepayment, totalProfit, hasValues } = useFinancingCalculator(amountRef, tenureRef, rateRef);

const fmt = (val) => 'RM ' + Number(val).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
  <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0">
    <div v-if="hasValues"
      class="rounded-xl border border-teal-200 bg-teal-50 p-4 shadow-sm"
      :class="compact ? 'p-3' : 'p-4'"
    >
      <div class="flex items-center gap-2 mb-3">
        <Calculator class="h-4 w-4 text-teal-700" />
        <h4 class="text-sm font-semibold text-teal-900">Anggaran Pembiayaan</h4>
      </div>

      <div :class="compact ? 'space-y-1' : 'space-y-2'">
        <div class="flex items-center justify-between">
          <span class="text-xs text-teal-700">Anggaran Bulanan</span>
          <span class="text-sm font-bold text-teal-900">{{ fmt(monthlyPayment) }}<span class="text-xs font-normal">/bln</span></span>
        </div>
        <div class="flex items-center justify-between border-t border-teal-200 pt-1.5">
          <span class="text-xs text-teal-700">Jumlah Bayaran</span>
          <span class="text-sm font-semibold text-teal-900">{{ fmt(totalRepayment) }}</span>
        </div>
        <div class="flex items-center justify-between border-t border-teal-200 pt-1.5">
          <span class="text-xs text-teal-700">Jumlah Keuntungan</span>
          <span class="text-sm font-medium text-teal-800">{{ fmt(totalProfit) }}</span>
        </div>
      </div>
    </div>
  </Transition>
</template>
