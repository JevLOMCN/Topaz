import EquipmentCost from '@/data/EquipmentCost'
import {
  defaultFullItemObject,
  defaultInventoryValue,
  getFullItemRecipe,
} from '@/utils/craftingCalculator'
import { describe, expect, test } from '@jest/globals'

describe('Properly return full item recipe w/ Inventory', () => {
  test('Weapon primary recipe', () => {
    expect(
      getFullItemRecipe(
        EquipmentCost.weapon.primary.Legendary,
        {},
        defaultInventoryValue,
        1
      )
    ).toStrictEqual({
      '[E] Steel': 3000,
      '[R] Steel': 30000,
      'Glittering Powder': 187500,
      Copper: 150000000,
      Darksteel: 37500000,
      '[L] Steel': 300,
      '[E] Evil Minded Orb': 1000,
      '[R] Evil Minded Orb': 10000,
      '[L] Evil Minded Orb': 100,
      '[E] Moon Shadow Stone': 1000,
      '[R] Moon Shadow Stone': 10000,
      '[L] Moon Shadow Stone': 100,
      '[L] Dragon Scale': 1,
    })
  })
  test('Armor recipe', () => {
    expect(
      getFullItemRecipe(EquipmentCost.armor.Epic, {}, defaultInventoryValue, 1)
    ).toStrictEqual({
      '[R] Steel': 3000,
      'Glittering Powder': 12500,
      Copper: 10000000,
      Darksteel: 2500000,
      '[E] Steel': 300,
      '[R] Quintessence': 1000,
      '[E] Quintessence': 100,
      '[R] Exorcism Bauble': 1000,
      '[E] Exorcism Bauble': 100,
      '[E] Dragon Leather': 1,
    })
  })
  test('Armor recipe', () => {
    expect(
      getFullItemRecipe(
        EquipmentCost.earrings.Legendary,
        {},
        defaultInventoryValue,
        1
      )
    ).toStrictEqual({
      '[E] Platinum': 3000,
      '[R] Platinum': 30000,
      'Glittering Powder': 187500,
      Copper: 150000000,
      Darksteel: 37500000,
      '[L] Platinum': 300,
      '[E] Illuminating Fragment': 1000,
      '[R] Illuminating Fragment': 10000,
      '[L] Illuminating Fragment': 100,
      '[E] Anima Stone': 1000,
      '[R] Anima Stone': 10000,
      '[L] Anima Stone': 100,
      '[L] Dragon Eye': 1,
    })
  })
})

describe('Properly return item recipe with inventory', () => {
  test('Weapon primary recipe', () => {
    expect(
      getFullItemRecipe(
        EquipmentCost.weapon.primary.Legendary,
        {},
        {
          ...defaultInventoryValue,
          steel: {
            Legendary: { traddable: 10, nonTraddable: 0 },
            Epic: { traddable: 25, nonTraddable: 75 },
            Rare: { traddable: 725, nonTraddable: 3000 },
            Common: { traddable: 0, nonTraddable: 0 },
            Uncommon: { traddable: 0, nonTraddable: 0 },
          },
          copper: 1,
          darksteel: 1,
        },
        1
      )
    ).toStrictEqual({
      '[E] Steel': 2800,
      '[R] Steel': 24275,
      'Glittering Powder': 181250,
      Copper: 144999999,
      Darksteel: 36249999,
      '[L] Steel': 290,
      '[E] Evil Minded Orb': 1000,
      '[R] Evil Minded Orb': 10000,
      '[L] Evil Minded Orb': 100,
      '[E] Moon Shadow Stone': 1000,
      '[R] Moon Shadow Stone': 10000,
      '[L] Moon Shadow Stone': 100,
      '[L] Dragon Scale': 1,
    })
  })
  test('Weapon primary recipe with inventory', () => {
    expect(
      getFullItemRecipe(
        EquipmentCost.weapon.primary.Legendary,
        {},
        {
          ...defaultInventoryValue,
          evil_minded_orb: {
            ...defaultFullItemObject,
            Legendary: { traddable: 100, nonTraddable: 0 },
          },
          moon_shadow_stone: {
            ...defaultFullItemObject,
            Legendary: { traddable: 100, nonTraddable: 0 },
          },
          steel: {
            Legendary: { traddable: 74, nonTraddable: 0 },
            Epic: { traddable: 25, nonTraddable: 92650 },
            Rare: { traddable: 725, nonTraddable: 3000 },
            Common: { traddable: 0, nonTraddable: 0 },
            Uncommon: { traddable: 0, nonTraddable: 0 },
          },
          copper: 1,
          darksteel: 1,
        },
        1
      )
    ).toStrictEqual({
      '[E] Steel': 0,
      '[R] Steel': 0,
      'Glittering Powder': 28250,
      Copper: 22599999,
      Darksteel: 5649999,
      '[L] Steel': 226,
      '[E] Evil Minded Orb': 0,
      '[R] Evil Minded Orb': 0,
      '[L] Evil Minded Orb': 0,
      '[E] Moon Shadow Stone': 0,
      '[R] Moon Shadow Stone': 0,
      '[L] Moon Shadow Stone': 0,
      '[L] Dragon Scale': 1,
    })
  })
  test('Jewerly recipe', () => {
    expect(
      getFullItemRecipe(
        EquipmentCost.jewelry.Rare,
        {},
        {
          ...defaultInventoryValue,
          steel: {
            Legendary: { traddable: 10, nonTraddable: 0 },
            Epic: { traddable: 25, nonTraddable: 75 },
            Rare: { traddable: 725, nonTraddable: 3000 },
            Common: { traddable: 0, nonTraddable: 0 },
            Uncommon: { traddable: 0, nonTraddable: 0 },
          },
          copper: 25000000,
          darksteel: 9999,
        },
        1
      )
    ).toStrictEqual({
      '[R] Platinum': 75,
      '[R] Illuminating Fragment': 25,
      '[R] Anima Stone': 25,
      '[R] Dragon Horn': 1,
    })
  })
})
