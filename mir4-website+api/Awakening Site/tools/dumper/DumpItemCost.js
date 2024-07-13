/* eslint-disable @typescript-eslint/no-var-requires */
const itemMake = require('./ITEM_MAKE.json')
const itemDataList = require('./ITEM.json')
const stringTemplate = require('./STRING_TEMPLATE.json')
const money = require('./MONEY.json')
const fs = require('fs')

const CostJSON = {}
const BaseResourcesJSON = {}

const items = Object.values(itemMake[0].Rows)
const itemsData = itemDataList[0].Rows
const stringData = stringTemplate[0].Rows

const rarities = ['Common', 'Uncommon', 'Rare', 'Epic', 'Legendary']
const rarityRegex = /(\[L\].|\[E\].|\[R\].|\[UC\].)/gm

const ALLOWED_RESOURCE_NAME = [
  'Anima Stone',
  'Exorcism Bauble',
  'Moon Shadow Stone',
  'Illuminating Fragment',
  'Quintessence',
  'Evil Minded Orb',
  'Platinum',
  'Steel',
]

const ALLOWED_ITEM_COST = [
  'Dragon Scale',
  'Dragon Claw',
  'Dragon Leather',
  'Dragon Horn',
  'Dragon Eye',
]

for (const item of items) {
  const { ItemId, MakeType, ClassId } = item
  const itemNameSid = itemsData[item.ItemId].NameSid
  const name = getItemName(itemNameSid)

  const materialCost = getMaterialItems(item)
  const rarity = getItemRarity(ItemId)
  if (
    (ClassId !== 4 && ClassId !== 0) ||
    (MakeType === 4 && rarity === 'Uncommon') ||
    Object.keys(materialCost).length < 1
  ) {
    continue
  }

  const costObject = {
    ...materialCost,
    ...retrieveAdditionalCost(item),
  }

  CostJSON[name] = {
    Rarity: rarity,
    Cost: costObject,
  }
}

for (const [name, mergedObj] of Object.entries(CostJSON)) {
  const { Rarity, Cost } = mergedObj

  if (!name.match(rarityRegex)) continue
  const nameWithoutRarity = name.replace(rarityRegex, '')

  if (!ALLOWED_RESOURCE_NAME.includes(nameWithoutRarity)) continue

  if (nameWithoutRarity in BaseResourcesJSON) {
    BaseResourcesJSON[nameWithoutRarity][Rarity] = Cost
  } else {
    BaseResourcesJSON[nameWithoutRarity] = {
      [Rarity]: Cost,
    }
  }
  delete BaseResourcesJSON[name]
}

// Final formatting for JSON files
for (const [name, obj] of Object.entries(CostJSON)) {
  const nameWithoutRarity = name.replace(rarityRegex, '')

  if (nameWithoutRarity in BaseResourcesJSON) {
    delete CostJSON[name]
  }

  if (
    Object.keys(obj.Cost).some((item) =>
      ALLOWED_ITEM_COST.includes(item.replace(rarityRegex, ''))
    )
  ) {
    continue
  }

  delete CostJSON[name]
}

function getMaterialItems(costObject) {
  const obj = {}

  Array.from(Array(5).keys())
    .map((index) => {
      index = index + 1

      const objectKeyId = `MaterialUse0${index}Id`
      const objectKeyCount = `MaterialItem0${index}Count`
      const key =
        getItemNameSid(costObject[objectKeyId] + 100000) ||
        getItemNameSid(costObject[objectKeyId] + 200000)

      if (key) {
        obj[getItemName(key)] = costObject[objectKeyCount]
      }

      return null
    })
    .filter(Boolean)

  return obj
}

function retrieveAdditionalCost(costObject) {
  const obj = {}

  Array.from(Array(2).keys())
    .map((index) => {
      index = index + 1

      const objectKeyId = `NeedCost0${index}Id`
      const objectKeyCount = `NeedCost0${index}Count`
      if (getItemMoneySid(costObject[objectKeyId])) {
        obj[getItemName(getItemMoneySid(costObject[objectKeyId]))] =
          costObject[objectKeyCount]
      }

      return null
    })
    .filter(Boolean)

  return obj
}

function getItemName(id) {
  return stringData[id]?.ENG
}

function getItemNameSid(itemId) {
  return itemsData[itemId]?.NameSid
}

function getItemRarity(itemId) {
  return rarities[itemsData[itemId]?.Grade - 1]
}

function getItemMoneySid(itemId) {
  return money[0].Rows[itemId]?.NameSid
}

fs.writeFileSync('./ITEM_COST.json', JSON.stringify(CostJSON, null, 2))
fs.writeFileSync(
  './BASE_RESOURCES.json',
  JSON.stringify(BaseResourcesJSON, null, 2)
)
