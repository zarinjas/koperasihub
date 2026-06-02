<script setup>
import { ref, watch } from 'vue';
import { Button } from '@/Shared/Components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/Shared/Components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/Shared/Components/ui/popover';
import { Badge } from '@/Shared/Components/ui/badge';
import { X, Check, ChevronsUpDown } from 'lucide-vue-next';

const props = defineProps({
    searchUrl: { type: String, required: true },
    modelValue: { type: Array, default: () => [] },
    selectedMembers: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const query = ref('');
const results = ref([]);
const loading = ref(false);
const selected = ref([...props.modelValue]);

const selectedNames = ref([...props.selectedMembers]);

const searchMembers = async (q) => {
    if (!q || q.length < 1) {
        results.value = [];
        return;
    }

    loading.value = true;
    try {
        const res = await fetch(`${props.searchUrl}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        results.value = data.filter(
            (m) => !selected.value.some((s) => s.id === m.id),
        );
    } finally {
        loading.value = false;
    }
};

const selectMember = (member) => {
    selected.value.push({ id: member.id, full_name: member.full_name, member_no: member.member_no, email: member.email });
    open.value = false;
    query.value = '';
    emit('update:modelValue', selected.value.map((m) => m.id));
};

const removeMember = (index) => {
    selected.value.splice(index, 1);
    emit('update:modelValue', selected.value.map((m) => m.id));
};

watch(query, (val) => {
    searchMembers(val);
});

watch(
    () => props.selectedMembers,
    (val) => {
        if (val.length > 0) {
            selectedNames.value = val;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="space-y-2">
        <div v-if="selected.length > 0" class="flex flex-wrap gap-1.5">
            <Badge
                v-for="(member, index) in selected"
                :key="member.id"
                variant="secondary"
                class="flex items-center gap-1 py-1.5 pl-2.5 pr-1.5 text-sm"
            >
                <span>{{ member.full_name }} ({{ member.member_no }})</span>
                <button
                    type="button"
                    class="ml-0.5 inline-flex rounded-sm opacity-60 transition hover:opacity-100"
                    @click="removeMember(index)"
                >
                    <X class="h-3 w-3" />
                </button>
            </Badge>
        </div>

        <Popover :open="open" @update:open="open = $event">
            <PopoverTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    class="w-full justify-between"
                >
                    Cari ahli...
                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-96 p-0">
                <Command>
                    <CommandInput
                        v-model="query"
                        placeholder="Cari nama, no ahli, atau emel..."
                        @update:model-value="searchMembers"
                    />
                    <CommandList>
                        <CommandEmpty>
                            <span v-if="loading">Mencari...</span>
                            <span v-else>Tiada ahli ditemui.</span>
                        </CommandEmpty>
                        <CommandGroup>
                            <CommandItem
                                v-for="member in results"
                                :key="member.id"
                                :value="member.id"
                                @select="selectMember(member)"
                            >
                                <Check class="mr-2 h-4 w-4 opacity-0" />
                                <div>
                                    <p class="text-sm font-medium">{{ member.full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ member.member_no }} · {{ member.email }}</p>
                                </div>
                            </CommandItem>
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    </div>
</template>