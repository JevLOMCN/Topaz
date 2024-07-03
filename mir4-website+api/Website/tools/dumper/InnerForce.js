/* eslint-disable @typescript-eslint/no-var-requires */
const stringTemplate = require('./STRING_TEMPLATE.json')
const forceUpgrade = require('./CHARACTER_FORCE_LEVEL.json')
const forceLevels = require('./CHARACTER_FORCE_BLOOD.json')
const forceInfo = require('./CHARACTER_FORCE.json')
const itemDataList = require('./ITEM.json')
const status = require('./STATUS.json')
const fs = require('fs')

const CostJSON = {}
const levelsData = forceLevels[0].Rows
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

const BloodSetName = {
  1: 'Muscle Strength Manual',
  2: 'Nine Yin Manual',
  3: 'Nine Yang Manual',
  4: 'Violet Mist Art',
  5: 'Northern Profound Art',
  6: 'Toad Stance',
}

for (const [id, data] of Object.entries(levelsData)) {
  const name = getSetId(data.BloodSetID)
  const object = {
    EnergyPerClick: data.TrainingCost_Spirit,
    ...retrieveCost(data),
  }
  const className = ClassesById[id.toString().at(0)]

  if (className in CostJSON) {
    if (name in CostJSON[className]) {
      CostJSON[className][name] = {
        ...CostJSON[className][name],
        [String(data.BloodLevel)]: object,
      }
    } else {
      CostJSON[className][name] = {
        [String(data.BloodLevel)]: object,
      }
    }
  } else {
    CostJSON[className] = {
      [name]: {
        [String(data.BloodLevel)]: object,
      },
    }
  }
}

function retrieveCost(costObject) {
  const obj = {}

  const classId = Number(costObject.UniqueID.toString().at(0)) * 1000000
  const currentBloodId = Number(costObject.BloodSetID.toString().at(-1)) * 1000
  const upgradeObject =
    forceUpgradeData[
      classId + currentBloodId + costObject.HardTraining_ForcePanelLevel
    ]

  Array.from(Array(4).keys())
    .map((index) => {
      index = index + 1

      const bloodName = getItemName(
        forceInfoData[costObject.BloodSetID][`Blood_Name_${index}`]
      )
      obj[bloodName] = {}

      const effectKeyId = `Blood${index}_AttributeType`
      const effectKeyCount = `Blood${index}_AttributeValue`

      const upgradeEffectKeyCount = `ForceReward_Attribute0${index}Value`
      if (costObject[effectKeyId] !== 0) {
        obj[bloodName][
          getItemName(statusData[costObject[effectKeyId]].StringId)
        ] = costObject[effectKeyCount]
        //  + upgradeObject[upgradeEffectKeyCount]
      }

      const objectKeyId = `Blood${index}_Training_NeedUse01Id`
      const objectKeyCount = `Blood${index}_Training_NeedItem01Count`
      if (costObject[objectKeyId] !== 0) {
        obj[bloodName][
          getItemName(getItemNameSid(costObject[objectKeyId] + 100000))
        ] = costObject[objectKeyCount]
      }

      const objectKeyId2 = `Blood${index}_Training_NeedUse02Id`
      const objectKeyCount2 = `Blood${index}_Training_NeedItem02Count`
      if (costObject[objectKeyId2] !== 0) {
        obj[bloodName][
          getItemName(getItemNameSid(costObject[objectKeyId2] + 100000))
        ] = costObject[objectKeyCount2]
      }

      return null
    })
    .filter(Boolean)

  return obj
}

function getItemNameSid(itemId) {
  return itemsData[itemId]?.NameSid
}

function getSetId(setId) {
  return BloodSetName[setId.toString().at(-1)]
}

function getItemName(id) {
  return stringData[id]?.ENG
}

fs.writeFileSync('./FORCE_LEVEL.json', JSON.stringify(CostJSON, null, 2))
