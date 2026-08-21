import { describe, it, expect } from 'vitest'
import { messages } from '@/i18n'

function flattenKeys(obj: Record<string, any>, prefix = ''): string[] {
  let result: string[] = []
  for (const key in obj) {
    if (Object.prototype.hasOwnProperty.call(obj, key)) {
      const newKey = prefix ? `${prefix}.${key}` : key
      if (typeof obj[key] === 'object' && obj[key] !== null) {
        result = result.concat(flattenKeys(obj[key], newKey))
      } else {
        result.push(newKey)
      }
    }
  }
  return result
}

describe('Frontend i18n Translation Key Parity', () => {
  it('must have exact nested key parity between German (de) and English (en)', () => {
    const deKeys = flattenKeys(messages.de).sort()
    const enKeys = flattenKeys(messages.en).sort()

    const missingInDe = enKeys.filter((k) => !deKeys.includes(k))
    const missingInEn = deKeys.filter((k) => !enKeys.includes(k))

    expect(
      missingInDe,
      `Missing German keys: ${missingInDe.join(', ')}`
    ).toEqual([])

    expect(
      missingInEn,
      `Missing English keys: ${missingInEn.join(', ')}`
    ).toEqual([])
  })
})
