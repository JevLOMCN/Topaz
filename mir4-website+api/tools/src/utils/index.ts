import { type Level } from '@/app/xp/page'
import {
  ArbalistFU,
  ArbalistInnerForce,
  DarkistFU,
  DarkistInnerForce,
  LancerFU,
  LancerInnerForce,
  SorcererFU,
  SorcererInnerForce,
  TaoistFU,
  TaoistInnerForce,
  WarriorFU,
  WarriorInnerForce,
} from '@/data/InnerForce'
import {
  Alkaid,
  Antirelaxation,
  CentenaryCongregation,
  DiviseAction,
  Dubhe,
  EarthValley,
  EmbroideredThrone,
  FertileScale,
  GoldenJade,
  GreatRuler,
  GreatUnion,
  LandsEnd,
  Mizar,
  PinnacleStar,
  PulsingSky,
  Quorum,
  RoyalDecree,
  SkyPalace,
  Springwater,
  UnitedHeaven,
  VirtuousElevation,
  Waterbridge,
  WindHub,
} from '@/icons/inner-force/index'
import { atom } from 'jotai'

export const rarityRegex = /(\[L\].|\[E\].|\[R\].|\[UC\].)/gm

export const ComplementaryItems = [
  'Darksteel',
  'Copper',
  'Energy',
  'Glittering Powder',
]

export const atomWithLocalStorage = <T>(key: string, initialValue: T) => {
  if (typeof window === 'undefined') return atom(initialValue) as any

  const getInitialValue = () => {
    const item = localStorage.getItem(key)
    try {
      return JSON.parse(item ?? '')
    } catch {
      return initialValue
    }
  }

  const baseAtom = atom(getInitialValue())

  const derivedAtom = atom(
    (get) => get(baseAtom),
    (get, set, update) => {
      const nextValue =
        typeof update === 'function' ? update(get(baseAtom)) : update
      set(baseAtom, nextValue)
      localStorage.setItem(key, JSON.stringify(nextValue))
    }
  )
  return derivedAtom
}

export const formatForExperience = (value: string) => {
  value = value.replace(/\D/g, '')
  value = value.replace(/^(\d{6})(.+)/g, '$1')
  return value.replace(/^(\d{1,2})(\d{4})/, '$1.$2')
}

export const formatForPercentage = (value: string) => {
  value = value.replace(/\D/g, '')
  return value.replace(/^(\d{3})(.+)/g, '$1')
}

export const formatLevel = (value: string): Level => {
  value = value.replace(/\D/g, '')
  value = value.replace(/^(\d{3})(.+)/g, '$1')
  return Number(value) > 190 ? 190 : (Number(value) as Level)
}

export const getPercentage = (
  value: string | number,
  percentage?: string | number
) => Number(value) * (Number(percentage ?? 0) / 100)

export const getReadableNumber = (number: number) =>
  Math.round(number).toLocaleString('en', { useGrouping: true })

export const getValidNumber = (
  value: string | number,
  fallbackValue: number
) => {
  value = String(value).replace(/\D/g, '')
  return Number.isInteger(Number(value)) ? Number(value) : fallbackValue
}

export function retrieveWalkthroughFromStorage() {
  if (typeof window !== 'undefined') {
    return JSON.parse(localStorage.getItem('Walkthrough') ?? '{}')
  }
  return {}
}

// Get the quantity of items of tier one to craft the selected  tier
export const itemTierToQuantity = {
  1: 1,
  2: 2,
  3: 4,
  4: 8,
} as const

export const ItemRarities: RarityTypes[] = [
  'Legendary',
  'Epic',
  'Rare',
  'Uncommon',
  'Common',
]

export const AllowedInventoryItemTypes = [
  'anima_stone',
  'blue_devil_stone',
  'copper',
  'darksteel',
  'dragon_leather',
  'energy',
  'evil_minded_orb',
  'exorcism_bauble',
  'glittering_powder',
  'illuminating_fragment',
  'moon_shadow_stone',
  'platinum',
  'quintessence',
  'steel',
  'dragon_eye',
  'dragon_scale',
  'dragon_claw',
  'dragon_horn',
  'moonlight_magic_stone',
  'century_fruit',
  'eternal_snow_panax',
  'flower_oil',
  'herb_leaf',
  'herb_root',
  'purified_water',
  'reishi',
  'snow_panax',
  'unihorn_slice',
  'virtue_pill',
  'greater_yang_pill',
  'greater_yin_pill',
  'lesser_yang_pill',
  'lesser_yin_pill',
  'epic_azureum_mineral_fluid',
]

export function deepMerge(targetObject: any, sourceObject: any) {
  const copyTargetObject = structuredClone(targetObject)
  const copySourceObject = structuredClone(sourceObject)

  Object.keys(copySourceObject).forEach((key) => {
    if (
      typeof copySourceObject[key] === 'object' &&
      !Array.isArray(copySourceObject[key])
    ) {
      copyTargetObject[key] = deepMerge(
        copyTargetObject[key],
        copySourceObject[key]
      )
    } else {
      copyTargetObject[key] = copySourceObject[key]
    }
  })

  return copyTargetObject
}

export const sumObjects = <T extends { [key in string]: number }>(
  data: T[]
): any => {
  const result: any = {}

  data.forEach((object) => {
    for (const [key, value] of Object.entries(object)) {
      if (key in result) {
        result[key] = (result[key] as number) + value
      } else {
        result[key] = value
      }
    }
  })
  return result
}

export const prepareItemForDisplay = (
  data: Array<{ [key in ItemTypes]: number }>
): ItemForDisplay[] => {
  return Object.entries(data).map(([item, amount]) => {
    const rarity = extractItemRarity(item)
    let name = item

    if (rarity !== 'Common') {
      name = name.substring(name.indexOf(' ') + 1)
    }

    return {
      name,
      rarity,
      amount,
    }
  }) as any
}

export const extractItemRarity = (name: string): RarityTypes | 'Default' => {
  if (name === 'Copper' || name === 'Darksteel') return 'Default'
  if (name === 'Glittering Powder' || name === 'Life Essence') return 'Uncommon'
  if (name === 'Epic Azureum Mineral Fluid') return 'Epic'

  const rarity = name.match(/^([\S]+)/gm)

  if (rarity === null) return 'Common'

  switch (rarity[0]) {
    case '[L]':
      return 'Legendary'
    case '[E]':
      return 'Epic'
    case '[R]':
      return 'Rare'
    case '[UC]':
      return 'Uncommon'
    default:
      return 'Common'
  }
}

export const getItemImagePath = (
  props: (
    | {
        category: 'weapon'
        weaponType: 'primary' | 'secondary'
      }
    | { category: Exclude<ItemCategory, 'weapon'> }
  ) & {
    rarity: RarityTypes
  }
) => {
  const { category, rarity } = props
  switch (category) {
    case 'weapon':
      return `/items/weapon_${rarity}_${props.weaponType}.webp`
    case 'armor':
      return `/items/armor_${rarity}.webp`
    case 'jewelry':
      return `/items/accessory_${rarity}_1.webp`
    case 'earrings':
      return `/items/accessory_${rarity}_2.webp`
  }
}

export const toCamelCase = (string?: string) =>
  (string ?? '').toLocaleLowerCase().replace(/\s/g, '_')

export const createNodeGroups = (currentMapPoints: {
  [key in string]: nodeObject
}) => {
  const groupedNodes: Record<string, Array<nodeObject & { id: string }>> = {}
  if (!currentMapPoints) return []
  Object.entries(currentMapPoints).forEach(([id, { pos, type, rarity }]) => {
    const sum = `${Math.round(pos[0] / 5)} ${Math.round(
      pos[1] / 5
    )} ${type} ${rarity}`
    const obj = {
      pos: [pos[0], pos[1]] as [number, number],
      type,
      rarity,
      id,
    }

    if (sum in groupedNodes) {
      groupedNodes[sum].push(obj)
    } else groupedNodes[sum] = [obj]
  })

  const readyToDisplayGroups: {
    [key in string]: nodeObject & {
      amount: number
    }
  } = {}
  Object.values(groupedNodes).forEach((values) => {
    const reduceResult = values.reduce(
      (acc, cur) => {
        acc.left += cur.pos[0]
        acc.right += cur.pos[1]
        return acc
      },
      { left: 0, right: 0 }
    )

    const amount = values.length
    const valueX = (reduceResult.left / amount).toFixed(2)
    const valueY = (reduceResult.right / amount).toFixed(2)
    const { rarity, type } = values[0]

    readyToDisplayGroups[values[0].id] = {
      pos: [Number(valueX), Number(valueY)],
      amount,
      rarity,
      type,
    }
  })

  return readyToDisplayGroups
}

export function formatItemName(name: string): ItemWithRarity {
  const nameWithoutRarity = name.replace(rarityRegex, '')

  return nameWithoutRarity
    .toLocaleLowerCase()
    .replace(/\s/g, '_') as ItemWithRarity
}

// Get Value either by using percentage or seconds input
export function getMiningSpeedValue(input: number, isPercentage: boolean) {
  const baseSeconds = 10
  if (isPercentage) {
    const outputValue = baseSeconds / (input / 100 + 1)
    return outputValue.toFixed(2)
  }

  const percentageValue = (100 * baseSeconds) / input - 100
  return Math.round(percentageValue).toString()
}

export function getNumbersInRange(start: number, end: number) {
  return Array.from(
    { length: (end - start) / 1 + 1 },
    (_, index) => start + index * 1
  )
}

export const getBloodIcon = {
  Alkaid,
  Antirelaxation,
  'Centenary Congregation': CentenaryCongregation,
  'Divisive Action': DiviseAction,
  Dubhe,
  'Earth Valley': EarthValley,
  'Embroidered Throne': EmbroideredThrone,
  'Fertile Scale': FertileScale,
  'Golden Jade': GoldenJade,
  'Great Ruler': GreatRuler,
  'Great Union': GreatUnion,
  'Heart Core': SkyPalace,
  "Land's End": LandsEnd,
  Mizar,
  'Pinnacle Star': PinnacleStar,
  'Pulsing Sky': PulsingSky,
  Quorum,
  'Royal Decree': RoyalDecree,
  'Sky Palace': SkyPalace,
  Springwater,
  'United Heaven': UnitedHeaven,
  'Virtuous Elevation': VirtuousElevation,
  Waterbridge,
  'Wind Hub': WindHub,
}

export const bloodNameToSet: { [key in BloodNames]: BloodSets } = {
  'Sky Palace': 'Muscle Strength Manual',
  'Royal Decree': 'Muscle Strength Manual',
  'Pulsing Sky': 'Muscle Strength Manual',
  'Great Ruler': 'Muscle Strength Manual',
  "Land's End": 'Nine Yin Manual',
  'Centenary Congregation': 'Nine Yin Manual',
  'Embroidered Throne': 'Nine Yin Manual',
  'Golden Jade': 'Nine Yin Manual',
  'Heart Core': 'Nine Yang Manual',
  'Virtuous Elevation': 'Nine Yang Manual',
  Antirelaxation: 'Nine Yang Manual',
  Springwater: 'Nine Yang Manual',
  'Pinnacle Star': 'Violet Mist Art',
  'Wind Hub': 'Violet Mist Art',
  'Great Union': 'Violet Mist Art',
  'Earth Valley': 'Violet Mist Art',
  Dubhe: 'Northern Profound Art',
  'Fertile Scale': 'Northern Profound Art',
  Mizar: 'Northern Profound Art',
  Alkaid: 'Northern Profound Art',
  'Divisive Action': 'Toad Stance',
  Waterbridge: 'Toad Stance',
  'United Heaven': 'Toad Stance',
  Quorum: 'Toad Stance',
}

export const getBloodsByTab: { [key in BloodSets]: BloodNames[] } = {
  'Muscle Strength Manual': [
    'Sky Palace',
    'Royal Decree',
    'Pulsing Sky',
    'Great Ruler',
  ],
  'Nine Yin Manual': [
    "Land's End",
    'Centenary Congregation',
    'Embroidered Throne',
    'Golden Jade',
  ],
  'Nine Yang Manual': [
    'Heart Core',
    'Virtuous Elevation',
    'Antirelaxation',
    'Springwater',
  ],
  'Violet Mist Art': [
    'Pinnacle Star',
    'Wind Hub',
    'Great Union',
    'Earth Valley',
  ],
  'Northern Profound Art': ['Dubhe', 'Fertile Scale', 'Mizar', 'Alkaid'],
  'Toad Stance': ['Divisive Action', 'Waterbridge', 'United Heaven', 'Quorum'],
}

export const getDataByClass = {
  Warrior: WarriorInnerForce,
  Sorcerer: SorcererInnerForce,
  Taoist: TaoistInnerForce,
  Arbalist: ArbalistInnerForce,
  Lancer: LancerInnerForce,
  Darkist: DarkistInnerForce,
}

export const getUpgradeDataByClass = {
  Warrior: WarriorFU,
  Sorcerer: SorcererFU,
  Taoist: TaoistFU,
  Arbalist: ArbalistFU,
  Lancer: LancerFU,
  Darkist: DarkistFU,
}

export const effectToBloodName: { [key in string]: BloodNames } = {
  HP: 'Virtuous Elevation',
  MP: 'Antirelaxation',
  'PHYS ATK': 'Sky Palace',
  'PHYS DEF': 'Pulsing Sky',
  Accuracy: "Land's End",
  EVA: 'Centenary Congregation',
  CRIT: 'Embroidered Throne',
  'CRIT ATK DMG Boost': 'Divisive Action',
  'CRIT DMG Reduction': 'Waterbridge',
  'CRIT EVA': 'Springwater',
  'Spell ATK': 'Sky Palace',
  'Spell DEF': 'Great Ruler',
  'Monster ATK DMG Boost': 'Royal Decree',
  'Monster DMG Reduction': 'Golden Jade',
  'Skill ATK DMG Boost': 'United Heaven',
  'Skill DMG Reduction': 'Quorum',
  'Stun Success Boost': 'Pinnacle Star',
  'Stun RES Boost': 'Wind Hub',
  'Debilitation Success Boost': 'Great Union',
  'Debilitation RES Boost': 'Earth Valley',
  'Silence Success Boost': 'Dubhe',
  'Silence RES Boost': 'Fertile Scale',
  'Knockdown Success Boost': 'Mizar',
  'Knockdown RES Boost': 'Alkaid',
}

export function getBloodSetObject(
  currentSet: BloodSets,
  originalObject: Partial<{ [key in BloodNames]: { initial: number; final: number } }>
): Partial<{ [key in BloodNames]: { initial: number; final: number } }> {
  const {
    'Sky Palace': SkyPalace,
    'Royal Decree': RoyalDecree,
    'Pulsing Sky': PulsingSky,
    'Great Ruler': GreatRuler,
    "Land's End": LandsEnd,
    'Centenary Congregation': CentenaryCongregation,
    'Embroidered Throne': EmbroideredThrone,
    'Golden Jade': GoldenJade,
    'Heart Core': HeartCore,
    'Virtuous Elevation': VirtuousElevation,
    Antirelaxation,
    Springwater,
    'Pinnacle Star': PinnacleStar,
    'Wind Hub': WindHub,
    'Great Union': GreatUnion,
    'Earth Valley': EarthValley,
    Dubhe,
    'Fertile Scale': FertileScale,
    Mizar,
    Alkaid,
    'Divisive Action': DiviseAction,
    Waterbridge,
    'United Heaven': UnitedHeaven,
    Quorum,
  } = originalObject

  switch (currentSet) {
    case 'Muscle Strength Manual':
      return {
        'Sky Palace': SkyPalace,
        'Royal Decree': RoyalDecree,
        'Pulsing Sky': PulsingSky,
        'Great Ruler': GreatRuler,
      }
    case 'Nine Yin Manual':
      return {
        "Land's End": LandsEnd,
        'Centenary Congregation': CentenaryCongregation,
        'Embroidered Throne': EmbroideredThrone,
        'Golden Jade': GoldenJade,
      }
    case 'Nine Yang Manual':
      return {
        'Heart Core': HeartCore,
        'Virtuous Elevation': VirtuousElevation,
        Antirelaxation,
        Springwater,
      }
    case 'Violet Mist Art':
      return {
        'Pinnacle Star': PinnacleStar,
        'Wind Hub': WindHub,
        'Great Union': GreatUnion,
        'Earth Valley': EarthValley,
      }
    case 'Northern Profound Art':
      return { Dubhe, 'Fertile Scale': FertileScale, Mizar, Alkaid }
    case 'Toad Stance':
      return {
        'Divisive Action': DiviseAction,
        Waterbridge,
        'United Heaven': UnitedHeaven,
        Quorum,
      }
  }
}

export const calculateBloodCost = (
  bloodObject: Partial<{ [key in BloodNames]: { initial: number; final: number } }>,
  mir4Class: Mir4Classes
) => {
  const dataObject = getDataByClass[mir4Class ?? 'Arbalist']

  const resultObj: { [key in string]: number } = {}
  for (const [bloodName, { initial, final }] of Object.entries(bloodObject)) {
    if (initial === final) continue

    const levelDifference = final - initial
    if (levelDifference < 1) continue

    const levelIteration = getNumbersInRange(initial + 1, final)
    for (const levelstep of levelIteration) {
      const bloodLevel =
        dataObject[bloodNameToSet[bloodName as BloodNames]][
          levelstep as keyof (typeof dataObject)[BloodSets]
        ]
      const bloodContent = bloodLevel[bloodName as keyof typeof bloodLevel]

      resultObj.energy =
        (resultObj?.energy || 0) + (bloodLevel.EnergyPerClick as number)

      for (const [key, value] of Object.entries(bloodContent)) {
        resultObj[key] = (resultObj?.[key] || 0) + (value as number)
      }
    }
  }

  return resultObj
}

export const calculateBloodEffects = (
  bloodObject: Partial<{
    [key in BloodNames]: { initial: number; final: number }
  }>,
  mir4Class: Mir4Classes,
  showInnerForcePromotion: boolean
) => {
  const dataObject = getDataByClass[mir4Class ?? 'Arbalist']
  const upgradeData = getUpgradeDataByClass[mir4Class ?? 'Arbalist']

  const resultObj: { [key in string]: { initial: number; final: number } } = {}

  for (const [bloodName, { initial, final }] of Object.entries(bloodObject)) {
    const levelDifference = final - initial
    if (levelDifference < 0) continue

    const initialTier = Math.floor(initial / 5) + 1
    let finalTier = Math.floor((final - 1) / 5) + 1

    if (finalTier < 0) continue

    const bloodSet = bloodNameToSet[bloodName as BloodNames]
    let bloodLevel =
      dataObject[bloodSet][initial as keyof (typeof dataObject)[BloodSets]]
    let bloodContent = bloodLevel[bloodName as keyof typeof bloodLevel]
    const upgradeObject = upgradeData[bloodSet]

    for (const [key, value] of Object.entries(bloodContent)) {
      if (AllowedInventoryItemTypes.includes(formatItemName(key))) continue
      const upgradeEffectValue = Number(
        upgradeObject?.[
          Math.min(
            initialTier,
            getMaxIFTier[bloodSet]
          ) as keyof typeof upgradeObject
        ]?.Effects?.[key as keyof (typeof upgradeObject)['1']['Effects']] ?? 0
      )

      resultObj[key] = {
        initial: Number(value ?? 0) + upgradeEffectValue,
        final: 0,
      }
    }

    bloodLevel =
      dataObject[bloodNameToSet[bloodName as BloodNames]][
        final as keyof (typeof dataObject)[BloodSets]
      ]
    bloodContent = bloodLevel[bloodName as keyof typeof bloodLevel]

    for (const [key, value] of Object.entries(bloodContent)) {
      if (AllowedInventoryItemTypes.includes(formatItemName(key))) continue

      const addTierCondition = final % 5 === 0 && showInnerForcePromotion
      if (addTierCondition) {
        finalTier = Math.min(finalTier + 1, getMaxIFTier[bloodSet])
      }

      const upgradeEffectValue = Number(
        upgradeObject?.[finalTier as keyof typeof upgradeObject]?.Effects?.[
          key as keyof (typeof upgradeObject)['1']['Effects']
        ] ?? 0
      )

      const finalValue = Number(value ?? 0) + upgradeEffectValue

      resultObj[key] = {
        ...resultObj[key],
        final: finalValue,
      }
    }
  }

  return resultObj
}

export const calculateUpgradeCost = (
  bloodObject: Partial<{ [key in BloodNames]: { initial: number; final: number } }>,
  mir4Class: Mir4Classes
) => {
  const sets: Partial<{ [key in BloodSets]: { start: number; end: number } }> =
    {}
  const resultObj: { [key in string]: number } = {}
  const upgradeData = getUpgradeDataByClass[mir4Class]

  for (const [bloodName, { initial, final }] of Object.entries(bloodObject)) {
    if (initial === final) continue

    const levelDifference = final - initial
    if (levelDifference < 1) continue

    const targetedObject = getBloodSetObject(
      bloodNameToSet[bloodName as BloodNames],
      bloodObject
    )

    const minLevel = Math.min(
      ...Object.values(targetedObject).map((values) => values.initial)
    )
    const maxLevel = Math.max(
      ...Object.values(targetedObject).map((values) => values.final)
    )
    const currentTier = Math.floor(minLevel / 5) + 1
    const nextTier = Math.floor(maxLevel / 5)

    const currentSet = bloodNameToSet[bloodName as BloodNames]

    sets[currentSet] = {
      start: Math.min(sets?.[currentSet]?.start ?? currentTier, currentTier),
      end: Math.max(sets?.[currentSet]?.end ?? nextTier, nextTier),
    }
  }

  for (const [setName, { start, end }] of Object.entries(sets)) {
    const levelIteration = getNumbersInRange(start, end)
    for (const levelstep of levelIteration) {
      const bloodObject = upgradeData[setName as keyof typeof upgradeData]
      const content = bloodObject[levelstep as keyof typeof bloodObject]

      for (const [key, value] of Object.entries(content)) {
        if (key === 'Effects') continue
        resultObj[key] = (resultObj?.[key] || 0) + (value as number)
      }
    }
  }

  return resultObj
}

export function formatEffectValue(name: string, value: number) {
  switch (name) {
    case 'CRIT ATK DMG Boost':
    case 'CRIT DMG Reduction':
    case 'Monster ATK DMG Boost':
    case 'Monster DMG Reduction':
    case 'Skill ATK DMG Boost':
    case 'Skill DMG Reduction':
    case 'Stun Success Boost':
    case 'Stun RES Boost':
    case 'Debilitation Success Boost':
    case 'Debilitation RES Boost':
    case 'Silence Success Boost':
    case 'Silence RES Boost':
    case 'Knockdown Success Boost':
    case 'Knockdown RES Boost':
      return `${value / 10}%`
    default:
      return getReadableNumber(value)
  }
}

export function getValidBloodValue(bloodTab: BloodSets, value: number) {
  let MAX_VALUE = 100

  if (
    ['Violet Mist Art', 'Northern Profound Art', 'Toad Stance'].includes(
      bloodTab
    )
  ) {
    MAX_VALUE = 60
  }

  if (value < 0) return 0
  if (value > MAX_VALUE) return MAX_VALUE
  return value
}

export const getMaxIFTier: { [key in BloodSets]: number } = {
  'Muscle Strength Manual': 20,
  'Nine Yang Manual': 20,
  'Nine Yin Manual': 20,
  'Northern Profound Art': 12,
  'Toad Stance': 12,
  'Violet Mist Art': 12,
}
