import BaseResourceCost from '@/data/BaseResouceCost'

import {
  ComplementaryItems,
  extractItemRarity,
  formatItemName,
  rarityRegex,
} from '@/utils/index'

export const defaultItemObject = { traddable: 0, nonTraddable: 0 }

export const defaultFullItemObject = {
  Legendary: { traddable: 0, nonTraddable: 0 },
  Epic: { traddable: 0, nonTraddable: 0 },
  Rare: { traddable: 0, nonTraddable: 0 },
  Uncommon: { traddable: 0, nonTraddable: 0 },
  Common: { traddable: 0, nonTraddable: 0 },
}

export const defaultInventoryValue = {
  anima_stone: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  blue_devil_stone: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  evil_minded_orb: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  exorcism_bauble: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  illuminating_fragment: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  moon_shadow_stone: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  platinum: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  steel: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  quintessence: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  glittering_powder: 0,
  copper: 0,
  darksteel: 0,
  energy: 0,
  dragon_scale: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  dragon_claw: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  dragon_eye: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  dragon_horn: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  dragon_leather: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  century_fruit: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  eternal_snow_panax: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  flower_oil: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  herb_leaf: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  herb_root: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  moonlight_magic_stone: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  purified_water: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  reishi: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  snow_panax: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  unihorn_slice: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
  virtue_pill: {
    Legendary: { traddable: 0, nonTraddable: 0 },
    Epic: { traddable: 0, nonTraddable: 0 },
    Rare: { traddable: 0, nonTraddable: 0 },
    Uncommon: { traddable: 0, nonTraddable: 0 },
    Common: { traddable: 0, nonTraddable: 0 },
  },
}

export function getItemOwnedAmount({
  item,
  rarity,
  inventory,
}: {
  item: ItemWithRarity | NonRarityItems
  rarity: RarityTypes | 'Default'
  inventory: InventoryType
}) {
  if (rarity === 'Default') {
    return inventory[formatItemName(item) as NonRarityItems]
  }

  const inventoryItem = inventory[formatItemName(item)][rarity]

  let ownedAmount = 0
  if (inventoryItem) {
    ownedAmount =
      typeof inventoryItem === 'number'
        ? inventoryItem
        : inventoryItem?.traddable + inventoryItem?.nonTraddable
  }

  return ownedAmount
}

export function getFullItemRecipe(
  itemRecipe: Record<string, number>,
  result: Record<string, number>,
  inventory: InventoryType,
  tierMultiplier: number
) {
  for (const [item, amount] of Object.entries(itemRecipe)) {
    const itemRarity = extractItemRarity(item)

    let ownedAmount = getItemOwnedAmount({
      item: item as ItemWithRarity,
      rarity: itemRarity,
      inventory,
    })

    if (ComplementaryItems.includes(item)) ownedAmount = 0

    const totalResource = ((result[item] || 0) + amount) * tierMultiplier
    const totalAmount = Math.max(totalResource - ownedAmount, 0)

    getItemRecipe(
      item,
      itemRarity,
      result,
      totalAmount,
      inventory
    )

    result[item] = totalAmount
  }

  ComplementaryItems.forEach((item) => {
    if (item in result) {
      result[item] -= getItemOwnedAmount({
        item: formatItemName(item),
        rarity: 'Default',
        inventory,
      })
    }
  })

  return result
}

export function getItemRecipe(
  itemName: string,
  rarity: RarityTypes | 'Default',
  result: Record<string, number>,
  multiplier: number,
  inventory: InventoryType
) {
  if (rarity === 'Default') return

  const nameWithoutRarity = itemName.replace(rarityRegex, '')
  const itemRecipe =
    BaseResourceCost?.[nameWithoutRarity as keyof typeof BaseResourceCost]?.[
      rarity as Exclude<RarityTypes, 'Rare' | 'Uncommon' | 'Common'>
    ]

  if (!itemRecipe) return

  for (const [item, amount] of Object.entries(itemRecipe)) {
    const itemRarity = extractItemRarity(item)

    let ownedAmount = getItemOwnedAmount({
      item: item as ItemWithRarity,
      rarity: itemRarity,
      inventory,
    })

    // prevent owned amount from being subtracted multiple times
    if (ComplementaryItems.includes(item)) ownedAmount = 0

    const totalAmount = (result[item] || 0) + amount * multiplier
    const realAmount = Math.max(totalAmount - ownedAmount, 0)

    result[item] = realAmount

    getItemRecipe(item, itemRarity, result, realAmount, inventory)
  }
}
