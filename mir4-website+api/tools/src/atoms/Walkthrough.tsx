import { atom } from 'jotai'

interface WalkthroughStagesType {
  stage: number
  isActive: boolean
  stages: Array<{ id: string; content: string; title: string }>
  type: 'crafting' | 'xp' | null
}

export const WalkthroughAtom = atom<WalkthroughStagesType>({
  stage: 0,
  isActive: false,
  stages: [],
  type: null,
})
