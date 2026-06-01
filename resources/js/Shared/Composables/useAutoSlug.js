import { computed, watch, ref } from 'vue'

export function useAutoSlug(getSource, form, slugField) {
  const userInteracted = ref(!!form[slugField])

  const slugify = (text) => {
    if (!text) return ''
    return text
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_]+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '')
  }

  watch(getSource, (val) => {
    if (!userInteracted.value) {
      form[slugField] = slugify(val || '')
    }
  })

  watch(() => form[slugField], (newVal, oldVal) => {
    if (oldVal === undefined) return
    if (!newVal) {
      userInteracted.value = false
    } else if (newVal !== slugify(getSource() || '')) {
      userInteracted.value = true
    }
  })

  const slugHelp = computed(() => userInteracted.value
    ? 'Kosongkan untuk auto-fill dari tajuk'
    : 'Diisi automatik dari tajuk',
  )

  return { userInteracted, slugHelp }
}
