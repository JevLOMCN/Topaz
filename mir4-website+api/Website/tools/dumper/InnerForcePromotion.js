/* eslint-disable @typescript-eslint/no-var-requires */
const stringTemplate = require('./STRING_TEMPLATE.json')
const forceUpgrade = require('./CHARACTER_FORCE_LEVEL.json')
const forceInfo = require('./CHARACTER_FORCE.json')
const itemDataList = require('./ITEM.json')
const status = require('./STATUS.json')
const fs = require('fs')

const CostJSON = {}
const forceUpgradeData = forceUpgrade[0].Rows
const stringData = stringTemplate[0].Rows
const itemsData = itemDataList[0].Rows
const statusData = status[0].Rows
const forceInfoData = forceInfo[0].Rows

const ClassesById = {
  1: 'Warrior',
  2: 'Sorcerer',
  3: 'Taoist',
  4: 'Arbalist',
  5: 'Lancer',
  6: 'Darkist',
}

for (const [id, data] of Object.entries(forceUpgradeData)) {
  const name = getItemName(
    forceInfoData[data.ForcePanelID].ForcePanel_TitleStringID
  )
  const object = {
    copper: data.HardTraining_NeedCostCount,
    ...retrieveCost(data),
  }
  const className = ClassesById[id.toString().at(0)]

  if (className in CostJSON) {
    if (name in CostJSON[className]) {
      CostJSON[className][name] = {
        ...CostJSON[className][name],
        [String(data.ForcePanelLevel)]: object,
      }
    } else {
      CostJSON[className][name] = {
        [String(data.ForcePanelLevel)]: object,
      }
    }
  } else {
    CostJSON[className] = {
      [name]: {
        [String(data.ForcePanelLevel)]: object,
      },
    }
  }
}

function retrieveCost(costObject) {
  const obj = {}

  Array.from(Array(4).keys())
    .map((index) => {
      index = index + 1

      const objectKeyId = `HardTraining_NeedUseId0${index}`
      const objectKeyCount = `HardTraining_NeedItem0${index}Count`
      if (costObject[objectKeyId] !== 0) {
        obj[getItemName(getItemNameSid(costObject[objectKeyId] + 100000))] =
          costObject[objectKeyCount]
      }

      const effectKeyId = `ForceReward_Attribute0${index}Type`
      const effectKeyValue = `ForceReward_Attribute0${index}Value`

      if (costObject[effectKeyId] !== 0) {
        if ('Effects' in obj) {
          obj.Effects[
            getItemName(statusData[costObject[effectKeyId]].StringId)
          ] = costObject[effectKeyValue]
        } else {
          obj.Effects = {
            [getItemName(statusData[costObject[effectKeyId]].StringId)]:
              costObject[effectKeyValue],
          }
        }
      }

      return null
    })
    .filter(Boolean)

  return obj
}

function getItemNameSid(itemId) {
  return itemsData[itemId]?.NameSid
}

function getItemName(id) {
  return stringData[id]?.ENG
}

fs.writeFileSync(
  './FORCE_LEVEL_UPGRADE.json',
  JSON.stringify(CostJSON, null, 2)
)
