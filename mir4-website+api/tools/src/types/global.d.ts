type RarityTypes = 'Legendary' | 'Epic' | 'Rare' | 'Uncommon' | 'Common'

type ItemTypes =
  | 'anima_stone'
  | 'blue_devil_stone'
  | 'copper'
  | 'darksteel'
  | 'dragon_leather'
  | 'energy'
  | 'evil_minded_orb'
  | 'exorcism_bauble'
  | 'glittering_powder'
  | 'illuminating_fragment'
  | 'moon_shadow_stone'
  | 'platinum'
  | 'quintessence'
  | 'steel'
  | 'dragon_eye'
  | 'dragon_scale'
  | 'dragon_claw'
  | 'dragon_horn'
  | 'moonlight_magic_stone'
  | 'century_fruit'
  | 'eternal_snow_panax'
  | 'flower_oil'
  | 'herb_leaf'
  | 'herb_root'
  | 'purified_water'
  | 'reishi'
  | 'snow_panax'
  | 'unihorn_slice'
  | 'virtue_pill'

type ItemWithRarity = Exclude<
  ItemTypes,
  'copper' | 'darksteel' | 'energy' | 'glittering_powder'
>
type NonRarityItems = Exclude<ItemTypes, ItemWithRarity>

type InventoryType = {
  [key in ItemWithRarity]: {
    [key in RarityTypes]: { traddable: number; nonTraddable: number }
  }
} & {
  [key in NonRarityItems]: number
}

type ItemCostObject = {
  [key in Exclude<RarityTypes, 'Uncommon' | 'Common'>]: {
    [key in string]: number
  }
}

type EquipmentCost = {
  [key in Exclude<ItemCategory, 'weapon'>]: ItemCostObject
} & { ['weapon']: { [key in 'primary' | 'secondary']: ItemCostObject } }

interface InventoryItem {
  traddable: number
  nonTraddable: number
}

interface PercentageState {
  initial?: string
  final?: string
}

type ItemCategory = 'weapon' | 'armor' | 'jewelry' | 'earrings'

type ItemTier = 1 | 2 | 3 | 4

interface SettingsObject {
  displayRarity: RarityTypes[]
  showOwnedItems: boolean
  language: 'en' | 'pt'
}

type ConquestTowers =
  | 'Tower of Conquest'
  | 'Forge'
  | 'Mine'
  | 'Millennial Tree'
  | 'Training Sanctum'
  | 'Tower of Quintessence'
  | 'Tower of Victory'
  | 'Holy Shrine'
  | 'Portal'
  | 'Sanctuary of Hydra'

interface ItemForDisplay {
  name: string
  rarity: RarityTypes | 'Default'
  amount: number
}

type statusLevels = {
  [key in statusEffects]: { from: number; to: number }
}

type statusEffects =
  | 'PHYS DEF'
  | 'HP'
  | 'EVA'
  | 'PHYS ATK'
  | 'Accuracy'
  | 'MP'
  | 'Spell DEF'

type shardsType =
  | 'ethereal_shard'
  | 'lunar_shard'
  | 'solar_shard'
  | 'boundless_shard'

type Mir4Classes =
  | 'Warrior'
  | 'Sorcerer'
  | 'Taoist'
  | 'Arbalist'
  | 'Lancer'
  | 'Darkist'
