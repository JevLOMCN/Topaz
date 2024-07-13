import { atom } from 'jotai'
import { atomWithStorage } from 'jotai/utils'

const defaultLevels = {
  'PHYS DEF': {
    from: 1,
    to: 5,
  },
  HP: {
    from: 1,
    to: 5,
  },
  EVA: {
    from: 1,
    to: 5,
  },
  'PHYS ATK': {
    from: 1,
    to: 5,
  },
  Accuracy: {
    from: 1,
    to: 5,
  },
  MP: {
    from: 1,
    to: 5,
  },
  'Spell DEF': {
    from: 1,
    to: 5,
  },
}

export const statusAtom = atom<statusEffects | null>(null)

export const statusLevelsAtom = atomWithStorage<statusLevels>('Mir4Tools_Constitution', defaultLevels)

export const constitutionUpgradeAtom = atom<{ masteryIteration: number[] }>({
  masteryIteration: [],
})
