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
    modelValue: { type: [Number, String], default: null },
    error: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'select']);

const open = ref(false);
const query = ref('');
const results = ref([]);
const loading = ref(false);
const selectedMember = ref(null);

const searchMembers = async (q) => {
    if (!q || q.length < 2) {
        results.value = [];
        return;
    }

    loading.value = true;
    try {
        const res = await fetch(`/api/members/search?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        results.value = data.data || [];
    } finally {
        loading.value = false;
    }
};

const selectMember = (member) => {
    selectedMember.value = member;
    emit('update:modelValue', member.id);
    emit('select', member);
    open.value = false;
    query.value = '';
};

const clearSelection = () => {
    selectedMember.value = null;
    emit('update:modelValue', null);
    emit('select', null);
};

watch(query, (val) => {
    if (val.length >= 2) {
        searchMembers(val);
    } else {
        results.value = [];
    }
});

watch(() => props.modelValue, async (id) => {
    if (!id) {
        selectedMember.value = null;
        return;
    }
    try {
        const res = await fetch(`/api/members/search?id=${encodeURIComponent(id)}`);
        const data = await res.json();
        if (data.data && data.data.length > 0) {
            selectedMember.value = data.data[0];
        }
    } catch {
        // ignore
    }
}, { immediate: true });
</script>

<template>
    <div class="space-y-2">
        <label class="text-sm font-medium text-slate-800">
            Diperkenalkan Oleh
        </label>

        <div v-if="selectedMember" class="flex items-center gap-2">
            <Badge variant="secondary" class="flex items-center gap-1.5 py-1.5 pl-2.5 pr-1.5 text-sm">
                <span>{{ selectedMember.full_name }} ({{ selectedMember.member_no }})</span>
                <button type="button" class="ml-0.5 inline-flex rounded-sm opacity-60 transition hover:opacity-100" @click="clearSelection">
                    <X class="h-3.5 w-3.5" />
                </button>
            </Badge>
        </div>

        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    class="w-full justify-between"
                >
                    <span :class="selectedMember ? 'text-slate-900' : 'text-slate-400'">
                        {{ selectedMember ? 'Tukar perujuk' : 'Cari nama atau no ahli perujuk...' }}
                    </span>
                    <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-96 p-0">
                <Command>
                    <CommandInput
                        v-model="query"
                        placeholder="Cari nama atau no ahli..."
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
                                :value="member.full_name"
                                @select="selectMember(member)"
                            >
                                <Check
                                    :class="`mr-2 h-4 w-4 ${selectedMember?.id === member.id ? 'opacity-100' : 'opacity-0'}`"
                                />
                                <div>
                                    <p class="text-sm font-medium">{{ member.full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ member.member_no }}</p>
                                </div>
                            </CommandItem>
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>

        <p v-if="!selectedMember" class="text-xs text-slate-500">
            Biarkan kosong jika tiada perujuk.
        </p>

        <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
    </div>
</template>
