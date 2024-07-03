const achievement = require('./ACHIEVEMENT.json')
const stringTemplate = require('./STRING_TEMPLATE.json')
const conquest = require('./GREAT_BUILDING.json')
const conquestStep = require('./GREAT_BUILDING_STEP.json')
const money = require('./MONEY.json')
const fs = require('fs')

const ConquestTowersData = {}
const toTranslateENG = {}
const toTranslatePT = {}
const toTranslateES = {}

const SEPTARIA_STRING_KEY = 5000103
const CHARACTER_MAX_LVL_STRING_KEY = 5000054
const MAGIC_STONE_SLOT_STRING_KEY = 500030
const UNSEALED_SLOT_STRING_KEY = 500167
const SQUARE_TICKET_STRING_KEY = 5000098
const PEAK_TICKET_STRING_KEY = 5000099
const SPIRIT_SLOT_STRING_KEY = 5000059
const MAX_RAID_TICKET_STRING_KEY = 5000100
const MYSTICAL_PIECE_SLOT_STRING_KEY = 500174
const EXP_RESTORE_STRING_KEY = 5000101

const HUNTING_EXP_TYPE_KEY = 82
const COPPER_GAIN_BOOST_TYPE_KEY = 84
const ENERGY_GAIN_BOOST_TYPE_KEY = 86
const DARKSTEEL_GAIN_BOOST_TYPE_KEY = 87
const PVP_ATK_DMG_BOOST_TYPE_KEY = 38
const PVP_DMG_REDUCTION_TYPE_KEY = 39
const BOSS_ATK_DMG_BOOST_TYPE_KEY = 41
const BOSS_DMG_REDUCTION_TYPE_KEY = 43
const MARKET_TAX_TYPE_KEY = 118

const towers = conquest[0].Rows
const towersStep = Object.values(conquestStep[0].Rows)
const stringData = stringTemplate[0].Rows
const achievementData = achievement[0].Rows

function dumpConquestTowers() {
  for (const stepObj of towersStep) {
    const { BuildingStep, BuildingCombat, GreatBuildingId, UpgradeTime } =
      stepObj

    const dataObject = {
      Step: BuildingStep,
      Power: BuildingCombat,
      UpgradeTime,
      Cost: getStepCosts(stepObj),
      Building: getStepBuildingConditions(stepObj),
      Achievement: getStepAchievements(stepObj),
      Effects: getStepEffects(stepObj),
    }

    const buildingName = stringData[towers[GreatBuildingId].BuildingName].ENG
    if (buildingName in ConquestTowersData) {
      ConquestTowersData[buildingName].Steps.push(dataObject)
    } else {
      ConquestTowersData[buildingName] = {
        BuildingId: GreatBuildingId,
        Steps: [dataObject],
      }
    }
  }
}

function getStepCosts(stepObj) {
  const object = {}

  Array.from(Array(4).keys()).forEach((index) => {
    const idKey = `NeedID0${index + 1}`
    const countKey = `NeedCount0${index + 1}`

    if (stepObj[idKey] === 0) return
    const moneyStringId = money[0].Rows[stepObj[idKey]].NameSid
    const moneyName = stringData[moneyStringId].ENG

    object[moneyName] = stepObj[countKey]
  })

  return object
}

function getStepEffects(stepObj) {
  const object = {}

  Array.from(Array(4).keys()).forEach((index) => {
    const stringKey = `EffectString0${index + 1}`
    const typeKey = `EffectValue0${index + 1}`
    const valueKey = `EffectNum0${index + 1}`

    if (stepObj[stringKey][0] === 0) return
    let value = stepObj[valueKey][0]

    // value formatted according to effect type
    switch (stepObj[typeKey][0]) {
      case HUNTING_EXP_TYPE_KEY:
      case COPPER_GAIN_BOOST_TYPE_KEY:
      case ENERGY_GAIN_BOOST_TYPE_KEY:
      case DARKSTEEL_GAIN_BOOST_TYPE_KEY:
        value = `${value / 100}.00%`
        break
      case PVP_ATK_DMG_BOOST_TYPE_KEY:
      case PVP_DMG_REDUCTION_TYPE_KEY:
      case BOSS_ATK_DMG_BOOST_TYPE_KEY:
      case BOSS_DMG_REDUCTION_TYPE_KEY:
        value = `${value / 10}.00%`
        break
      case MARKET_TAX_TYPE_KEY:
        value = `-${value / 100}% (${25 - value / 100}%)`
        break
    }

    // specific value handler
    switch (stepObj[stringKey][0]) {
      case SEPTARIA_STRING_KEY:
        value = stringData[stepObj[typeKey][0]].ENG
        break
      case CHARACTER_MAX_LVL_STRING_KEY:
        value = stepObj[typeKey][0]
        break
      case MAGIC_STONE_SLOT_STRING_KEY:
      case SPIRIT_SLOT_STRING_KEY:
        value =
          stepObj[typeKey][0] > 0 ? stringData[stepObj[typeKey][0]].ENG : '-'
        break
      case UNSEALED_SLOT_STRING_KEY:
      case EXP_RESTORE_STRING_KEY:
        value = `+${stepObj[typeKey][0]}`
        break
      case SQUARE_TICKET_STRING_KEY:
      case PEAK_TICKET_STRING_KEY:
      case MAX_RAID_TICKET_STRING_KEY:
      case MYSTICAL_PIECE_SLOT_STRING_KEY:
        value = `+${stepObj[valueKey][0]}`
        break
    }

    const effectName = stringData[stepObj[stringKey][0]].ENG

    object[effectName] = value
  })

  return object
}

function getStepAchievements(stepObj) {
  const object = {}

  Array.from(Array(3).keys()).forEach((index) => {
    const idKey = `AchievementIdCondition0${index + 1}`
    const stringKey = `AchievementIdConditionString0${index + 1}`

    if (stepObj[stringKey] === 0) return
    const count = achievementData[stepObj[idKey]].CompleteCount
    let achievementString = stringData[stepObj[stringKey]].ENG
    let achievementStringES = stringData[stepObj[stringKey]].SPA
    let achievementStringPT = stringData[stepObj[stringKey]].POR

    const achievementHasCount = achievementString.match(/\{1\}/gm)

    if (achievementHasCount) {
      achievementString = achievementString.replace(/\{1\}/gm, count)
      achievementStringES = achievementStringES.replace(/\{1\}/gm, count)
      achievementStringPT = achievementStringPT.replace(/\{1\}/gm, count)
    } else if (count > 1) {
      achievementString = `${achievementString} ${count}x`
      achievementStringES = `${achievementStringES} ${count}x`
      achievementStringPT = `${achievementStringPT} ${count}x`
    }

    object[stepObj[idKey]] = achievementString

    toTranslateENG[achievementString] = achievementString
    toTranslateES[achievementString] = achievementStringES
    toTranslatePT[achievementString] = achievementStringPT
  })

  return object
}

function getStepBuildingConditions(stepObj) {
  const object = {}

  Array.from(Array(3).keys()).forEach((index) => {
    const idKey = `BuildingIdCondition0${index + 1}`

    if (stepObj[idKey] === 0) return
    const buildingObject = conquestStep[0].Rows[stepObj[idKey]]
    const buildingName =
      stringData[towers[buildingObject.GreatBuildingId].BuildingName].ENG

    object[buildingName] = buildingObject.BuildingStep
  })
  return object
}

dumpConquestTowers()

fs.writeFileSync('../public/locales/en/conquest.json', JSON.stringify(toTranslateENG, null, 2))
fs.writeFileSync('../public/locales/pt/conquest.json', JSON.stringify(toTranslatePT, null, 2))
fs.writeFileSync('../public/locales/es/conquest.json', JSON.stringify(toTranslateES, null, 2))
fs.writeFileSync('./result.json', JSON.stringify(ConquestTowersData, null, 2))
