import { defaultInventoryValue } from '@/utils/craftingCalculator'
import { atom } from 'jotai'
import { atomWithStorage } from 'jotai/utils'

export const showInventoryAtom = atom(false)

export const InventoryAtom = atomWithStorage<InventoryType>(
  'Mir4Tools_Inventory',
  defaultInventoryValue
)
