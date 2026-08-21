import { useI18n } from 'vue-i18n'

export function useFormatters() {
  const { n, d, locale } = useI18n()

  function formatCurrency(amount: number | null | undefined): string {
    if (amount === null || amount === undefined || isNaN(amount)) {
      return '0,00 €'
    }
    return n(amount, 'currency', locale.value)
  }

  function formatNumber(amount: number | null | undefined): string {
    if (amount === null || amount === undefined || isNaN(amount)) {
      return '0,00'
    }
    return n(amount, 'decimal', locale.value)
  }

  function formatDate(
    dateValue: string | Date | null | undefined,
    format: 'short' | 'long' = 'short'
  ): string {
    if (!dateValue) return ''
    const date = typeof dateValue === 'string' ? new Date(dateValue) : dateValue
    if (isNaN(date.getTime())) return ''
    return d(date, format, locale.value)
  }

  function formatTime(dateValue: string | Date | null | undefined): string {
    if (!dateValue) return ''
    const date = typeof dateValue === 'string' ? new Date(dateValue) : dateValue
    if (isNaN(date.getTime())) return ''
    return d(date, 'time', locale.value)
  }

  return {
    formatCurrency,
    formatNumber,
    formatDate,
    formatTime,
    locale,
  }
}
