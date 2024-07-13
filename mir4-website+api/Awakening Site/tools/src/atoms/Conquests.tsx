import { atom } from 'jotai'

export const ConquestsAtom = atom<{ tower: ConquestTowers; stage: number }>({
  tower: 'Mine',
  stage: 0,
})
