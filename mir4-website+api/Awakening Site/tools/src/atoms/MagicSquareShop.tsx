import { atomWithStorage } from 'jotai/utils'

export type ShardsInventoryType = {
  [key in shardsType]: {
    [key in 'Epic' | 'Rare' | 'Uncommon']: number
  }
}

export const ShardsInventoryAtom = atomWithStorage<ShardsInventoryType>(
  'Mir4Tools_Shards',
  {
    ethereal_shard: {
      Epic: 0,
      Rare: 0,
      Uncommon: 0,
    },
    lunar_shard: {
      Epic: 0,
      Rare: 0,
      Uncommon: 0,
    },
    solar_shard: {
      Epic: 0,
      Rare: 0,
      Uncommon: 0,
    },
    boundless_shard: {
      Epic: 0,
      Rare: 0,
      Uncommon: 0,
    },
  }
)
