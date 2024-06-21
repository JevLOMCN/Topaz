import { atom } from 'jotai'

export const MapsAtom = atom<string[]>([
  'Global Map',
  // 'Snake Pit Area',
  // 'Snake Pit',
])

export const MapsFloorAtom = atom<number>(0)

export type rarityVibilityObject = { [key in RarityTypes]: boolean }

export const rarityVisibilityAtom = atom<{
  energy: rarityVibilityObject
  mining: rarityVibilityObject
  chest: rarityVibilityObject
  gather: rarityVibilityObject
  darksteel: rarityVibilityObject
}>({
  energy: {
    Legendary: true,
    Epic: true,
    Rare: true,
    Uncommon: true,
    Common: true,
  },
  mining: {
    Legendary: true,
    Epic: true,
    Rare: true,
    Uncommon: true,
    Common: true,
  },
  chest: {
    Legendary: true,
    Epic: true,
    Rare: true,
    Uncommon: true,
    Common: true,
  },
  gather: {
    Legendary: true,
    Epic: true,
    Rare: true,
    Uncommon: true,
    Common: true,
  },
  darksteel: {
    Legendary: true,
    Epic: true,
    Rare: true,
    Uncommon: true,
    Common: true,
  },
})

export const currentMapPointsAtom = atom<mapNodesObject>({})
